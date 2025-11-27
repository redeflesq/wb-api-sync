FROM php:8.1-fpm

# Установка системных зависимостей
RUN apt-get update --fix-missing && apt-get install -y \
	netcat-openbsd \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Очистка кэша
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создание рабочей директории
WORKDIR /var/www/html

# Копирование файлов приложения
COPY . /var/www/html

# Установка зависимостей Composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Копирование конфигурации nginx
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Копирование конфигурации supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Установка прав доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Копирование entrypoint скрипта
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

# Экспорт портов
EXPOSE 80

# Запуск entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]