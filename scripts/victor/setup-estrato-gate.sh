#!/usr/bin/env bash
# Sprint 7 — Gate 100% & Polish (estrato.cc Victor)
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== Sprint 7 — Gate 100% ==="

if [[ -d "$REPO" ]]; then
  rsync -a --delete \
    "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
  rsync -a --delete \
    "$REPO/estrato-publisher-bridge/" "$WEB/wp-content/plugins/estrato-publisher-bridge/"
  mkdir -p /var/www/estrato/repo
  rsync -a "$REPO/scripts/" /var/www/estrato/repo/scripts/
  rsync -a "$REPO/portals/" /var/www/estrato/repo/portals/
fi

$WP plugin activate estrato-portal-bootstrap estrato-publisher-bridge --quiet 2>/dev/null || true

if [[ -f "$REPO/scripts/victor/setup-estrato-nginx-security-headers.sh" ]]; then
  bash "$REPO/scripts/victor/setup-estrato-nginx-security-headers.sh"
fi

if [[ -f "$REPO/scripts/victor/setup-sprint7-gate.php" ]]; then
  echo "--- gate cleanup ---"
  $WP eval-file "$REPO/scripts/victor/setup-sprint7-gate.php"
fi

if [[ -f "$REPO/scripts/victor/archive-legacy-posts.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/archive-legacy-posts.php" || true
fi

if [[ -f "$REPO/scripts/victor/fix-uncategorized.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/fix-uncategorized.php" || true
fi

$WP yoast index --reindex --skip-confirmation 2>/dev/null || true
$WP cache flush 2>/dev/null || true

echo "--- regression check (strict) ---"
bash "$REPO/scripts/victor/check-portal-regression.sh" --strict

echo "=== Sprint 7 gate done ==="
