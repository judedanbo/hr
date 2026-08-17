#!/bin/sh
#
# Warms Laravel's caches at container start rather than at image build time.
#
# This is the whole reason configuration can live in a ConfigMap/Secret instead
# of a baked .env: at build time none of those values exist, so caching config
# then would freeze the wrong values into the image.
#
# Kubernetes workloads that run something other than php-fpm (queue worker,
# scheduler, migration Job) must override `args`, NOT `command` — overriding
# `command` replaces this entrypoint and skips the cache warm-up.

set -eu

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
