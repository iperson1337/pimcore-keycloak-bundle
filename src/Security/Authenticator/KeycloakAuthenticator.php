<?php

namespace Iperson1337\PimcoreKeycloakBundle\Security\Authenticator;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Security\User\User;
use Pimcore\Tool\Authentication;
use Pimcore\Tool\Session;
use Psr\Log\LoggerInterface;
use Iperson1337\PimcoreKeycloakBundle\Provider\KeycloakResourceOwner;
use Iperson1337\PimcoreKeycloakBundle\Security\User\KeycloakUserProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class KeycloakAuthenticator extends OAuth2Authenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    public const PIMCORE_ADMIN_LOGIN_CHECK = 'pimcore_admin_login_check';

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly KeycloakUserProvider $userProvider,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly ?TranslatorInterface $translator = null
    ) {
    }

    public function supports(Request $request): bool
    {
        return 'iperson1337_pimcore_keycloak_auth_check' === $request->attributes->get('_route') && $request->query->has('code');
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $this->logger->debug('Starting Keycloak authentication');

            $client = $this->clientRegistry->getClient('keycloak');

            $this->logger->debug('Fetching access token from Keycloak');

            $accessToken = $this->fetchAccessToken($client);

            if (!$accessToken) {
                $this->logger->error('No access token received from Keycloak');
                throw new CustomUserMessageAuthenticationException('No access token');
            }

            $this->logger->debug('Fetching user information from Keycloak');

            $resourceOwner = $client->fetchUserFromToken($accessToken);

            if (!$resourceOwner instanceof KeycloakResourceOwner) {
                throw new CustomUserMessageAuthenticationException('Invalid access token');
            }

            $this->logger->debug('Loading user from resource owner', [
                'username' => $resourceOwner->getPreferredUsername()
            ]);

            $keycloakUser = $this->userProvider->loadUserByResourceOwner($resourceOwner, $accessToken);

            $pimcoreUser = $keycloakUser->getPimcoreUser();

            if (!$pimcoreUser) {
                $this->logger->error('No Pimcore user found or created');
                throw new CustomUserMessageAuthenticationException('Invalid Pimcore user');
            }

            $this->logger->debug('Pimcore user loaded successfully', [
                'username' => $pimcoreUser->getUsername()
            ]);

            // Сохраняем ID-токен в сессии для возможного single logout
            if (isset($resourceOwnerArray['id_token'])) {
                $request->getSession()->set('keycloak_id_token', $resourceOwnerArray['id_token']);
            }

            // Отключаем требование двухфакторной аутентификации
            if (method_exists($pimcoreUser, 'setTwoFactorAuthentication')) {
                $pimcoreUser->setTwoFactorAuthentication('required', false);
            }

            // Создаем бейдж пользователя для паспорта
            $userBadge = new UserBadge($keycloakUser->getUserIdentifier(), function () use ($pimcoreUser) {
                return new User($pimcoreUser);
            });

            return new SelfValidatingPassport($userBadge);
        } catch (\Exception $e) {
            $this->logger->error('Authentication error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new CustomUserMessageAuthenticationException('Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $securityUser = $token->getUser();
        if (!$securityUser instanceof User) {
            throw new \Exception('Invalid user object. User has to be instance of ' . User::class);
        }

        $user = $securityUser->getUser();


        // Устанавливаем язык пользователя
        $request->setLocale($user->getLanguage());
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($user->getLanguage());
        }

        // Устанавливаем пользователя в кэше для обратной совместимости
        RuntimeCache::set('pimcore_admin_user', $user);

        // Сохраняем пользователя в сессии
        if ($request->hasSession()) {
            $this->saveUserToSession($securityUser, $request->getSession());
        }

        if ($request->attributes->get('_route') != 'iperson1337_pimcore_keycloak_auth_check') {
            return null;
        }

        if ($request->get('deeplink') && $request->get('deeplink') !== 'true') {
            $url = $this->router->generate('pimcore_admin_login_deeplink');
            $url .= '?' . $request->get('deeplink');
        } else {
            $url = $this->router->generate(self::PIMCORE_ADMIN_LOGIN_CHECK, [
                'username' => $user->getUsername(),
                'token' => Authentication::generateToken($user->getUsername()),
                'perspective' => strip_tags($request->get('perspective', '')),
            ]);
        }

        if ($url) {
            $response = new RedirectResponse($url);
            $response->headers->setCookie(new Cookie('pimcore_admin_sid', 'true'));

            return $response;
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error('Authentication failure', [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        // Добавляем сообщение об ошибке в параметры URL
        $loginParams = ['login_error' => $exception->getMessage()];

        // Перенаправляем на страницу входа с сообщением об ошибке
        return new RedirectResponse($this->router->generate('pimcore_admin_login', $loginParams));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $this->logger->info('KeycloakEntryPoint: Перенаправление неаутентифицированного пользователя на Keycloak');

        if ($request->hasSession()) {
            $request->getSession()->set('loginReferrer', $request->getUri());
        }

        try {
            return new RedirectResponse($this->router->generate('iperson1337_pimcore_keycloak_auth_connect'));
        } catch (\Exception $e) {
            $this->logger->error('KeycloakEntryPoint: Ошибка при перенаправлении на Keycloak: ' . $e->getMessage());

            return new RedirectResponse($this->router->generate('pimcore_admin_login', [
                'login_error' => 'Ошибка при подключении к SSO. Пожалуйста, попробуйте использовать стандартный вход.'
            ]));
        }
    }

    /**
     * Сохраняет пользователя в сессии
     */
    protected function saveUserToSession(User $user, SessionInterface $session): void
    {
        if (Authentication::isValidUser($user->getUser())) {
            $pimcoreUser = $user->getUser();

            Session::useBag($session, function (AttributeBagInterface $adminSession, SessionInterface $session) use ($pimcoreUser) {
                $session->migrate();
                $adminSession->set('user', $pimcoreUser);
            });
        }
    }
}
