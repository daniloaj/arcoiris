# Usamos la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalamos extensiones necesarias para PHPMailer
RUN docker-php-ext-install mysqli

# Habilitamos mod_rewrite para URLs amigables
RUN a2enmod rewrite

# Copiamos todos los archivos del proyecto al servidor web
COPY . /var/www/html/

# Damos permisos para que Apache pueda leer los archivos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Instalamos Composer para PHPMailer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --optimize-autoloader --no-dev --working-dir=/var/www/html

# Exponemos el puerto 80 (el estándar para web)
EXPOSE 80