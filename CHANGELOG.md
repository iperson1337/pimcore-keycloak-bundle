# Changelog

Все заметные изменения в пакете будут документироваться в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
и придерживается [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-03-26

### Добавлено
- Первоначальный выпуск
- Аутентификация в административном интерфейсе Pimcore через Keycloak SSO
- Автоматическое создание пользователей Pimcore на основе данных из Keycloak
- Синхронизация данных пользователя при каждом логине
- Поддержка Single Logout (выход одновременно из Pimcore и Keycloak)
- Соответствие ролей Keycloak и Pimcore
- Управление аккаунтом Keycloak из интерфейса Pimcore
