<?php

namespace Iperson1337\PimcoreKeycloakBundle\Security\User;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;
use Iperson1337\PimcoreKeycloakBundle\Provider\KeycloakResourceOwner;
use Iperson1337\PimcoreKeycloakBundle\Service\UserMapperService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

readonly class KeycloakUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserMapperService $userMapperService,
        private LoggerInterface   $logger,
        private bool              $autoCreateUsers,
        private bool              $syncUserData
    ) {
    }

    /**
     * Загружает пользователя по его имени пользователя
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $pimcoreUser = User::getByName($identifier);

        if (!$pimcoreUser instanceof User) {
            $this->logger->warning('Pimcore user not found: {username}', ['username' => $identifier]);
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        $keycloakUser = new KeycloakUser($identifier);
        $keycloakUser->setPimcoreUser($pimcoreUser);

        return $keycloakUser;
    }

    /**
     * Создает или обновляет пользователя на основе данных из Keycloak
     */
    public function loadUserByResourceOwner(
        ResourceOwnerInterface $resourceOwner,
        AccessToken $accessToken
    ): KeycloakUser {
        if (!$resourceOwner instanceof KeycloakResourceOwner) {
            throw new \InvalidArgumentException(sprintf(
                'Expected an instance of %s, got "%s".',
                KeycloakResourceOwner::class,
                get_class($resourceOwner)
            ));
        }

        $username = $resourceOwner->getPreferredUsername();
        $email = $resourceOwner->getEmail();

        // Ищем пользователя по имени
        $pimcoreUser = User::getByName($username);

        // Если пользователь не найден по имени, ищем по email
        if (!$pimcoreUser instanceof User && $email) {
            $pimcoreUser = $this->userMapperService->findPimcoreUserByEmail($email);
        }

        // Если пользователь все еще не найден, и включено автосоздание
        if (!$pimcoreUser instanceof User && $this->autoCreateUsers) {
            $pimcoreUser = $this->userMapperService->createPimcoreUserFromKeycloak($resourceOwner);
        }
        // Если пользователь не найден и автосоздание отключено
        elseif (!$pimcoreUser instanceof User && !$this->autoCreateUsers) {
            $this->logger->error('User not found and auto-creation is disabled: {username}', [
                'username' => $username
            ]);
            throw new UserNotFoundException(sprintf(
                'User "%s" not found and auto-creation is disabled.',
                $username
            ));
        }
        // Если пользователь существует и включена синхронизация данных
        elseif ($pimcoreUser instanceof User && $this->syncUserData) {
            $this->userMapperService->syncPimcoreUserWithKeycloak($pimcoreUser, $resourceOwner);
        }

        // Создаем KeycloakUser на основе Pimcore пользователя
        $keycloakUser = new KeycloakUser($username);
        $keycloakUser->setPimcoreUser($pimcoreUser);
        $keycloakUser->setKeycloakId($resourceOwner->getId());
        $keycloakUser->setEmail($resourceOwner->getEmail());
        $keycloakUser->setFirstName($resourceOwner->getFirstName());
        $keycloakUser->setLastName($resourceOwner->getLastName());
        $keycloakUser->setKeycloakRoles($resourceOwner->getResourceRoles());

        // Устанавливаем роли для пользователя
        $roles = $this->userMapperService->mapPimcoreRolesToKeycloakRoles($pimcoreUser, $resourceOwner->getResourceRoles());
        $keycloakUser->setRoles($roles);

        return $keycloakUser;
    }

    /**
     * Обновляет пользователя
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof KeycloakUser) {
            throw new UnsupportedUserException(sprintf(
                'Expected an instance of %s, got "%s".',
                KeycloakUser::class,
                get_class($user)
            ));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * Проверяет поддержку класса
     */
    public function supportsClass(string $class): bool
    {
        return KeycloakUser::class === $class || is_subclass_of($class, KeycloakUser::class);
    }
}
