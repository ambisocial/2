#!/usr/bin/env bash
# Sprint 5 — estrutura, navegação, hubs, layout, visual.
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== estrato.cc Nav & Visual (Sprint 5) ==="

if [[ -d "$REPO/estrato-portal-bootstrap" ]]; then
  rsync -a "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
fi

$WP plugin activate estrato-portal-bootstrap --quiet
$WP eval-file "$REPO/scripts/victor/setup-sprint5-nav.php"

$WP rewrite flush --hard
systemctl restart php8.3-fpm 2>/dev/null || true

echo "--- hubs ---"
for hub in selic ibovespa dolar cripto inflacao tributacao agronegocio; do
  code=$(curl -sk -o /dev/null -w "%{http_code}" -H "Host: estrato.cc" "https://187.127.12.186/tudo-sobre/$hub/" 2>/dev/null || echo "000")
  echo "/tudo-sobre/$hub/ → HTTP $code"
done
code=$(curl -sk -o /dev/null -w "%{http_code}" -H "Host: estrato.cc" "https://187.127.12.186/cotacoes/" 2>/dev/null || echo "000")
echo "/cotacoes/ → HTTP $code"

echo "=== Sprint 5 done ==="
