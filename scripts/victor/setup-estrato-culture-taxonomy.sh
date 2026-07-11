#!/usr/bin/env bash
# Taxonomia v2 — portal Entretenimento, cultura pop e narrativas de nicho
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="${ESTRATO_WP:-/var/www/culture.estrato.cc}"
PORTAL="${ESTRATO_PORTAL:-estrato-culture}"

echo "=== Estrato Culture: Taxonomia + RSS + keywords ==="

if [[ -f "$REPO/scripts/victor/validate-rss-feeds.py" ]]; then
  python3 "$REPO/scripts/victor/validate-rss-feeds.py" "$REPO/portals/estrato-culture-taxonomy.php" || true
fi

if [[ -f "$REPO/scripts/victor/check-portal-regression-ci.sh" ]]; then
  bash "$REPO/scripts/victor/check-portal-regression-ci.sh" || true
fi

export ESTRATO_PORTAL="$PORTAL"
if [[ -f "$REPO/scripts/victor/apply-portal-config.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/apply-portal-config.php"
else
  sudo -u www-data wp --path="$WP" eval '
    if ( function_exists( "estrato_rss_apply_preset" ) ) {
      estrato_rss_apply_preset( "brasil-culture" );
    }
  '
fi

if [[ -f "$REPO/scripts/victor/setup-sprint9-rss-curation.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/setup-sprint9-rss-curation.php"
fi

echo "=== Taxonomia Culture concluída ==="
echo "Fonte canônica: $REPO/portals/estrato-culture-taxonomy.php"
echo "Preset RSS: brasil-culture"
