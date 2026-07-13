#!/usr/bin/env bash
# Sprint 6 — AEO, GEO & indexação.
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== estrato.cc AEO/GEO (Sprint 6) ==="

if [[ -d "$REPO/estrato-portal-bootstrap" ]]; then
  rsync -a "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
fi
if [[ -d "$REPO/estrato-publisher-bridge" ]]; then
  rsync -a "$REPO/estrato-publisher-bridge/" "$WEB/wp-content/plugins/estrato-publisher-bridge/"
fi

$WP plugin activate estrato-portal-bootstrap estrato-publisher-bridge --quiet
$WP eval-file "$REPO/scripts/victor/setup-sprint6-aeo.php"

if [[ -f "$REPO/scripts/victor/index-bot/ping-sitemaps.py" ]]; then
  ESTRATO_DOMAIN="https://estrato.cc" python3 "$REPO/scripts/victor/index-bot/ping-sitemaps.py" || true
fi

systemctl restart php8.3-fpm 2>/dev/null || true

echo "--- validação AEO ---"
for f in llms.txt llms-full.txt ads.txt estrato-indexnow-key.txt; do
  code=$(curl -sk -o /dev/null -w "%{http_code}" -H "Host: estrato.cc" "https://187.127.12.186/$f" 2>/dev/null || echo "000")
  echo "/$f → HTTP $code"
done

echo "=== Sprint 6 done ==="
