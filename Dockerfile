FROM php:8.2-apache

COPY index.php /var/www/html/
COPY style.css /var/www/html/
COPY story/ /var/www/html/story/
COPY people/ /var/www/html/people/
COPY photos/ /var/www/html/photos/
