.PHONY: test pest analyse fix check serve seed

test:
	php artisan test

pest:
	pest

analyse:
	phpstan analyse

fix:
	rector process
	pint

check:
	composer check

serve:
	php artisan serve

seed:
	php artisan migrate:fresh --seed

