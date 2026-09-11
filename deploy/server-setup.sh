#!/usr/bin/env bash
set -euo pipefail

APP_DIR=/opt/yandex-reviews
APP_PORT="${APP_PORT:-8080}"
ARCHIVE="${1:-/tmp/yandex-reviews.tgz}"

if ! command -v docker >/dev/null 2>&1; then
    curl -fsSL https://get.docker.com | sh
fi

mkdir -p "$APP_DIR"
tar xzf "$ARCHIVE" -C "$APP_DIR"
cd "$APP_DIR"

IP="$(curl -4 -s --max-time 5 ifconfig.me || hostname -I | awk '{print $1}')"

if [ ! -f .env ]; then
    cp .env.example .env
fi

sed -i "s#^APP_ENV=.*#APP_ENV=production#; s#^APP_DEBUG=.*#APP_DEBUG=false#; s#^APP_URL=.*#APP_URL=http://$IP:$APP_PORT#; s#^SANCTUM_STATEFUL_DOMAINS=.*#SANCTUM_STATEFUL_DOMAINS=$IP:$APP_PORT,$IP,localhost:$APP_PORT#" .env
grep -q '^APP_PORT=' .env && sed -i "s#^APP_PORT=.*#APP_PORT=$APP_PORT#" .env || echo "APP_PORT=$APP_PORT" >> .env

if command -v ufw >/dev/null 2>&1 && ufw status | grep -q '^Status: active'; then
    ufw allow "$APP_PORT"/tcp >/dev/null
fi

docker compose up -d --build
docker compose ps
echo
echo "Готово: http://$IP:$APP_PORT  (admin@example.com / password)"
