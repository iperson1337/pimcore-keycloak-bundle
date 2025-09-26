# Руководство по конфигурации Pimcore Keycloak Bundle

Это подробное руководство по настройке и конфигурации Pimcore Keycloak Bundle.

## Содержание

1. [Переменные окружения](#переменные-окружения)
2. [Конфигурация бандла](#конфигурация-бандла)
3. [Настройка Scopes](#настройка-scopes)
4. [Маппинг пользователей](#маппинг-пользователей)
5. [Безопасность](#безопасность)
6. [Примеры конфигурации](#примеры-конфигурации)

## Переменные окружения

### Обязательные переменные

| Переменная | Описание | Пример |
|------------|----------|---------|
| `KEYCLOAK_CLIENT_ID` | ID клиента в Keycloak | `pimcore-admin` |
| `KEYCLOAK_CLIENT_SECRET` | Секрет клиента | `your-client-secret` |
| `KEYCLOAK_SERVER_BASE_URL` | Базовый URL сервера Keycloak | `https://keycloak.example.com/auth` |
| `KEYCLOAK_REALM` | Realm в Keycloak | `your-realm` |

### Дополнительные переменные

| Переменная | Описание | По умолчанию | Пример |
|------------|----------|--------------|---------|
| `KEYCLOAK_SERVER_PUBLIC_BASE_URL` | Публичный URL для внешнего доступа | `KEYCLOAK_SERVER_BASE_URL` | `https://keycloak.example.com/auth` |
| `KEYCLOAK_SERVER_PRIVATE_BASE_URL` | Приватный URL для внутреннего доступа | `KEYCLOAK_SERVER_BASE_URL` | `https://keycloak.internal.com/auth` |
| `KEYCLOAK_DEFAULT_SCOPES` | Scopes для OAuth2 запросов | `openid,profile,email` | `openid,profile,email,roles` |
| `KEYCLOAK_TARGET_ROUTE_NAME` | Маршрут для редиректа после входа | `pimcore_admin_index` | `pimcore_admin_index` |

### Пример .env файла

```bash
###> iperson1337/pimcore-keycloak-bundle ###
KEYCLOAK_CLIENT_ID=pimcore-admin
KEYCLOAK_CLIENT_SECRET=your-client-secret-here
KEYCLOAK_SERVER_BASE_URL=https://keycloak.example.com/auth
KEYCLOAK_SERVER_PUBLIC_BASE_URL=https://keycloak.example.com/auth
KEYCLOAK_SERVER_PRIVATE_BASE_URL=https://keycloak.internal.com/auth
KEYCLOAK_REALM=your-realm
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles
KEYCLOAK_TARGET_ROUTE_NAME=pimcore_admin_index
###< iperson1337/pimcore-keycloak-bundle ###
```

## Конфигурация бандла

### Основная конфигурация

Создайте или отредактируйте файл `config/packages/iperson1337_pimcore_keycloak.yaml`:

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
        ssl_verification: true
        default_scopes: '%env(KEYCLOAK_DEFAULT_SCOPES)%'
    
    # Маппинг полей пользователя
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

### Описание параметров

#### Основные настройки

- **`default_target_route_name`** - маршрут, на который будет перенаправлен пользователь после успешного входа
- **`admin_user_class`** - класс пользователя Pimcore (обычно не изменяется)
- **`default_language`** - язык по умолчанию для новых пользователей
- **`auto_create_users`** - автоматически создавать пользователей Pimcore при первом входе через Keycloak
- **`sync_user_data`** - синхронизировать данные пользователя при каждом входе

#### Настройки Keycloak

- **`client_id`** - ID клиента в Keycloak
- **`client_secret`** - секрет клиента
- **`server_url`** - базовый URL сервера Keycloak
- **`server_public_url`** - публичный URL (для внешнего доступа)
- **`server_private_url`** - приватный URL (для внутреннего доступа)
- **`realm`** - realm в Keycloak
- **`ssl_verification`** - проверять SSL сертификат (рекомендуется `true` для продакшна)
- **`default_scopes`** - scopes для OAuth2 запросов

#### Маппинг пользователей

- **`username`** - поле Keycloak для имени пользователя
- **`email`** - поле Keycloak для email
- **`firstname`** - поле Keycloak для имени
- **`lastname`** - поле Keycloak для фамилии

## Настройка Scopes

### Что такое Scopes

Scopes определяют, какие данные пользователя будут запрашиваться у Keycloak. Это важная часть OAuth2/OpenID Connect протокола.

### Форматы конфигурации

#### Строка (рекомендуется)

```bash
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles
```

#### Массив в YAML

```yaml
default_scopes:
    - openid
    - profile
    - email
    - roles
    - custom_scope
```

### Стандартные Scopes

| Scope | Описание | Обязательный |
|-------|----------|--------------|
| `openid` | Обязательный для OpenID Connect | Да |
| `profile` | Основная информация профиля (имя, фамилия) | Нет |
| `email` | Email адрес | Нет |
| `roles` | Роли пользователя | Нет |
| `address` | Адрес пользователя | Нет |
| `phone` | Телефон | Нет |

### Кастомные Scopes

Вы можете создавать собственные scopes в Keycloak:

1. Перейдите в **Client Scopes** в админке Keycloak
2. Создайте новый scope
3. Настройте mappers для этого scope
4. Добавьте scope в ваш клиент
5. Укажите scope в `KEYCLOAK_DEFAULT_SCOPES`

### Примеры конфигурации Scopes

#### Минимальная конфигурация
```bash
KEYCLOAK_DEFAULT_SCOPES=openid
```

#### Стандартная конфигурация
```bash
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email
```

#### Расширенная конфигурация
```bash
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles,address,phone
```

#### Конфигурация с кастомными scopes
```bash
KEYCLOAK_DEFAULT_SCOPES=openid,profile,email,roles,company_info,department
```

## Маппинг пользователей

### Стандартный маппинг

По умолчанию используется следующий маппинг полей:

| Поле Pimcore | Поле Keycloak | Описание |
|--------------|---------------|----------|
| `username` | `preferred_username` | Имя пользователя |
| `email` | `email` | Email адрес |
| `firstname` | `given_name` | Имя |
| `lastname` | `family_name` | Фамилия |

### Настройка кастомного маппинга

Вы можете изменить маппинг полей в конфигурации:

```yaml
user_mapping:
    username: 'username'  # Использовать username вместо preferred_username
    email: 'email'
    firstname: 'first_name'  # Кастомное поле
    lastname: 'last_name'    # Кастомное поле
```

### Требования к полям

- **`username`** - должно быть уникальным в Pimcore
- **`email`** - должно быть валидным email адресом
- **`firstname`** и **`lastname`** - могут быть пустыми

## Безопасность

### SSL/TLS

Всегда используйте HTTPS в продакшн-окружении:

```yaml
keycloak:
    ssl_verification: true  # Включить проверку SSL
```

### Секреты

- Никогда не коммитьте секреты в репозиторий
- Используйте переменные окружения для всех чувствительных данных
- Регулярно обновляйте `KEYCLOAK_CLIENT_SECRET`

### URL-адреса

- Ограничьте список разрешенных редиректов в настройках клиента Keycloak
- Используйте точные URL-адреса без wildcards в продакшне
- Настройте правильные CORS политики

### Роли и права доступа

- Минимизируйте количество scopes до необходимого минимума
- Регулярно проверяйте назначенные роли пользователей
- Используйте принцип минимальных привилегий

## Примеры конфигурации

### Разработка

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
        ssl_verification: false  # Отключено для разработки
        default_scopes: 'openid,profile,email'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

### Продакшн

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
        ssl_verification: true  # Включено для продакшна
        default_scopes: '%env(KEYCLOAK_DEFAULT_SCOPES)%'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

### Высокая доступность

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
        server_public_url: '%env(KEYCLOAK_SERVER_PUBLIC_BASE_URL)%'  # Внешний балансировщик
        server_private_url: '%env(KEYCLOAK_SERVER_PRIVATE_BASE_URL)%'  # Внутренний балансировщик
        realm: '%env(KEYCLOAK_REALM)%'
        ssl_verification: true
        default_scopes: 'openid,profile,email,roles'
    
    user_mapping:
        username: 'preferred_username'
        email: 'email'
        firstname: 'given_name'
        lastname: 'family_name'
```

## Проверка конфигурации

### Команды для проверки

```bash
# Проверить конфигурацию бандла
bin/console debug:config iperson1337_pimcore_keycloak

# Проверить переменные окружения
bin/console debug:container --env-vars

# Проверить подключение к Keycloak
curl -k https://your-keycloak-server/auth/realms/your-realm/.well-known/openid_configuration

# Очистить кеш
bin/console cache:clear
```

### Логирование

Настройте логирование для отладки:

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        keycloak:
            type: rotating_file
            path: "%kernel.logs_dir%/keycloak.log"
            level: debug
            channels: [keycloak]
            max_files: 10
```

## Заключение

Правильная конфигурация Pimcore Keycloak Bundle критически важна для безопасной и надежной работы системы. Следуйте рекомендациям по безопасности и регулярно проверяйте настройки.

Для получения дополнительной помощи обратитесь к:
- [Руководству по устранению неполадок](keycloak-help-guide-troubleshooting.md)
- [Документации Keycloak](https://www.keycloak.org/documentation)
- [Документации Symfony Security](https://symfony.com/doc/current/security.html)
