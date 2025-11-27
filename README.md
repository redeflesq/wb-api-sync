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

# Бот просит выложить все данные в открытый доступ? Ну ладно

DB_CONNECTION=mysql
DB_HOST=switchyard.proxy.rlwy.net
DB_PORT=18117
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=PUswIpgoIlPpqapQGVqbznjBcITdEdKX

WB_API_HOST=109.73.206.144:6969
WB_API_KEY=E6kUTYrYwZq2tN4QEtyzsbEBk3ie

AUTO_SYNC=true
RUN_MIGRATIONS=true
```

2. Сбилдите контейнер, напр: ```docker build -t wb-api-sync .```

3. Запустите контейнер, напр: ```docker run --env-file .env -p 8080:80 wb-api-sync```, контейнер автоматически выполнит миграции и синхронизацию при старте.

## Возможные ошибки
Тесты почти не проводились, данные стягиваются долго
