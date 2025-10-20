# Use official PHP image
FROM php:8.2-apache

# Install MySQL client
RUN apt-get update && apt-get install -y default-mysql-client

# Copy app files
COPY . /var/www/html/

# Expose port
EXPOSE 8080

# Change Apache port to 8080 (Railway requirement)
RUN sed -i 's/80/8080/' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD bash scripts/migrate.sh && ["apache2-foreground"]