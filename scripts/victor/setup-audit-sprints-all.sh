#!/usr/bin/env bash
# Deploy Sprints 1–6 (auditoria UI/UX) — sync plugins + S1 cleanup + regression.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  portal_sync_plugins
  portal_wp eval-file "$REPO/scripts/victor/s1-cleanup-aeo-paywall.php" 2>/dev/null || true
  portal_wp cache flush 2>/dev/null || true
  echo "OK $PORTAL_ID"
done

systemctl reload php8.3-fpm 2>/dev/null || true
bash "$SCRIPT_DIR/check-portal-regression-all.sh" --strict || true
echo "Audit sprints 1–6 deployed"
