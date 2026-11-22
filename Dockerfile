FROM php:8.2-apache

RUN docker-php-ext-install sockets

COPY index.php /var/www/html/
COPY attack.php /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
 && chmod 755 /var/www/html

EXPOSE 80
