#!/bin/bash
# Deploy staged files (bot / image generation / invoice email changes)
set -e

APP=/home/u817318881/domains/darkgrey-ibex-557173.hostingersite.com/public_html/lisa_app
STAGE=/home/u817318881/deploy-1015

echo "== 1. copy staged files into app =="
cp -r $STAGE/. $APP/
rm -rf $STAGE
echo "files copied"

cd $APP

echo "== 2. dompdf check =="
if [ ! -d vendor/barryvdh/laravel-dompdf ]; then
  echo "dompdf MISSING -> running composer install"
  composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "dompdf present OK"
fi

echo "== 3. migrate =="
php artisan migrate --force

echo "== 4. rebuild caches =="
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "== 5. orders columns =="
php artisan tinker --execute 'echo implode(",", \Illuminate\Support\Facades\Schema::getColumnListing("orders"));' 2>/dev/null || true
echo ""

echo "== 6. new invoice routes =="
php artisan route:list --path=orders 2>/dev/null | head -15 || true

echo "DONE_DEPLOY_1015"
