#!/usr/bin/env bash
# Sprint 8 — Taxonomia financeira (estrato.cc Victor)
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== Sprint 8 — Taxonomia financeira ==="

if [[ -d "$REPO" ]]; then
  rsync -a --delete \
    "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
  rsync -a --delete \
    "$REPO/estrato-rss-bootstrap/" "$WEB/wp-content/plugins/estrato-rss-bootstrap/"
  rsync -a "$REPO/portals/" /var/www/estrato/repo/portals/
  rsync -a "$REPO/scripts/" /var/www/estrato/repo/scripts/
fi

$WP plugin activate estrato-portal-bootstrap estrato-rss-bootstrap estrato-publisher-bridge --quiet 2>/dev/null || true

if [[ -f "$REPO/scripts/victor/apply-portal-config.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/apply-portal-config.php" || true
fi

if [[ -f "$REPO/scripts/victor/setup-sprint8-taxonomy.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/setup-sprint8-taxonomy.php"
fi

if [[ -f "$REPO/scripts/victor/setup-sprint11-taxonomy-v2.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/setup-sprint11-taxonomy-v2.php"
fi

if [[ -f "$REPO/scripts/victor/setup-sprint7-gate.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/setup-sprint7-gate.php" || true
fi

if [[ -f "$REPO/scripts/victor/fix-uncategorized.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/fix-uncategorized.php" || true
fi

$WP cache flush 2>/dev/null || true

echo "--- regression check (strict) ---"
bash "$REPO/scripts/victor/check-portal-regression.sh" --strict

echo "=== Sprint 8 done ==="
