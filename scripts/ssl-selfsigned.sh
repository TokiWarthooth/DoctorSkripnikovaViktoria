#!/bin/sh
# Временный самоподписанный сертификат, чтобы nginx мог стартовать до выпуска Let's Encrypt.
# Примеры:
#   ./scripts/ssl-selfsigned.sh doctorskripnikova.ru
#   ./scripts/ssl-selfsigned.sh 194.87.200.201
set -e
cd "$(dirname "$0")/.."
mkdir -p ssl
HOST="${1:-doctorskripnikova.ru}"

if echo "$HOST" | grep -qE '^[0-9.]+$'; then
  openssl req -x509 -nodes -days 60 -newkey rsa:2048 \
    -keyout ssl/privkey.pem \
    -out ssl/fullchain.pem \
    -subj "/CN=${HOST}" \
    -addext "subjectAltName=IP:${HOST}"
else
  openssl req -x509 -nodes -days 60 -newkey rsa:2048 \
    -keyout ssl/privkey.pem \
    -out ssl/fullchain.pem \
    -subj "/CN=${HOST}" \
    -addext "subjectAltName=DNS:${HOST},DNS:www.${HOST}"
fi

echo "OK: ssl/fullchain.pem и ssl/privkey.pem (временные, до Let's Encrypt)"
