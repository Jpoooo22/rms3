# PHP + Apache image to run the Records Management System
FROM php:8.2-apache

# mysqli is what the app uses to talk to MySQL
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Serve default.php via index.php redirect, silence ServerName warning
RUN echo 'DirectoryIndex index.php index.html' > /etc/apache2/conf-enabled/directoryindex.conf \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Copy the application into the web root
COPY . /var/www/html/

# Let Apache (www-data) write uploads (profile pictures, task reference files, signatures)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
