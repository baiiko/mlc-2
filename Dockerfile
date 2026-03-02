# =============================================================================
# Stage: PHP Base - Common setup (no code, for dev with volumes)
# =============================================================================
FROM php:8.4-fpm-alpine AS php-base
WORKDIR /var/www/app

# Create app user with configurable UID/GID
ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} app \
    && adduser -D -s /bin/sh -u ${UID} -G app app

# Install system dependencies and PHP extensions
RUN apk --no-cache add \
        git \
        curl \
        icu-dev \
        icu-data-full \
        libxml2-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        supervisor \
        wget \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        xml \
        ctype \
    && rm -rf /tmp/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP configuration
COPY docker/php/app.ini /usr/local/etc/php/conf.d/app.ini

# PHP-FPM pool configuration
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Supervisor configuration
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/supervisor/messenger.conf /etc/supervisor/conf.d/messenger.conf

RUN chown -R app:app /var/www/app
USER app

EXPOSE 9000
CMD ["php-fpm"]

# =============================================================================
# Stage: Node Dev - Vite dev server
# =============================================================================
FROM node:22-alpine AS node-dev
WORKDIR /var/www/app

ARG UID=1000
ARG GID=1000
RUN deluser --remove-home node 2>/dev/null || true \
    && addgroup -g ${GID} app \
    && adduser -u ${UID} -G app -s /bin/sh -D app

USER app
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host"]

# =============================================================================
# PRODUCTION STAGES
# =============================================================================

# Stage: Node Builder - Build frontend assets
FROM node:lts-alpine AS node-builder
WORKDIR /app
COPY app/package.json app/package-lock.json* ./
RUN npm install
COPY app/ ./
RUN npm run build

# Stage: PHP Prod - With code baked in
FROM php-base AS php-prod

USER root

# Enable opcache for production
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy application source
COPY --chown=app:app ./app/ /var/www/app/
COPY --from=node-builder --chown=app:app /app/public/build /var/www/app/public/build

# Create directories with correct ownership (for Docker volume initialization)
RUN mkdir -p /var/www/app/public/uploads/maps/thumbnails \
    && mkdir -p /var/matchsettings \
    && chown -R app:app /var/www/app/public/uploads \
    && chown -R app:app /var/matchsettings

USER app
ENV APP_ENV=prod
RUN composer install --no-dev --no-scripts --optimize-autoloader \
    && php bin/console assets:install

# Stage: Web Prod - PHP-FPM for production
FROM php-prod AS web

USER root
COPY docker/entrypoint.sh /entrypoint.sh
RUN sed -i 's/\r$//' /entrypoint.sh && chmod +x /entrypoint.sh
USER app

HEALTHCHECK --start-period=10s --interval=10s CMD nc -z -w90 127.0.0.1 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# Stage: Worker Prod - Messenger consumer for production
FROM php-prod AS worker
USER root
HEALTHCHECK --start-period=30s --interval=10s --timeout=5s --retries=3 \
    CMD ps aux | grep -v grep | grep -q "messenger:consume"
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]

# Stage: Nginx - Web server with templates
FROM nginx:alpine AS nginx

COPY ./docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template

ENV NGINX_HOST=localhost
ENV PHP_FPM_HOST=php

COPY --from=node-builder /app/public /var/www/app/public
COPY ./app/public /var/www/app/public

EXPOSE 80
HEALTHCHECK --start-period=10s --interval=10s CMD wget -q -O /dev/null --server-response http://127.0.0.1 2>&1 | grep -q "HTTP/" || exit 1
