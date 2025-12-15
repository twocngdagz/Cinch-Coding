#!/bin/sh
php artisan config:clear
php artisan migrate:fresh
supervisord -c /etc/supervisor/conf.d/supervisord.conf &
exec php artisan serve --host=0.0.0.0 --port=8000
