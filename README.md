# WB Data Sync

Инструмент для автоматической синхронизации данных с API WB и хранения их в MySQL через Docker-контейнер.

## Описание

Контейнер автоматически:

1. Ждёт доступность базы данных.
2. Генерирует ключ приложения при необходимости.
3. Очищает и кэширует конфигурацию Laravel.
4. Выполняет миграции базы данных.
5. Настраивает права на папки `storage` и `bootstrap/cache`.
6. Запускает синхронизацию данных с WB API (`sales`, `orders`, `stocks`, `incomes`) за последние 30 дней (при включённой переменной `AUTO_SYNC`).
7. Запускает Supervisor для управления nginx, php-fpm и cron.

Данные сохраняются в таблицы MySQL с уникальным идентификатором, обеспечивается обновление существующих записей и вставка новых.  

## Требования

- Docker и Docker Compose
- PHP 8+
- Laravel 9+
- MySQL 8+

## Настройка

1. Создайте `.env` на основе `.env.example` и укажите:

```dotenv
DB_CONNECTION=mysql
DB_HOST=your_host
DB_PORT=your_port
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

WB_API_HOST=your_api_host
WB_API_KEY=your_api_key

AUTO_SYNC=true
RUN_MIGRATIONS=true
```

2. Сбилдите контейнер, напр: ```docker build -t wb-api-sync .```

3. Запустите контейнер, напр: ```docker run --env-file .env -p 8080:80 wb-api-sync```, контейнер автоматически выполнит миграции и синхронизацию при старте.

## Возможные ошибки
Тесты почти не проводились, данные стягиваются долго
