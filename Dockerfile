# Dockerfile pour Railway.app - Backend PHP GameZone
FROM php:8.2-apache

# Install MySQL extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for .htaccess
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy backend files from backend_infinityfree/api
COPY backend_infinityfree/api/ /var/www/html/

# Ensure .env.railway is copied (force copy hidden files)
COPY backend_infinityfree/api/.env.railway /var/www/html/.env.railway

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
