# Dockerfile pour Railway.app - Backend PHP GameZone - VERSION 2.1
# Force rebuild to fix PHP ternary syntax error
FROM php:8.2-apache

# Default runtime flags (no secrets)
ENV APP_ENV=production \
    SESSION_SAMESITE=None \
    SESSION_SECURE=1

# Install MySQL extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for .htaccess
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy real backend into web root (endpoints at /)
COPY api/ /var/www/html/

# Copy setup scripts to web root
COPY setup_complete.php /var/www/html/
COPY init_all_tables.php /var/www/html/

# Create uploads directory structure under /var/www/html with proper permissions
RUN mkdir -p /var/www/html/uploads/avatars \
    && mkdir -p /var/www/html/uploads/games \
    && mkdir -p /var/www/html/uploads/files \
    && mkdir -p /var/www/html/uploads/images \
    && mkdir -p /var/www/html/uploads/thumbnails \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
