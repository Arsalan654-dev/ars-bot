#!/bin/bash
#
# Hostinger restructure: move the Laravel app INSIDE public_html
# so the full source tree is visible in the File Manager, while the
# web root stays clean and secure.
#
# Before:  ~/lisa_app  +  public_html -> symlink to lisa_app/public
# After:   public_html/index.php, .htaccess, asset symlinks + public_html/lisa_app/
#
set -e

DOMAIN=/home/u817318881/domains/darkgrey-ibex-557173.hostingersite.com
HOME_DIR=/home/u817318881

echo "== 0. backup .env =="
cp $HOME_DIR/lisa_app/.env $HOME_DIR/.env.lisa.bak
echo "env backed up to ~/.env.lisa.bak"

if [ -d "$DOMAIN/public_html/lisa_app" ] && [ ! -L "$DOMAIN/public_html" ]; then
  echo "== ALREADY RESTRUCTURED — skipping move steps =="
else
  echo "== 1. safety check =="
  if [ ! -L "$DOMAIN/public_html" ]; then echo "ABORT: public_html is not a symlink"; exit 1; fi
  echo "--- current public dir ---"
  ls -la $HOME_DIR/lisa_app/public/ | head -20

  echo "== 2. create real public_html and move app inside =="
  rm $DOMAIN/public_html
  mkdir $DOMAIN/public_html
  mv $HOME_DIR/lisa_app $DOMAIN/public_html/lisa_app

  echo "== 3. fix app-internal storage symlink (was absolute) =="
  cd $DOMAIN/public_html/lisa_app/public
  rm -f storage
  ln -s ../storage/app/public storage
  ls -la storage
fi

echo "== 4. web-root asset symlinks =="
cd $DOMAIN/public_html
for item in assets build favicon.ico favicon.png robots.txt .well-known; do
  if [ -e "lisa_app/public/$item" ]; then ln -sfn "lisa_app/public/$item" "$item"; echo "linked $item"; fi
done
ln -sfn lisa_app/storage/app/public storage
echo "linked storage"

echo "== 5. web-root entry files =="
cat > index.php <<'PHP'
<?php

/**
 * Web root entry point (Hostinger layout).
 *
 * The Laravel application lives in public_html/lisa_app so the full
 * source tree stays visible in the File Manager, while the web root
 * only exposes public assets through symlinks.
 */

require __DIR__.'/lisa_app/public/index.php';
PHP
echo "wrote index.php"

cat > .htaccess <<'HTACCESS'
AddHandler application/x-httpd-php85 .php

<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Block direct access to the application internals (.env, vendor, config...)
    RewriteRule ^lisa_app(/|$) - [F,L]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS
echo "wrote .htaccess"

echo "== 6. rebuild caches from the new location =="
cd $DOMAIN/public_html/lisa_app
php artisan optimize:clear || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "== 7. verify =="
ls -la $DOMAIN/public_html
echo "--- app root ---"
ls $DOMAIN/public_html/lisa_app | head -25

echo "DONE_RESTRUCTURE"
