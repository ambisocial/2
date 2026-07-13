#!/usr/bin/env bash
# Aplica logotipo, cores PressGrid e preset RSS financeiro no Victor.
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
WP="sudo -u www-data wp --path=$WEB"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"

echo "=== estrato.cc portal branding ==="

# Garante plugins atualizados (deploy manual ou git pull no Victor)
if [[ -d "$REPO/estrato-portal-bootstrap" ]]; then
  rsync -a "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
  rsync -a "$REPO/estrato-rss-bootstrap/" "$WEB/wp-content/plugins/estrato-rss-bootstrap/" 2>/dev/null || true
fi

$WP plugin activate estrato-portal-bootstrap estrato-rss-bootstrap --quiet

# Config YAML → option + branding + menu financeiro
if [[ -f "$REPO/scripts/victor/apply-portal-config.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/apply-portal-config.php"
else
  $WP eval '
    if ( function_exists( "estrato_portal_apply_branding" ) ) {
      estrato_portal_apply_branding( estrato_portal_get_config() );
    }
    if ( function_exists( "estrato_rss_apply_preset" ) ) {
      estrato_rss_apply_preset( "brasil-financeiro" );
    }
    echo "branding applied\n";
  '
fi

$WP theme mod get custom_logo
$WP theme mod get pressgrid_accent_color
$WP menu list --format=table 2>/dev/null | head -5 || true

echo "=== done ==="
