<?php

namespace Iperson1337\PimcoreKeycloakBundle\Controller;

use Iperson1337\PimcoreKeycloakBundle\Provider\KeycloakProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KeycloakController extends AbstractController
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly LoggerInterface $logger
    ) {
    }

    public function connectAction(): Response
    {
        try {
            return $this->clientRegistry->getClient('keycloak')->redirect();
        } catch (\Exception $e) {
            $this->logger->error('Error initiating Keycloak authentication: ' . $e->getMessage());
            return new Response('Ошибка подключения к Keycloak. Пожалуйста, попробуйте позже.', Response::HTTP_BAD_REQUEST);
        }
    }

    public function checkAction(Request $request): Response
    {
        $loginReferrer = null;
        if ($request->hasSession()) {
            $loginReferrer = $request->getSession()->remove('loginReferrer');
        }

        return $loginReferrer ? $this->redirect($loginReferrer) : $this->redirectToRoute('pimcore_admin_index');
    }

    public function logoutAction(Request $request): Response
    {
        /** @var KeycloakProvider $provider */
        $provider = $this->clientRegistry->getClient('keycloak')->getOAuth2Provider();

        return new RedirectResponse($provider->getLogoutUrl());
    }


    public function accountAction(): RedirectResponse
    {
        /** @var KeycloakProvider $provider */
        $provider = $this->clientRegistry->getClient('keycloak')->getOAuth2Provider();

        return $this->redirect($provider->getResourceOwnerManageAccountUrl());
    }
}
