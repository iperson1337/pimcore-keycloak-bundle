<?php

namespace Iperson1337\PimcoreKeycloakBundle\Security\Authenticator;

use Iperson1337\PimcoreKeycloakBundle\Provider\KeycloakResourceOwner;
use Iperson1337\PimcoreKeycloakBundle\Security\User\KeycloakUserProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Security\User\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Аутентификация через штатную форму /admin/login, но без редиректа в Keycloak:
 * логин/пароль формы проверяются напрямую в Keycloak через Resource Owner
 * Password Credentials grant (grant_type=password). Требует включённого
 * "Direct Access Grants Enabled" у клиента в Keycloak.
 */
class KeycloakDirectGrantAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const string PIMCORE_ADMIN_LOGIN = 'pimcore_admin_login';

    public const string PIMCORE_ADMIN_LOGIN_CHECK = 'pimcore_admin_login_check';

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly KeycloakUserProvider $userProvider,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly ?TranslatorInterface $translator = null
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return self::PIMCORE_ADMIN_LOGIN_CHECK === $request->attributes->get('_route')
            && $request->isMethod('POST')
            && $request->request->has('username')
            && $request->request->has('password');
    }

    public function authenticate(Request $request): Passport
    {
        $username = (string) $request->request->get('username');
        $password = (string) $request->request->get('password');

        if ('' === $username || '' === $password) {
            throw new CustomUserMessageAuthenticationException('Missing credentials');
        }

        $client = $this->clientRegistry->getClient('keycloak');
        $provider = $client->getOAuth2Provider();

        try {
            // Явно запрашиваем те же scopes, что и redirect-flow (KEYCLOAK_DEFAULT_SCOPES) —
            // без 'openid' Keycloak выдаёт токен без id_token, и /userinfo отвечает
            // 403 insufficient_scope с пустым не-JSON телом (не UnexpectedValueException
            // "Invalid response... Expected JSON" у League OAuth2 client).
            $accessToken = $provider->getAccessToken('password', [
                'username' => $username,
                'password' => $password,
                'scope' => $provider->defaultScopes,
            ]);

            $resourceOwner = $client->fetchUserFromToken($accessToken);
        } catch (IdentityProviderException $e) {
            $this->logger->info('Keycloak direct grant rejected', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            throw new CustomUserMessageAuthenticationException('Invalid credentials', [], 0, $e);
        } catch (\Throwable $e) {
            $this->logger->error('Keycloak direct grant failed', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            throw new CustomUserMessageAuthenticationException('Keycloak authentication failed', [], 0, $e);
        }

        if (!$resourceOwner instanceof KeycloakResourceOwner) {
            throw new CustomUserMessageAuthenticationException('Invalid Keycloak response');
        }

        $keycloakUser = $this->userProvider->loadUserByResourceOwner($resourceOwner, $accessToken);
        $pimcoreUser = $keycloakUser->getPimcoreUser();

        if (!$pimcoreUser) {
            $this->logger->error('No Pimcore user found or created for Keycloak direct grant login', [
                'username' => $username,
            ]);
            throw new CustomUserMessageAuthenticationException('Invalid Pimcore user');
        }

        $pimcoreUser->setTwoFactorAuthentication('required', false);

        $userBadge = new UserBadge($keycloakUser->getUserIdentifier(), static fn () => new User($pimcoreUser));

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $securityUser = $token->getUser();

        if ($securityUser instanceof User) {
            $pimcoreUser = $securityUser->getUser();

            // Язык интерфейса и legacy-совместимость — так же, как в штатном
            // Pimcore\Bundle\AdminBundle\Security\Authenticator\AdminAbstractAuthenticator
            $request->setLocale($pimcoreUser->getLanguage());
            if ($this->translator instanceof LocaleAwareInterface) {
                $this->translator->setLocale($pimcoreUser->getLanguage());
            }

            RuntimeCache::set('pimcore_admin_user', $pimcoreUser);
        }

        if ($request->get('deeplink') && $request->get('deeplink') !== 'true') {
            $url = $this->router->generate('pimcore_admin_login_deeplink');
            $url .= '?' . $request->get('deeplink');
        } else {
            $url = $this->router->generate('pimcore_admin_index', [
                '_dc' => time(),
                'perspective' => strip_tags((string) $request->get('perspective', '')),
            ]);
        }

        $response = new RedirectResponse($url);
        $response->headers->setCookie(new Cookie('pimcore_admin_sid', 'true'));

        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->info('Keycloak direct grant authentication failed', [
            'message' => $exception->getMessage(),
        ]);

        return new RedirectResponse($this->router->generate(self::PIMCORE_ADMIN_LOGIN, [
            'auth_failed' => 'true',
        ]));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set('loginReferrer', $request->getUri());
        }

        return new RedirectResponse($this->router->generate(self::PIMCORE_ADMIN_LOGIN));
    }
}
