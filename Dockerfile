FROM php:8.2-cli

# Install mysqli and other extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y libzip-dev libpng-dev libonig-dev && \
    docker-php-ext-install zip gd mbstring

WORKDIR /app
COPY . .

# Create uploads directory
RUN mkdir -p /app/uploads/profiles && chmod 777 /app/uploads/profiles

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "router.php"]
