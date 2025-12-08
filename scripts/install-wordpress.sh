#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

SITE_URL=${SITE_URL:-http://localhost:8080}
ADMIN_USER=${ADMIN_USER:-admin}
ADMIN_PASSWORD=${ADMIN_PASSWORD:-admin}
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@example.com}

echo "Waiting for WordPress container to be ready..."
sleep 15

echo "Installing WordPress core (if not already installed)..."
docker compose run --rm wpcli wp core install \
  --url="${SITE_URL}" \
  --title="Eva Toast Notices" \
  --admin_user="${ADMIN_USER}" \
  --admin_password="${ADMIN_PASSWORD}" \
  --admin_email="${ADMIN_EMAIL}" \
  --skip-email || true

echo "Installing and activating WooCommerce..."
docker compose run --rm wpcli wp plugin install woocommerce --activate || true

echo "Activating Eva Toast Notices plugin..."
docker compose run --rm wpcli wp plugin activate eva-toast-notices || true

echo "Done. Visit ${SITE_URL} to access the site."


