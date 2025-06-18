<?php

namespace Iperson1337\PimcoreKeycloakBundle\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;

class KeycloakResourceOwner implements ResourceOwnerInterface
{
    protected array $response;

    protected AccessToken $token;

    public function __construct(array $response, AccessToken $token)
    {
        $this->response = $response;
        $this->token = $token;
    }

    public function getId(): ?string
    {
        return $this->response['sub'] ?? null;
    }

    public function getPreferredUsername(): ?string
    {
        return $this->response['preferred_username'] ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->response['email'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->response['name'] ?? null;
    }

    public function getFirstName(): ?string
    {
        return $this->response['given_name'] ?? null;
    }

    public function getLastName(): ?string
    {
        return $this->response['family_name'] ?? null;
    }

    public function getLocale(): ?string
    {
        return $this->response['locale'] ?? null;
    }

    public function getResourceAccess(): array
    {
        return $this->response['resource_access'] ?? [];
    }

    public function getResourceRoles(): array
    {
        $roles = [];

        // Проверяем наличие resource_access
        if (isset($this->response['resource_access']) && is_array($this->response['resource_access'])) {
            // Перебираем все ресурсы
            foreach ($this->response['resource_access'] as $resource => $data) {
                // Если в ресурсе есть роли
                if (isset($data['roles']) && is_array($data['roles'])) {
                    // Добавляем все роли в общий массив
                    foreach ($data['roles'] as $role) {
                        $roles[] = $role;
                    }
                }
            }
        }

        return $roles;
    }

    public function getRealmAccessRoles(): array
    {
        return $this->response['realm_access']['roles'] ?? [];
    }

    /**
     * Получает роли для конкретного ресурса
     */
    public function getResourceRolesForClient(string $clientId): array
    {
        if (isset($this->response['resource_access'][$clientId]['roles'])
            && is_array($this->response['resource_access'][$clientId]['roles'])) {
            return $this->response['resource_access'][$clientId]['roles'];
        }

        return [];
    }

    public function getRoles(): array
    {
        return $this->response['roles'] ?? [];
    }

    public function toArray(): array
    {
        return $this->response;
    }
}
