# Dockerfile pour Railway.app - Backend PHP GameZone
FROM php:8.2-apache

# Install MySQL extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for .htaccess
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy all backend files
COPY . /var/www/html/

# Create uploads directory structure with proper permissions
RUN mkdir -p /var/www/uploads/avatars \
    && mkdir -p /var/www/uploads/games \
    && mkdir -p /var/www/uploads/files \
    && mkdir -p /var/www/uploads/images \
    && mkdir -p /var/www/uploads/thumbnails \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/www/uploads \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/uploads

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
