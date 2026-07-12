#!/usr/bin/env bash
# Sprint E1→E4 — conteúdo editorial (autores, AEO, longforms, cotações).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/sprint-e-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

PORTALS=(
  estrato-finance
  estrato-mind
  estrato-lifestyle
  estrato-science
  estrato-sustain
  estrato-culture
)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "=== E $PORTAL_ID @ $PORTAL_WEB_ROOT ===" | tee -a "$LOG"

  portal_sync_plugins 2>&1 | tee -a "$LOG" || true

  echo "--- E1 autores ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-e1-portal-authors.php" 2>&1 | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/rebuild-portal-menu.php" 2>&1 | tee -a "$LOG" || true

  echo "--- E2 AEO backfill ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-e2-portal-aeo-backfill.php" 2>&1 | tee -a "$LOG"

  echo "--- E3 longforms ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-e3-portal-longforms.php" 2>&1 | tee -a "$LOG"

  if [[ "$PORTAL_ID" == "estrato-finance" ]]; then
    echo "--- E4 cotações ---" | tee -a "$LOG"
    portal_wp eval-file "$REPO/scripts/victor/setup-sprint-e4-finance-cotacoes.php" 2>&1 | tee -a "$LOG"
  fi

  portal_wp cache flush 2>/dev/null || true
done

echo "" | tee -a "$LOG"
echo "=== Gate strict — rede ===" | tee -a "$LOG"
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG" | tail -30

echo "E log: $LOG"
