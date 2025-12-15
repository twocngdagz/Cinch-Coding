#!/bin/sh
php artisan migrate:fresh
php artisan config:clear
php artisan config:cache
supervisord -c /etc/supervisor/conf.d/supervisord.conf &
exec php artisan serve --host=0.0.0.0 --port=8000
