#!/usr/bin/env bash
# Sprint 10 — Operação contínua (estrato.cc Victor)
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== Sprint 10 — Operação contínua ==="

if [[ -d "$REPO" ]]; then
  rsync -a --delete \
    "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
  rsync -a --delete \
    "$REPO/estrato-rss-bootstrap/" "$WEB/wp-content/plugins/estrato-rss-bootstrap/"
  rsync -a "$REPO/portals/" /var/www/estrato/repo/portals/
  rsync -a "$REPO/scripts/" /var/www/estrato/repo/scripts/
fi

mkdir -p /var/www/estrato/reports
chown www-data:www-data /var/www/estrato/reports 2>/dev/null || true

$WP plugin activate estrato-portal-bootstrap estrato-rss-bootstrap --quiet 2>/dev/null || true

# S10.5 — garantir pipeline_primary
if [[ -f "$REPO/scripts/victor/apply-portal-config.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/apply-portal-config.php" || true
fi

if [[ -f "$REPO/scripts/victor/setup-sprint10-ops.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/setup-sprint10-ops.php"
fi

if [[ -f "$REPO/scripts/victor/ops-monthly-maintenance.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/ops-monthly-maintenance.php" || true
fi

$WP cache flush 2>/dev/null || true

echo "--- regression CI (static) ---"
bash "$REPO/scripts/victor/check-portal-regression-ci.sh"

echo "--- regression Victor (strict) ---"
bash "$REPO/scripts/victor/check-portal-regression.sh" --strict

echo "=== Sprint 10 done ==="
