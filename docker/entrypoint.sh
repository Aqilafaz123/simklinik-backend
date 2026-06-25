#!/bin/sh
set -e

FRONTEND_ASSETS="/var/www/frontend/public/assets"
BACKEND_ASSETS="/var/www/html/public/assets"

if [ -d "$FRONTEND_ASSETS" ] && [ ! -e "$BACKEND_ASSETS" ]; then
    ln -sfn "$FRONTEND_ASSETS" "$BACKEND_ASSETS"
fi

exec apache2-foreground
