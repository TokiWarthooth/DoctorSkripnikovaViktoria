#!/bin/bash
# Скрипт автоматического деплоя на сервере (вызов по webhook или вручную).
# Каталог клона на сервере должен совпадать с путём ниже (при другом пути — поправьте).

set -e
echo "🚀 Starting deployment (Doctor Skripnikova Victoria)..."

cd /var/www/DoctorSkripnikovaViktoria || exit 1

echo "📥 Pulling latest changes from GitHub..."
git pull origin main

echo "🛑 Stopping containers..."
docker-compose -f docker-compose.prod.yml down

echo "🔨 Building and starting containers..."
docker-compose -f docker-compose.prod.yml up -d --build

echo "🔐 Setting permissions (storage)..."
# Имя контейнера зависит от имени папки проекта; id сервиса «php» надёжнее
PHP_CTN=$(docker-compose -f docker-compose.prod.yml ps -q php 2>/dev/null || true)
if [ -n "$PHP_CTN" ]; then
  docker exec "$PHP_CTN" chown -R www-data:www-data /var/www/html/storage /var/www/html/vendor 2>/dev/null || true
  docker exec "$PHP_CTN" chmod -R 775 /var/www/html/storage 2>/dev/null || true
fi

echo "✅ Checking container status..."
docker-compose -f docker-compose.prod.yml ps

echo "🧹 Cleaning up unused images..."
docker system prune -f

echo "✨ Deployment completed successfully!"
