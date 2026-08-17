# syntax=docker/dockerfile:1
#
# Production images for HRMIS. One Dockerfile, two targets, so both images are
# built from the same vendor and asset stages and can never serve mismatched code.
#
# Build from the repository root:
#   docker build -f docker/production/Dockerfile --target php-fpm -t <registry>/hrmis/php-fpm:<tag> .
#   docker build -f docker/production/Dockerfile --target nginx   -t <registry>/hrmis/nginx:<tag>   .
#
# No .env is ever copied in — configuration arrives from the Kubernetes ConfigMap
# and Secret at runtime, and the entrypoint caches it then.

# -------------------------------------------------------------------- base --
# Shared by the vendor and php-fpm stages so composer resolves dependencies
# against exactly the PHP version and extension set that will run them. Using
# the stock composer image instead would resolve against its own PHP (currently
# 8.5), which several of this project's dependencies reject.
FROM php:8.3-fpm-alpine AS base

# fcgi supplies cgi-fcgi, used by the Kubernetes readiness probe against ping.path.
RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev; \
    apk add --no-cache \
        icu-libs \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        fcgi; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        bcmath \
        intl \
        exif \
        opcache; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del .build-deps; \
    rm -rf /tmp/pear

# ------------------------------------------------------------ php packages --
FROM base AS vendor
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN apk add --no-cache git unzip

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY . .
# --no-scripts: the post-autoload-dump hook runs `artisan package:discover`, which
# needs a writable bootstrap/cache and a bootable app. Discovery belongs in the
# final stage instead, where the production dependency set is complete.
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts

# ---------------------------------------------------------------- frontend --
FROM node:20-alpine AS assets
WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
# resources/js/app.js imports ZiggyVue from vendor/tightenco/ziggy, so the asset
# build genuinely depends on composer output — it is not PHP-independent.
COPY --from=vendor /var/www/html/vendor ./vendor

RUN npm run build

# ----------------------------------------------------------------- php-fpm --
FROM base AS php-fpm

# Match the uid/gid the existing production image runs as, so files already
# written to the mounted PVCs stay readable and writable after the cutover.
RUN set -eux; \
    addgroup -g 1005 hrmis; \
    if getent passwd 100 >/dev/null; then \
        deluser "$(getent passwd 100 | cut -d: -f1)"; \
    fi; \
    adduser -u 100 -G hrmis -s /bin/sh -D hrmis

WORKDIR /var/www/html

COPY docker/production/php/php.ini      /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/production/php/opcache.ini  /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/production/php/www.conf     /usr/local/etc/php-fpm.d/zz-www.conf

COPY --chown=hrmis:hrmis . .
COPY --from=vendor --chown=hrmis:hrmis /var/www/html/vendor ./vendor
COPY --from=assets --chown=hrmis:hrmis /var/www/html/public/build ./public/build

COPY --chmod=0755 docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh

# bootstrap/cache and storage must be writable by the runtime user: the
# entrypoint writes the config/route/view caches there on every start.
RUN set -eux; \
    rm -f .env; \
    mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache; \
    ln -sfn /var/www/html/storage/app/public public/storage; \
    # composer ran with --no-scripts, so package discovery never happened. It has
    # to run here, against the production dependency set — the repository's own
    # bootstrap/cache is excluded by .dockerignore because it lists dev providers.
    php artisan package:discover --no-ansi; \
    chown -R hrmis:hrmis storage bootstrap/cache

USER hrmis

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# -------------------------------------------------------------------- nginx --
FROM nginx:1.27-alpine AS nginx

# The official image's entrypoint runs envsubst over /etc/nginx/templates/*.template,
# which is how FASTCGI_PASS is pointed at the right php-fpm Service per environment.
COPY docker/production/nginx/default.conf.template /etc/nginx/templates/default.conf.template

# Static assets come from the same stage the php-fpm image used.
COPY --from=assets /var/www/html/public /var/www/html/public

# The avatars PVC is mounted over public/storage/avatars at runtime.
RUN mkdir -p /var/www/html/public/storage

EXPOSE 80
