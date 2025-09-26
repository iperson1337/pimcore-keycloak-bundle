# Примеры использования Pimcore Keycloak Bundle

Этот документ содержит практические примеры использования Pimcore Keycloak Bundle в различных сценариях.

## Содержание

1. [Базовые примеры](#базовые-примеры)
2. [Расширенные конфигурации](#расширенные-конфигурации)
3. [Кастомные маппинги](#кастомные-маппинги)
4. [Интеграция с корпоративными системами](#интеграция-с-корпоративными-системами)
5. [Многоязычные системы](#многоязычные-системы)

## Базовые примеры

### Простая настройка для разработки

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: false
        default_scopes: 'openid,profile,email'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

```bash
# .env
KEYCLOAK_CLIENT_ID=pimcore-dev
KEYCLOAK_CLIENT_SECRET=dev-secret
KEYCLOAK_SERVER_BASE_URL=http://localhost:8080/auth
KEYCLOAK_REALM=development
```

### Продакшн конфигурация

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'ru'
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        server_public_url: '%env(KEYCLOAK_SERVER_PUBLIC_BASE_URL)%'
        server_private_url: '%env(KEYCLOAK_SERVER_PRIVATE_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: '%env(KEYCLOAK_DEFAULT_SCOPES)%'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

```bash
# .env.prod
KEYCLOAK_CLIENT_ID=pimcore-prod
KEYCLOAK_CLIENT_SECRET=${KEYCLOAK_CLIENT_SECRET}
KEYCLOAK_SERVER_BASE_URL=https://keycloak.company.com/auth
KEYCLOAK_SERVER_PUBLIC_BASE_URL=https://keycloak.company.com/auth
KEYCLOAK_SERVER_PRIVATE_BASE_URL=https://keycloak.internal.company.com/auth
KEYCLOAK_REALM=production
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles
```

## Расширенные конфигурации

### Конфигурация с кастомными scopes

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: '%env(KEYCLOAK_DEFAULT_SCOPES)%'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

```bash
# .env
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles,company_info,department,position
```

### Конфигурация с отключенным автоматическим созданием пользователей

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: false  # Отключено
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

### Конфигурация с отключенной синхронизацией

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: true
    sync_user_data: false  # Отключено
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

## Кастомные маппинги

### Маппинг с кастомными полями Keycloak

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email,roles'
    
    user_mapping:
        username: 'username'        # Используем username вместо preferred_username
        email: 'email'
        firstname: 'first_name'     # Кастомное поле
        lastname: 'last_name'       # Кастомное поле
```

### Маппинг для корпоративных систем

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email,roles,employee_id,department'
    
    user_mapping:
        username: 'employee_id'     # Используем employee_id как username
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

## Интеграция с корпоративными системами

### Active Directory интеграция

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email,roles,ad_groups'
    
    user_mapping:
        username: 'sAMAccountName'  # AD поле
        email: 'email'
        firstname: 'givenName'      # AD поле
        lastname: 'sn'              # AD поле
```

### LDAP интеграция

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email,roles,ldap_groups'
    
    user_mapping:
        username: 'uid'             # LDAP поле
        email: 'mail'               # LDAP поле
        firstname: 'cn'             # LDAP поле
        lastname: 'sn'              # LDAP поле
```

## Многоязычные системы

### Конфигурация для многоязычного сайта

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'  # Язык по умолчанию
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email,roles,locale'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

### Кастомный UserMapperService для многоязычности

```php
<?php

namespace App\Service;

use Iperson1337\PimcoreKeycloakBundle\Provider\KeycloakResourceOwner;
use Iperson1337\PimcoreKeycloakBundle\Service\UserMapperService;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;

readonly class MultilingualKeycloakUserMapperService extends UserMapperService
{
    public function __construct(
        protected LoggerInterface $logger,
        protected string $defaultLanguage,
    ) {
        parent::__construct($logger, $defaultLanguage);
    }
    
    protected function syncUserData(User $user, KeycloakResourceOwner $resourceOwner): void
    {
        parent::syncUserData($user, $resourceOwner);
        
        // Синхронизируем язык пользователя из Keycloak
        $locale = $resourceOwner->getResourceOwnerAttribute('locale');
        if ($locale) {
            $user->setLanguage($locale);
            $this->logger->info('Язык пользователя обновлен', ['locale' => $locale]);
        }
    }
}
```

## Высокая доступность

### Конфигурация с балансировщиком нагрузки

```yaml
# config/packages/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    default_target_route_name: 'pimcore_admin_index'
    admin_user_class: 'Pimcore\Model\User'
    default_language: 'en'
    auto_create_users: true
    sync_user_data: true
    
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        server_public_url: '%env(KEYCLOAK_SERVER_PUBLIC_BASE_URL)%'    # Внешний LB
        server_private_url: '%env(KEYCLOAK_SERVER_PRIVATE_BASE_URL)%'  # Внутренний LB
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email,roles'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

```bash
# .env
KEYCLOAK_SERVER_BASE_URL=https://keycloak.company.com/auth
KEYCLOAK_SERVER_PUBLIC_BASE_URL=https://keycloak.company.com/auth
KEYCLOAK_SERVER_PRIVATE_BASE_URL=https://keycloak-internal.company.com/auth
```

## Docker окружение

### Docker Compose конфигурация

```yaml
# docker-compose.yml
version: '3.8'

services:
  pimcore:
    build: .
    environment:
      - KEYCLOAK_CLIENT_ID=pimcore-admin
      - KEYCLOAK_CLIENT_SECRET=${KEYCLOAK_CLIENT_SECRET}
      - KEYCLOAK_SERVER_BASE_URL=http://keycloak:8080/auth
      - KEYCLOAK_REALM=master
      - KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles
    depends_on:
      - keycloak

  keycloak:
    image: quay.io/keycloak/keycloak:latest
    environment:
      - KEYCLOAK_ADMIN=admin
      - KEYCLOAK_ADMIN_PASSWORD=admin
    ports:
      - "8080:8080"
    command: start-dev
```

### Kubernetes конфигурация

```yaml
# k8s/configmap.yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: pimcore-keycloak-config
data:
  KEYCLOAK_CLIENT_ID: "pimcore-admin"
  KEYCLOAK_SERVER_BASE_URL: "https://keycloak.company.com/auth"
  KEYCLOAK_REALM: "production"
  KEYCLOAK_DEFAULT_SCOPES: "openid,profile,email,roles"

---
apiVersion: v1
kind: Secret
metadata:
  name: pimcore-keycloak-secrets
type: Opaque
data:
  KEYCLOAK_CLIENT_SECRET: <base64-encoded-secret>
```

## Мониторинг и логирование

### Расширенное логирование

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        keycloak:
            type: rotating_file
            path: "%kernel.logs_dir%/keycloak.log"
            level: debug
            channels: [keycloak]
            max_files: 30
            formatter: monolog.formatter.json
        
        keycloak_error:
            type: rotating_file
            path: "%kernel.logs_dir%/keycloak_error.log"
            level: error
            channels: [keycloak]
            max_files: 10
```

### Кастомный EventListener для мониторинга

```php
<?php

namespace App\EventListener;

use Iperson1337\PimcoreKeycloakBundle\Event\KeycloakUserCreatedEvent;
use Iperson1337\PimcoreKeycloakBundle\Event\KeycloakUserLoggedInEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class KeycloakMonitoringListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    public function onUserCreated(KeycloakUserCreatedEvent $event): void
    {
        $this->logger->info('Новый пользователь создан через Keycloak', [
            'user_id' => $event->getUser()->getId(),
            'username' => $event->getUser()->getName(),
            'keycloak_id' => $event->getResourceOwner()->getId(),
        ]);
    }
    
    public function onUserLoggedIn(KeycloakUserLoggedInEvent $event): void
    {
        $this->logger->info('Пользователь вошел через Keycloak', [
            'user_id' => $event->getUser()->getId(),
            'username' => $event->getUser()->getName(),
            'login_time' => new \DateTime(),
        ]);
    }
}
```

## Заключение

Эти примеры показывают различные способы использования Pimcore Keycloak Bundle в разных сценариях. Выберите подходящий пример для вашего случая использования и адаптируйте его под ваши потребности.

Для получения дополнительной информации обратитесь к:
- [Руководству по конфигурации](configuration-guide.md)
- [Документации по устранению неполадок](keycloak-help-guide-troubleshooting.md)
- [Основному README](../README.md)
