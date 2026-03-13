# ---- Stage 1: Install PHP dependencies ----
FROM php:7.4-cli AS vendor

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install zip gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/library

COPY library/composer.json library/composer.lock* ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# ---- Stage 2: Runtime ----
FROM php:7.4-apache

# Install PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libxml2-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        curl \
        locales \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        mbstring \
        xml \
        curl \
        opcache \
        exif \
    && apt-get purge -y \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libxml2-dev \
        libonig-dev \
        libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Thai locale
RUN sed -i '/th_TH.UTF-8/s/^# //' /etc/locale.gen && locale-gen

# ---- Apache config ----
RUN a2enmod rewrite headers

# Security headers
RUN { \
        echo 'ServerTokens Prod'; \
        echo 'ServerSignature Off'; \
        echo 'TraceEnable Off'; \
        echo ''; \
        echo '<IfModule mod_headers.c>'; \
        echo '    Header always set X-Content-Type-Options "nosniff"'; \
        echo '    Header always set X-Frame-Options "SAMEORIGIN"'; \
        echo '    Header always set Referrer-Policy "strict-origin-when-cross-origin"'; \
        echo '    Header always unset X-Powered-By'; \
        echo '</IfModule>'; \
    } > /etc/apache2/conf-available/security-custom.conf \
    && a2enconf security-custom

# Non-privileged port
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/' /etc/apache2/sites-available/*.conf

# Document root -> /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow AllowOverride for .htaccess
RUN { \
        echo '<Directory ${APACHE_DOCUMENT_ROOT}>'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } >> /etc/apache2/sites-available/000-default.conf

# Allow www-data to write Apache runtime files
RUN chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

# ---- PHP config ----
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/app.ini"

WORKDIR /var/www/html

# Copy vendor from composer stage
COPY --chown=www-data:www-data --from=vendor /app/library/vendor library/vendor/

# Copy application code
COPY --chown=www-data:www-data . .

# Prepare writable directories + entrypoint
RUN mkdir -p public/uploads files tmp/session \
    && chown -R www-data:www-data public/uploads files tmp \
    && sed -i 's/\r$//' docker/entrypoint.sh \
    && chmod +x docker/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["docker/entrypoint.sh"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

CMD ["apache2-foreground"]
