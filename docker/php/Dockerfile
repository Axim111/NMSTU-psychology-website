FROM php:8.2-fpm-alpine

# просто сгенереный нейронкой


# Установка зависимостей
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# Установка PHP расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Очистка (оставляем рантайм-библиотеки — apk del иначе вычищает и их
# как "более не нужные" зависимости, а без них падает уже собранный gd:
# "libpng16.so.16: No such file or directory")
RUN apk del --no-cache libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev libxml2-dev \
    && apk add --no-cache libpng libjpeg-turbo freetype oniguruma libxml2

# PHP-FPM по умолчанию обнуляет окружение для рабочих процессов
# (clear_env = yes) — это означает, что переменные DB_HOST/DB_USER и
# т.п. из docker-compose.yml видны через `docker compose exec php php -r`
# (это отдельный CLI-процесс, окружение ему подставляется полностью),
# но НЕ видны настоящим HTTP-запросам, которые обрабатывает FPM-воркер.
# Без этой строки Database.php не находит переменные окружения,
# откатывается на config.php (127.0.0.1) и получает
# "SQLSTATE[HY000] [2002] Connection refused", потому что внутри
# php-контейнера на 127.0.0.1:3306 никто не слушает — MySQL в
# отдельном контейнере db.
RUN sed -i 's/;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

# Копирование конфигурации PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

# Установка рабочей директории
WORKDIR /var/www/html

# Создание пользователя www-data
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Изменение владельца
RUN chown -R www:www /var/www/html

# Переключение на www-data
USER www

# Запуск PHP-FPM
CMD ["php-fpm"]