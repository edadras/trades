#!/usr/bin/env bash
# Zero-surprise deploy for a single server. Run from the project root as the deploy user.
set -euo pipefail
php artisan down --render="errors::503" || true
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan optimize
php artisan scout:sync-index-settings || true
php artisan horizon:terminate
php artisan inertia:stop-ssr || true
php artisan reverb:restart || true
php artisan up
