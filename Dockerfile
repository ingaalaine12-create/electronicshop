FROM php:8.2-apache

# Install PDO MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module (often useful for PHP applications)
RUN a2enmod rewrite

# Copy application files to Apache root
COPY . /var/www/html/

# Adjust permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80
