up:
	docker-compose up -d

down:
	docker-compose down

build:
	docker-compose build

restart:
	docker-compose down
	docker-compose up -d

logs:
	docker-compose logs -f

catalog-test:
	docker exec -it catalog_service php artisan test

checkout-test:
	docker exec -it checkout_service php artisan test

email-test:
	docker exec -it email_service php artisan test

test: catalog-test checkout-test email-test

catalog-shell:
	docker exec -it catalog_service bash

checkout-shell:
	docker exec -it checkout_service bash

email-shell:
	docker exec -it email_service bash

rebuild:
	docker-compose build --no-cache
	docker-compose up -d
