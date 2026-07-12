#!/usr/bin/env bash
# Sprint B1→B3 — estrato.cc finance (G1 + RSS).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WEB="${WEB_ROOT:-/var/www/estrato.cc}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

portal_resolve estrato-finance
export ESTRATO_PORTAL=estrato-finance
PORTAL_WEB_ROOT="$WEB"

LOG="${REPO}/logs/sprint-b-finance-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

run_b() {
  local step="$1"
  local file="$2"
  echo "=== $step ===" | tee -a "$LOG"
  if [[ "$step" == "B1 conteúdo" ]]; then
    portal_sync_plugins 2>&1 | tee -a "$LOG" || true
  fi
  portal_wp eval-file "$REPO/scripts/victor/$file" 2>&1 | tee -a "$LOG"
  portal_wp cache flush 2>/dev/null || true
}

run_b "B1 conteúdo" "setup-sprint-b1-finance-content.php"
run_b "B2 legado" "setup-sprint-b2-finance-legacy.php"
run_b "B3 G1+RSS" "setup-sprint-b3-finance-g1.php"

echo "=== Gate strict ===" | tee -a "$LOG"
sudo env ESTRATO_PORTAL=estrato-finance WEB_ROOT="$WEB" ESTRATO_REPO="$REPO" \
  bash "$REPO/scripts/victor/check-portal-regression.sh" --strict 2>&1 | tee -a "$LOG" | tail -15

echo "B log: $LOG"
