# Dockerfile pour Railway.app - Backend PHP GameZone - VERSION 2.1
# Force rebuild to fix PHP ternary syntax error
FROM php:8.2-apache

# Install MySQL extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for .htaccess
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy backend files from backend_infinityfree/api
COPY backend_infinityfree/api/ /var/www/html/

# Copy setup scripts from root to /var/www/html/
COPY setup_complete.php /var/www/html/
COPY init_all_tables.php /var/www/html/

# Ensure .env.railway is copied (force copy hidden files)
COPY backend_infinityfree/api/.env.railway /var/www/html/.env.railway

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
