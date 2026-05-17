docker-compose down -v
docker-compose up -d --build
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan storage:link
docker-compose exec app php composer.phar require barryvdh/laravel-dompdf
docker-compose exec app npm run build
