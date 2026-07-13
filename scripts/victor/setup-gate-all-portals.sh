#!/usr/bin/env bash
# Gate 6/6 — fix satélites + boost ratio + regression strict.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  export ESTRATO_REPO="$REPO"
  portal_sync_plugins 2>/dev/null || true
  portal_wp eval-file "$REPO/scripts/victor/fix-gate-satellites.php" 2>/dev/null || true
  portal_wp eval-file "$REPO/scripts/victor/boost-mid-posts-300.php" 2>/dev/null || true
  portal_wp eval-file "$REPO/scripts/victor/trim-mid-posts-for-ratio.php" 2>/dev/null || true
  portal_wp cache flush 2>/dev/null || true
  echo "OK $PORTAL_ID"
done

systemctl reload php8.3-fpm 2>/dev/null || true
bash "$SCRIPT_DIR/check-portal-regression-all.sh" --strict
echo "Gate 6/6 complete"
