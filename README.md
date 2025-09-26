# Keycloak SSO для Pimcore 11

[![Latest Stable Version](https://img.shields.io/packagist/v/iperson1337/pimcore-keycloak-bundle.svg)](https://packagist.org/packages/iperson1337/pimcore-keycloak-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/iperson1337/pimcore-keycloak-bundle.svg)](https://packagist.org/packages/iperson1337/pimcore-keycloak-bundle)
[![License](https://img.shields.io/packagist/l/iperson1337/pimcore-keycloak-bundle.svg)](https://github.com/iperson1337/pimcore-keycloak-bundle/blob/main/LICENSE)

Бандл для интеграции административного интерфейса Pimcore 11 с системой единого входа Keycloak SSO.

![Keycloak + Pimcore](docs/images/keycloak-pimcore.png)

## Возможности

- Аутентификация в административном интерфейсе Pimcore через Keycloak SSO
- Автоматическое создание пользователей Pimcore на основе данных из Keycloak
- Синхронизация данных пользователя при каждом логине
- Поддержка Single Logout (выход одновременно из Pimcore и Keycloak)
- Соответствие ролей Keycloak и Pimcore
- Управление аккаунтом Keycloak из интерфейса Pimcore

## Требования

- Pimcore 11
- Symfony 6.4
- PHP 8.1 или выше
- Настроенный сервер Keycloak

## Установка

### 1. Установка пакета через Composer

```bash
composer require iperson1337/pimcore-keycloak-bundle
```

2. **Включите бандл в `config/bundles.php`**

```php
return [
    // ...
    Iperson1337\PimcoreKeycloakBundle\PimcoreKeycloakBundle::class => ['all' => true],
    // ...
];
```

3. Запуск инсталлятора

```bash
bin/console pimcore:bundle:install PimcoreKeycloakBundle
```

4. **Добавьте переменные окружения в `.env` файл**

```
###> iperson1337/pimcore-keycloak-bundle ###
KEYCLOAK_CLIENT_ID=pimcore-admin
KEYCLOAK_CLIENT_SECRET=your-client-secret
KEYCLOAK_SERVER_BASE_URL=https://keycloak.example.com/auth
KEYCLOAK_SERVER_PUBLIC_BASE_URL=https://keycloak.example.com/auth
KEYCLOAK_SERVER_PRIVATE_BASE_URL=https://keycloak.example.com/auth
KEYCLOAK_REALM=your-realm
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles
###< iperson1337/pimcore-keycloak-bundle ###
```

5. **Настройте security.yaml**

```yaml
# config/packages/security.yaml
security:
    enable_authenticator_manager: true

    providers:
        # Стандартный провайдер Pimcore
        pimcore_admin:
            id: Pimcore\Security\User\UserProvider

    firewalls:
        # Общедоступные ресурсы, не требующие аутентификации
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        # Публичные API, не требующие аутентификации
        public_api:
            pattern: ^/api/public
            security: false

        #pimcore_admin: '%pimcore_admin_bundle.firewall_settings%'

        # Pimcore Admin интерфейс с аутентификацией через Keycloak
        pimcore_admin:
            pattern: ^/admin
            provider: pimcore_admin
            custom_authenticators:
                - Pimcore\Bundle\AdminBundle\Security\Authenticator\AdminTokenAuthenticator
                - Iperson1337\PimcoreKeycloakBundle\Security\Authenticator\KeycloakAuthenticator
            form_login:
                login_path: pimcore_admin_login
                check_path: pimcore_admin_login_check
                default_target_path: pimcore_admin_index
                username_parameter: username
                password_parameter: password
            logout:
                path: pimcore_admin_logout
                target: pimcore_admin_login
            entry_point: form_login

    access_control:
        # Pimcore admin ACl  // DO NOT CHANGE!
        - { path: ^/admin/settings/display-custom-logo, roles: PUBLIC_ACCESS }
        - { path: ^/admin/login/2fa-verify, roles: IS_AUTHENTICATED_2FA_IN_PROGRESS }
        - { path: ^/admin/login/2fa-setup, roles: ROLE_PIMCORE_USER }
        - { path: ^/admin/login/2fa, roles: IS_AUTHENTICATED_2FA_IN_PROGRESS }
        - { path: ^/admin/login$, roles: PUBLIC_ACCESS }
        - { path: ^/admin/login/(login|lostpassword|deeplink|csrf-token)$, roles: PUBLIC_ACCESS }
        - { path: ^/admin/login/2fa, roles: IS_AUTHENTICATED_2FA_IN_PROGRESS }

        # Маршруты Keycloak
        - { path: ^/admin/keycloak, roles: PUBLIC_ACCESS }

        # Защищенный административный интерфейс
        - { path: ^/admin, roles: ROLE_PIMCORE_USER }
        - { path: ^/asset/webdav, roles: ROLE_PIMCORE_USER }

    role_hierarchy:
        # Pimcore admin  // DO NOT CHANGE!
        ROLE_PIMCORE_ADMIN: [ROLE_PIMCORE_USER]

```

6. **Добавьте маршруты в конфигурацию**

```yaml
# config/routes/iperson1337_pimcore_keycloak.yaml
iperson1337_pimcore_keycloak:
    resource: "@PimcoreKeycloakBundle/config/routing.yaml"
    prefix: /

```

7. **Обновите cookie_samesite для поддержки OAuth2**

```yaml
# config/packages/framework.yaml
framework:
    session:
        cookie_samesite: 'lax'  # Требуется для работы OAuth2 редиректов
```

8. **Очистите кэш**

```bash
bin/console cache:clear
```

## Конфигурация

### Переменные окружения

| Переменная | Описание | Обязательная | Пример |
|------------|----------|--------------|---------|
| `KEYCLOAK_CLIENT_ID` | ID клиента в Keycloak | Да | `pimcore-admin` |
| `KEYCLOAK_CLIENT_SECRET` | Секрет клиента | Да | `your-client-secret` |
| `KEYCLOAK_SERVER_BASE_URL` | Базовый URL сервера Keycloak | Да | `https://keycloak.example.com/auth` |
| `KEYCLOAK_SERVER_PUBLIC_BASE_URL` | Публичный URL для внешнего доступа | Нет | `https://keycloak.example.com/auth` |
| `KEYCLOAK_SERVER_PRIVATE_BASE_URL` | Приватный URL для внутреннего доступа | Нет | `https://keycloak.internal.com/auth` |
| `KEYCLOAK_REALM` | Realm в Keycloak | Да | `your-realm` |
| `KEYCLOAK_DEFAULT_SCOPES` | Scopes для OAuth2 запросов | Нет | `openid,profile,email,roles` |
| `KEYCLOAK_TARGET_ROUTE_NAME` | Маршрут для редиректа после входа | Нет | `pimcore_admin_index` |

### Конфигурация бандла

Вы можете настроить бандл через файл `config/packages/iperson1337_pimcore_keycloak.yaml`:

```yaml
iperson1337_pimcore_keycloak:
    # Маршрут для редиректа после успешного входа
    default_target_route_name: '%env(KEYCLOAK_TARGET_ROUTE_NAME)%'
    
    # Класс пользователя Pimcore
    admin_user_class: 'Pimcore\Model\User'
    
    # Язык по умолчанию для новых пользователей
    default_language: 'ru'
    
    # Автоматически создавать пользователей при первом входе
    auto_create_users: true
    
    # Синхронизировать данные пользователя при каждом входе
    sync_user_data: true
    
    # Настройки подключения к Keycloak
    keycloak:
        client_id: '%env(resolve:KEYCLOAK_CLIENT_ID)%'
        client_secret: '%env(KEYCLOAK_CLIENT_SECRET)%'
        server_url: '%env(KEYCLOAK_SERVER_BASE_URL)%'
        server_public_url: '%env(KEYCLOAK_SERVER_PUBLIC_BASE_URL)%'
        server_private_url: '%env(KEYCLOAK_SERVER_PRIVATE_BASE_URL)%'
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: false
        default_scopes: '%env(KEYCLOAK_DEFAULT_SCOPES)%'
    
    # Маппинг полей пользователя
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

### Настройка Scopes

Параметр `default_scopes` определяет, какие данные пользователя будут запрашиваться у Keycloak. Вы можете указать scopes в двух форматах:

**Строка (рекомендуется):**
```bash
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles
```

**Массив в YAML:**
```yaml
default_scopes:
    - openid
    - profile
    - email
    - roles
    - custom_scope
```

**Доступные стандартные scopes:**
- `openid` - обязательный для OpenID Connect
- `profile` - основная информация профиля (имя, фамилия)
- `email` - email адрес
- `roles` - роли пользователя
- `address` - адрес пользователя
- `phone` - телефон

## Настройка Keycloak

1. Создайте новый клиент в Keycloak
2. Установите Client ID как `pimcore-admin` (или то, что указано в конфигурации)
3. Установите Access Type как `confidential`
4. Включите "Standard Flow" и "Direct Access Grants"
5. Установите Valid Redirect URIs как `https://your-pimcore-domain.com/auth/keycloak/check`
6. После сохранения перейдите на вкладку Credentials для получения Client Secret
7. Настройте Client Scopes в соответствии с вашими `default_scopes`

## Маппинг пользователей

Когда пользователь впервые входит через Keycloak, соответствующий пользователь Pimcore создается автоматически (если включена опция `auto_create_users`) со следующим маппингом:

- Keycloak preferred_username → Pimcore username
- Keycloak email → Pimcore email
- Keycloak given_name → Pimcore firstname
- Keycloak family_name → Pimcore lastname

## Маппинг ролей

Бандл автоматически преобразует роли Keycloak в роли Symfony Security. Например:

- Роль `admin` в Keycloak преобразуется в `ROLE_ADMIN` и `ROLE_PIMCORE_ADMIN` в Symfony и устанавливает флаг admin для пользователя Pimcore
- Другие роли Keycloak преобразуются с префиксом `ROLE_`

## Расширение функциональности

Для настройки более сложной логики маппинга ролей вы можете расширить сервис `UserMapperService`:

```php
<?php

namespace App\Service;

use Iperson1337\PimcoreKeycloakBundle\Provider\KeycloakResourceOwner;
use Iperson1337\PimcoreKeycloakBundle\Service\UserMapperService;
use Pimcore\Model\User;
use Psr\Log\LoggerInterface;

readonly class KeycloakUserMapperService extends UserMapperService
{
    public function __construct(
        protected LoggerInterface $logger,
        protected string          $defaultLanguage,
    ) {
        parent::__construct($logger, $defaultLanguage);
    }
    
    protected function syncUserRoles(User $user, array $keycloakRoles): void
    {
        parent::syncUserRoles($user, $keycloakRoles);

        // Ваша собственная логика маппинга ролей
        if (in_array('content-editor', $keycloakRoles, true)) {
            // Назначаем пользователю соответствующие роли Pimcore
            $user->setRoles(['contentEditor', 'reviewer']);
        }
    }

    protected function assignDefaultRolesToUser(User $user, KeycloakResourceOwner $resourceOwner): void
    {
        foreach ($resourceOwner->getResourceRoles() as $keycloakRole) {
            if (strtolower($keycloakRole) === 'admin' && !$user->isAdmin()) {
                $user->setAdmin(true);
                $this->logger->info('Пользователь установлен как админ на основе роли Keycloak');
            }

            // Здесь можно добавить дополнительную логику для других ролей
        }
    }
}
```

Затем зарегистрируйте ваш сервис в `services.yaml`:

```yaml
Iperson1337\PimcoreKeycloakBundle\Service\UserMapperService:
    class: App\Service\KeycloakUserMapperService
    arguments:
        $logger: '@monolog.logger.keycloak'
        $defaultLanguage: '%iperson1337_pimcore_keycloak.default_language%'
```

## Поддержка Single Logout

Бандл поддерживает Single Logout - когда пользователь выходит из Pimcore, он также выходит из Keycloak. Для этого:

1. Убедитесь, что ваш logout route использует контроллер `iperson1337_pimcore_keycloak_auth_logout`
2. Или настройте ваш собственный logout handler, который будет включать вызов Keycloak endpoints

## Логирование

Бандл использует отдельный канал логирования `keycloak`. Вы можете настроить его в `monolog.yaml`:

```yaml
monolog:
    handlers:
        keycloak:
            type: rotating_file
            path: "%kernel.logs_dir%/keycloak.log"
            level: debug
            channels: [keycloak]
            max_files: 10
```

## Безопасность и рекомендации

1. Всегда включайте SSL-верификацию в продакшн-окружении (`ssl_verification: true`)
2. Регулярно проверяйте и обновляйте клиентские секреты Keycloak
3. Используйте HTTPS для всех взаимодействий между Pimcore и Keycloak
4. Ограничьте список разрешенных редиректов в настройках клиента Keycloak

## Устранение неполадок

### Частые проблемы

**Ошибка: "Unrecognized option 'default_scopes'"**
- Убедитесь, что вы используете последнюю версию бандла
- Очистите кеш: `bin/console cache:clear`
- Проверьте, что файл `Configuration.php` содержит определение `default_scopes`

**Ошибка: "Invalid scopes"**
- Проверьте, что указанные scopes существуют в Keycloak
- Убедитесь, что scopes правильно настроены в клиенте Keycloak
- Проверьте формат переменной `KEYCLOAK_DEFAULT_SCOPES` (должна быть строка через запятую)

**Проблемы с аутентификацией**
- Проверьте правильность `KEYCLOAK_CLIENT_ID` и `KEYCLOAK_CLIENT_SECRET`
- Убедитесь, что URL редиректа совпадает с настройками в Keycloak
- Проверьте, что realm существует и доступен

**Проблемы с созданием пользователей**
- Убедитесь, что `auto_create_users: true` в конфигурации
- Проверьте маппинг полей в `user_mapping`
- Убедитесь, что у пользователя есть необходимые поля в Keycloak

### Отладка

1. **Проверьте конфигурацию:**
   ```bash
   bin/console debug:config iperson1337_pimcore_keycloak
   ```

2. **Проверьте переменные окружения:**
   ```bash
   bin/console debug:container --env-vars
   ```

3. **Проверьте логи:**
   ```bash
   tail -f var/log/keycloak.log
   ```

4. **Проверьте подключение к Keycloak:**
   ```bash
   curl -k https://your-keycloak-server/auth/realms/your-realm/.well-known/openid_configuration
   ```

### Дополнительные проверки

При возникновении проблем проверьте:

1. Правильность настроек клиента в Keycloak
2. Корректность URL-адресов и разрешенных редиректов
3. Настройки scope и mappers в Keycloak
4. Логи в файле keycloak.log
5. Настройки SSL-сертификатов (`ssl_verification`)
6. Доступность сервера Keycloak из Pimcore

Дополнительные руководства по устранению неполадок доступны в документации в папке `docs/`.

## Лицензия
MIT
