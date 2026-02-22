FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install PHP extensions for MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Install GD for image handling (optional but useful)
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Update Apache config to use our document root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set upload limits
RUN echo "upload_max_filesize = 50M\npost_max_size = 55M\nmax_file_uploads = 25" > /usr/local/etc/php/conf.d/uploads.ini

# Copy project files
COPY . /var/www/html/

# Set permissions for uploads
RUN mkdir -p /var/www/html/public/uploads && chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 80
