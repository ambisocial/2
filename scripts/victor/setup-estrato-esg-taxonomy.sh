#!/usr/bin/env bash
# Taxonomia v2 — portal Esg
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="${ESTRATO_WP:-/var/www/esg.estrato.cc}"
PORTAL="${ESTRATO_PORTAL:-estrato-esg}"

echo "=== Estrato Esg: Taxonomia + RSS + keywords ==="

if [[ -f "$REPO/scripts/victor/validate-rss-feeds.py" ]]; then
  python3 "$REPO/scripts/victor/validate-rss-feeds.py" "$REPO/portals/estrato-esg-taxonomy.php" || true
fi

export ESTRATO_PORTAL="$PORTAL"
if [[ -f "$REPO/scripts/victor/apply-portal-config.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/apply-portal-config.php"
else
  sudo -u www-data wp --path="$WP" eval '
    if ( function_exists( "estrato_rss_apply_preset" ) ) {
      estrato_rss_apply_preset( "brasil-esg" );
    }
  '
fi

if [[ -f "$REPO/scripts/victor/setup-sprint9-rss-curation.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/setup-sprint9-rss-curation.php" || true
fi

echo "=== OK Estrato Esg taxonomy ==="
