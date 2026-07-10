#!/usr/bin/env bash
# Sprint 3 — E-E-A-T: páginas institucionais, autores, footer, pipeline authors.
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== estrato.cc E-E-A-T setup (Sprint 3) ==="

if [[ -d "$REPO/estrato-portal-bootstrap" ]]; then
  rsync -a "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
fi
if [[ -d "$REPO/estrato-publisher-bridge" ]]; then
  rsync -a "$REPO/estrato-publisher-bridge/" "$WEB/wp-content/plugins/estrato-publisher-bridge/"
fi

$WP plugin activate estrato-portal-bootstrap estrato-publisher-bridge contact-form-7 --quiet

$WP eval-file "$REPO/scripts/victor/setup-institutional.php"

$WP rewrite flush --hard
systemctl restart php8.3-fpm-estrato.cc 2>/dev/null || systemctl restart php8.3-fpm 2>/dev/null || true

echo "--- validação E-E-A-T ---"
for path in sobre politica-editorial contato politica-de-privacidade; do
  code=$(curl -sk -o /dev/null -w "%{http_code}" -H "Host: estrato.cc" "https://187.127.12.186/$path/" 2>/dev/null || echo "000")
  echo "/$path/ → HTTP $code"
done
$WP user list --role=author --format=count 2>/dev/null | xargs -I{} echo "Autores: {}"
$WP user list --format=count 2>/dev/null | xargs -I{} echo "Usuários total: {}"

echo "=== Sprint 3 E-E-A-T done ==="
