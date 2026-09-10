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
        private bool $singleLogout = true,
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
        // При входе через Direct Grant браузерной сессии в Keycloak нет — редирект на
        // end-session только моргает страницей. Тогда выход отдаётся штатному logout
        // фаервола (сессия Pimcore уже очищена, редирект идёт на logout.target).
        if (!$this->singleLogout) {
            return;
        }

        $response = new RedirectResponse($this->router->generate($this->keycloakLogoutRoute));
        $event->setResponse($response);
    }
}
