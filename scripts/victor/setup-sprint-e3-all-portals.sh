#!/usr/bin/env bash
# Sprint E3 — longforms analysis (≥1500 pal) em todos os portais.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/sprint-e3-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "=== E3 $PORTAL_ID ===" | tee -a "$LOG"
  portal_sync_plugins 2>&1 | tee -a "$LOG" | tail -1 || true
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-e3-portal-longforms.php" 2>&1 | tee -a "$LOG"
  portal_wp cache flush 2>/dev/null || true
done

echo "E3 log: $LOG"
