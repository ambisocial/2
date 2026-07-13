#!/usr/bin/env bash
# Sprint 9 — Curadoria RSS por categoria (estrato.cc Victor)
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== Sprint 9 — Curadoria RSS ==="

if [[ -d "$REPO" ]]; then
  rsync -a --delete \
    "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
  rsync -a --delete \
    "$REPO/estrato-rss-bootstrap/" "$WEB/wp-content/plugins/estrato-rss-bootstrap/"
  rsync -a "$REPO/portals/" /var/www/estrato/repo/portals/
  rsync -a "$REPO/scripts/" /var/www/estrato/repo/scripts/
fi

$WP plugin activate estrato-rss-bootstrap estrato-portal-bootstrap --quiet 2>/dev/null || true

if [[ -f "$REPO/scripts/victor/setup-sprint9-rss-curation.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/setup-sprint9-rss-curation.php"
fi

if [[ -f "$REPO/scripts/victor/merge-legacy-categories.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/merge-legacy-categories.php" || true
fi

if [[ -f "$REPO/scripts/victor/setup-sprint7-gate.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/setup-sprint7-gate.php" || true
fi

if [[ -f "$REPO/scripts/victor/index-bot/index-by-category.py" ]]; then
  ESTRATO_DOMAIN="https://estrato.cc" python3 "$REPO/scripts/victor/index-bot/index-by-category.py" || true
fi

if [[ -f "$REPO/scripts/victor/enrich-mid-posts.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/enrich-mid-posts.php" || true
fi

$WP cache flush 2>/dev/null || true

echo "--- regression check (strict) ---"
bash "$REPO/scripts/victor/check-portal-regression.sh" --strict

echo "=== Sprint 9 done ==="
