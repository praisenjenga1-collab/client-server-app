# Use an official lightweight PHP image with Apache built-in
FROM php:8.2-apache

# Install the PDO MySQL extension so PHP can speak to your MySQL database container
RUN docker-php-ext-install pdo pdo_mysql

# Copy your local PHP/HTML/CSS files into the container's web directories
COPY . /var/www/html/

# Open up port 80 for web traffic
EXPOSE 80