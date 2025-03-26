<?php

namespace Iperson1337\PimcoreKeycloakBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

readonly class LogoutListener implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private string $keycloakLogoutRoute = 'iperson1337_pimcore_keycloak_auth_logout'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $response = new RedirectResponse($this->router->generate($this->keycloakLogoutRoute));
        $event->setResponse($response);
    }
}
