FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        libcurl4-openssl-dev \
        procps \
        && docker-php-ext-install sockets pcntl \
        && docker-php-ext-enable pcntl \
        && rm -rf /var/lib/apt/lists/*

COPY index.php attack.php worker.php /var/www/html/
RUN mkdir -p /var/www/html/runtime \
 && chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html \
 && chmod 777 /var/www/html/runtime

RUN echo "memory_limit = 1024M" > /usr/local/etc/php/conf.d/custom.ini \
 && echo "max_execution_time = 0" >> /usr/local/etc/php/conf.d/custom.ini \
 && echo "default_socket_timeout = 2" >> /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80
