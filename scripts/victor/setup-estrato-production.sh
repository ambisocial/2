#!/usr/bin/env bash
# Aplica configuração de produção no Victor para estrato.cc
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== estrato.cc production setup ==="

$WP option update timezone_string 'America/Sao_Paulo'
$WP option update blogdescription 'Economia, mercados e finanças'
$WP rewrite structure '/%postname%/' --hard
$WP rewrite flush --hard

# Pipeline principal; RSS complementar (2 items/feed, 15 max, hourly)
$WP option update estrato_rss_settings '{"items_per_feed":2,"items_first_run":6,"max_per_run":15,"cron_schedule":"hourly"}' --format=json

$WP plugin activate estrato-publisher-bridge estrato-rss-bootstrap estrato-portal-bootstrap 2>/dev/null || true
$WP plugin deactivate estrato-publisher-bridge --quiet 2>/dev/null || true
$WP plugin activate estrato-publisher-bridge --quiet

# Backfill thumbnails pendentes
if [[ -f /tmp/backfill-featured-images.php ]]; then
  $WP eval-file /tmp/backfill-featured-images.php || true
fi

$WP cron event list | grep -E 'estrato|rss' | head -10
echo "=== done ==="
