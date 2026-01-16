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

# Install Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | sh \
    && apk add --no-cache symfony-cli

# PHP configuration
RUN cat <<'EOF' > /usr/local/etc/php/conf.d/app.ini
[PHP]
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 256M
date.timezone = Europe/Paris
EOF

# PHP-FPM pool configuration
RUN cat <<'EOF' > /usr/local/etc/php-fpm.d/www.conf
[www]
user = app
group = app
listen = 9000
pm = dynamic
pm.max_children = 20
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
clear_env = no
catch_workers_output = yes
decorate_workers_output = no
EOF

# Supervisor configuration
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor \
    && cat <<'EOF' > /etc/supervisord.conf
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[unix_http_server]
file=/var/run/supervisor.sock

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[include]
files = /etc/supervisor/conf.d/*.conf
EOF

RUN cat <<'EOF' > /etc/supervisor/conf.d/messenger.conf
[program:messenger-async]
command=php /var/www/app/bin/console messenger:consume async --time-limit=3600 --memory-limit=128M -vv
directory=/var/www/app
user=app
numprocs=1
autostart=true
autorestart=true
startsecs=0
startretries=10
stopsignal=SIGTERM
stopwaitsecs=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

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
RUN cat <<'EOF' > /usr/local/etc/php/conf.d/opcache.ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.interned_strings_buffer=16
opcache.fast_shutdown=1
EOF

# Copy application source
COPY --chown=app:app ./app/ /var/www/app/
COPY --from=node-builder --chown=app:app /app/public/build /var/www/app/public/build

USER app
ENV APP_ENV=prod
RUN composer install --no-dev --no-scripts --optimize-autoloader \
    && php bin/console assets:install

# Stage: Web Prod - PHP-FPM for production
FROM php-prod AS web

USER root
RUN cat <<'EOF' > /entrypoint.sh
#!/bin/sh
set -e
if [ -n "$DATABASE_URL" ]; then
    echo "Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
fi
php bin/console cache:clear --no-warmup
php bin/console cache:warmup
exec "$@"
EOF
RUN chmod +x /entrypoint.sh
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
HEALTHCHECK --start-period=10s --interval=10s CMD wget -q --spider http://localhost || exit 1
