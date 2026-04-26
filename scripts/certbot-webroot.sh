#!/bin/sh
# Выпуск Let's Encrypt (HTTP-01 на порту 80). Nginx в Docker должен уже работать и отдавать /.well-known/
#
# 1) Один раз: ./scripts/ssl-selfsigned.sh doctorskripnikova.ru
# 2) docker compose -f docker-compose.prod.yml up -d
# 3) ./scripts/certbot-webroot.sh your@email.ru
#
# Сертификаты копируются в ./ssl — контейнер их подхватывает без доступа к /etc/letsencrypt.
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
EMAIL="${1:?Укажите email: $0 your@email.ru}"
WEBROOT="$ROOT/public"
DOMAIN="doctorskripnikova.ru"

certbot certonly \
  --webroot -w "$WEBROOT" \
  -d "$DOMAIN" -d "www.$DOMAIN" \
  --email "$EMAIL" \
  --agree-tos \
  --non-interactive

SSL_DIR="$ROOT/ssl"
mkdir -p "$SSL_DIR"
cp "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" "$SSL_DIR/fullchain.pem"
cp "/etc/letsencrypt/live/$DOMAIN/privkey.pem" "$SSL_DIR/privkey.pem"
chmod 644 "$SSL_DIR/fullchain.pem"
chmod 600 "$SSL_DIR/privkey.pem"

echo "Готово. Перезапуск nginx: docker compose -f docker-compose.prod.yml restart nginx"
