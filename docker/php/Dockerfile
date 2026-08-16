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

# Очистка
RUN apk del --no-cache libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev libxml2-dev

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