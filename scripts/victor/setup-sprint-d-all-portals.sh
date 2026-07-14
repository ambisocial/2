#!/usr/bin/env bash
# Sprint D1→D3 — cross-linking rede (6 portais).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/sprint-d-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

PORTALS=(
  estrato-finance
  estrato-mind
  estrato-lifestyle
  estrato-science
  estrato-agro
  estrato-esg
  estrato-viagem
  estrato-culture
)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "=== D $PORTAL_ID @ $PORTAL_WEB_ROOT ===" | tee -a "$LOG"

  portal_sync_plugins 2>&1 | tee -a "$LOG" || true

  echo "--- D1 linker ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-d1-portal-linker.php" 2>&1 | tee -a "$LOG"

  echo "--- D2 network footer ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-d2-portal-network-footer.php" 2>&1 | tee -a "$LOG"

  echo "--- D3 hub crosslinks ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-d3-portal-crosslinks.php" 2>&1 | tee -a "$LOG"

  portal_wp cache flush 2>/dev/null || true
done

echo "" | tee -a "$LOG"
echo "=== Gate strict — rede ===" | tee -a "$LOG"
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG" | tail -30

echo "D log: $LOG"
