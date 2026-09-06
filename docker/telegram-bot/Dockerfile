FROM php:8.2-cli-alpine

RUN apk add --no-cache curl-dev \
    && docker-php-ext-install pdo_mysql curl

WORKDIR /var/www/html

CMD ["php", "bin/telegram-bot.php"]
