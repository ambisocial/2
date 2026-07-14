#!/usr/bin/env bash
# Sprint A5 — operação contínua (ops) em todos os satélites.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/sprint-a5-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

for PORTAL_ID in estrato-mind estrato-lifestyle estrato-science estrato-agro estrato-esg estrato-viagem estrato-culture; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "=== A5 $PORTAL_ID @ $PORTAL_WEB_ROOT ===" | tee -a "$LOG"
  portal_sync_plugins 2>&1 | tee -a "$LOG" || true
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-a5-portal-ops.php" 2>&1 | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/rebuild-portal-menu.php" 2>&1 | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/backfill-fallback-thumbnails.php" 2>&1 | tee -a "$LOG" || true
  portal_wp cache flush 2>/dev/null || true
done

echo "A5 log: $LOG"
