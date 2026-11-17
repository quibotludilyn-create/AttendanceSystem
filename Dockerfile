FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev libpng-dev libonig-dev curl unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd mbstring \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (PHPMailer)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy the rest of the application
COPY . .

# Create uploads directory
RUN mkdir -p /app/uploads/profiles && chmod 777 /app/uploads/profiles

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "router.php"]
