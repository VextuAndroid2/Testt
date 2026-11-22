FROM php:8.2-apache

RUN docker-php-ext-install sockets \
 && apt-get update && apt-get install -y procps \
 && rm -rf /var/lib/apt/lists/*

COPY index.php attack.php worker.php /var/www/html/
RUN mkdir -p /var/www/html/runtime \
 && chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html \
 && chmod 777 /var/www/html/runtime

# raise limits for heavy floods
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory.ini \
 && echo "max_execution_time = 0" >> /usr/local/etc/php/conf.d/memory.ini

EXPOSE 80
