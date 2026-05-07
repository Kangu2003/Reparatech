# ============================================================
# ReparaTech — Dockerfile para Render
# PHP 8.2 + Apache
# ============================================================
FROM php:8.2-apache

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar Apache: DocumentRoot en /var/www/html
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copiar el código del proyecto
COPY . /var/www/html/

# Permisos para la carpeta de uploads
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# Configurar Apache para permitir .htaccess y rewrite
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Crear .htaccess principal si no existe (para manejo de rutas)
RUN if [ ! -f /var/www/html/.htaccess ]; then \
    echo "Options -Indexes" > /var/www/html/.htaccess && \
    echo "DirectoryIndex index.php" >> /var/www/html/.htaccess; \
fi

# Exponer el puerto que usa Render (10000 por defecto para web services)
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/ || exit 1
