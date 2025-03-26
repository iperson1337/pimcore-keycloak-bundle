<?php

namespace Iperson1337\PimcoreKeycloakBundle\Service;

use Pimcore\Model\User;
use Pimcore\Model\User\Listing as UserListing;
use Pimcore\Model\User\Role;
use Psr\Log\LoggerInterface;
use Iperson1337\PimcoreKeycloakBundle\Provider\KeycloakResourceOwner;

readonly class UserMapperService
{
    public function __construct(
        private LoggerInterface $logger,
        private string $defaultLanguage,
    ) {
    }

    /**
     * Находит пользователя Pimcore по email
     */
    public function findPimcoreUserByEmail(string $email): ?User
    {
        try {
            if (empty($email)) {
                $this->logger->warning('Попытка поиска пользователя с пустым email');
                return null;
            }

            $listing = new UserListing();
            $listing->setCondition("email = ?", [$email]);
            $users = $listing->load();

            return !empty($users) ? $users[0] : null;
        } catch (\Exception $e) {
            $this->logger->error('Ошибка поиска пользователя по email: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Создает нового пользователя Pimcore на основе данных Keycloak
     */
    public function createPimcoreUserFromKeycloak(KeycloakResourceOwner $resourceOwner): User
    {
        $this->logger->info('Создание нового пользователя Pimcore на основе данных Keycloak');

        $email = $resourceOwner->getEmail();
        if (empty($email)) {
            throw new \RuntimeException('Невозможно создать пользователя без email');
        }

        try {
            $user = new User();
            $user->setParentId(0);
            // Устанавливаем базовые данные пользователя
            $user->setUsername($resourceOwner->getPreferredUsername() ?: $email);
            $user->setEmail($email);
            $user->setFirstname($resourceOwner->getFirstName() ?? '');
            $user->setLastname($resourceOwner->getLastName() ?? '');
            $user->setActive(true);
            $user->setLanguage($this->defaultLanguage);

            // Генерируем случайный пароль, который не будет использоваться
            // для аутентификации, но требуется для создания пользователя
            $randomPassword = bin2hex(random_bytes(16));
            $user->setPassword(password_hash($randomPassword, PASSWORD_DEFAULT));

            // Назначаем базовые роли
            $this->assignDefaultRolesToUser($user);

            $user->save();

            return $user;
        } catch (\Exception $e) {
            $this->logger->error('Ошибка создания пользователя Pimcore: ' . $e->getMessage());
            throw new \RuntimeException('Не удалось создать пользователя Pimcore', 0, $e);
        }
    }

    /**
     * Синхронизирует данные пользователя Pimcore с данными из Keycloak
     */
    public function syncPimcoreUserWithKeycloak(User $user, KeycloakResourceOwner $resourceOwner): void
    {
        $this->logger->debug('Синхронизация данных пользователя Pimcore с Keycloak');

        try {
            $hasChanges = false;

            // Синхронизируем основные поля, если они изменились
            if ($resourceOwner->getFirstName() && $user->getFirstname() !== $resourceOwner->getFirstName()) {
                $user->setFirstname($resourceOwner->getFirstName());
                $hasChanges = true;
            }

            if ($resourceOwner->getLastName() && $user->getLastname() !== $resourceOwner->getLastName()) {
                $user->setLastname($resourceOwner->getLastName());
                $hasChanges = true;
            }

            // Имя пользователя предпочтительно не менять, так как оно может использоваться для входа
            // но при необходимости можно добавить и эту логику

            // Синхронизируем роли, если необходимо
            $keycloakRoles = $resourceOwner->getResourceRoles();
            if (!empty($keycloakRoles)) {
                $this->syncUserRoles($user, $keycloakRoles);
                $hasChanges = true;
            }

            // Сохраняем изменения, если они были
            if ($hasChanges) {
                $user->save();
                $this->logger->info('Данные пользователя успешно синхронизированы с Keycloak');
            }
        } catch (\Exception $e) {
            $this->logger->error('Ошибка синхронизации данных пользователя: ' . $e->getMessage());
            // Не выбрасываем исключение, чтобы не блокировать аутентификацию
        }
    }

    /**
     * Сопоставляет роли Pimcore с ролями Keycloak
     */
    public function mapPimcoreRolesToKeycloakRoles(User $pimcoreUser, array $keycloakRoles = []): array
    {
        $roles = ['ROLE_PIMCORE_USER'];

        // Добавляем роли из Keycloak с префиксом ROLE_
        foreach ($keycloakRoles as $keycloakRole) {
            $roles[] = 'ROLE_' . strtoupper($keycloakRole);
        }

        // Получаем роли пользователя из Pimcore
        $pimcoreRoles = $pimcoreUser->getRoles();
        foreach ($pimcoreRoles as $roleId) {
            try {
                $role = Role::getById($roleId);
                if ($role) {
                    $roles[] = 'ROLE_' . strtoupper($role->getName());
                }
            } catch (\Exception $e) {
                $this->logger->warning('Не удалось загрузить роль Pimcore: ' . $e->getMessage());
            }
        }

        // Если пользователь админ в Pimcore, добавляем соответствующую роль
        if ($pimcoreUser->isAdmin()) {
            $roles[] = 'ROLE_PIMCORE_ADMIN';
        }

        return array_unique($roles);
    }

    /**
     * Синхронизирует роли пользователя на основе ролей Keycloak
     */
    private function syncUserRoles(User $user, array $keycloakRoles): void
    {
        // Здесь можно реализовать логику синхронизации ролей между Keycloak и Pimcore
        // Например, если у пользователя в Keycloak есть роль 'admin', сделать его админом в Pimcore

        foreach ($keycloakRoles as $keycloakRole) {
            if (strtolower($keycloakRole) === 'admin' && !$user->isAdmin()) {
                $user->setAdmin(true);
                $this->logger->info('Пользователь установлен как админ на основе роли Keycloak');
            }

            // Здесь можно добавить дополнительную логику для других ролей
        }
    }

    /**
     * Назначает базовые роли пользователю при создании
     */
    private function assignDefaultRolesToUser(User $user): void
    {
        // Здесь можно назначить базовые роли, которые должны быть у всех пользователей
        // Например:
        // $user->setRoles(['roleId1', 'roleId2']);
    }
}
