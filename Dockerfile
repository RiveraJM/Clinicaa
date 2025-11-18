# =========================================
# Base image PHP 8.2 con Apache
# =========================================
FROM php:8.2-apache

# =========================================
# Variables de entorno
# =========================================
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer

# =========================================
# Instalar extensiones de PHP y utilidades
# =========================================
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# =========================================
# Instalar Composer globalmente
# =========================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================================
# Directorio de trabajo
# =========================================
WORKDIR /var/www/html

# =========================================
# Copiar archivos del proyecto
# =========================================
COPY . .

# =========================================
# Instalar dependencias de PHP
# =========================================
RUN if [ -f composer.json ]; then \
      composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist; \
    fi

# =========================================
# Configuración de permisos Apache
# =========================================
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# =========================================
# Habilitar mod_rewrite de Apache
# =========================================
RUN a2enmod rewrite

# =========================================
# Exponer puerto HTTP
# =========================================
EXPOSE 80

# =========================================
# Comando por defecto al iniciar el contenedor
# =========================================
CMD ["apache2-foreground"]
