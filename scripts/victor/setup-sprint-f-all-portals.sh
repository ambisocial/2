#!/usr/bin/env bash
# Sprint F1→F3 — SEO/AEO/GEO avançado (6 portais).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/sprint-f-$(date +%Y%m%d-%H%M%S).log"
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
  echo "=== F $PORTAL_ID @ $PORTAL_WEB_ROOT ===" | tee -a "$LOG"

  portal_sync_plugins 2>&1 | tee -a "$LOG" || true

  echo "--- F1 IndexNow + sitemaps ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-f1-portal-indexnow.php" 2>&1 | tee -a "$LOG"

  echo "--- F2 ops + relatório ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-f2-portal-ops.php" 2>&1 | tee -a "$LOG"

  if [[ "$PORTAL_ID" != "estrato-finance" ]]; then
    portal_wp eval-file "$REPO/scripts/victor/rebuild-portal-menu.php" 2>&1 | tee -a "$LOG" || true
  fi

  echo "--- F3 GEO llms-full ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-f3-portal-geo.php" 2>&1 | tee -a "$LOG"

  portal_wp cache flush 2>/dev/null || true
done

echo "" | tee -a "$LOG"
echo "=== Gate strict — rede ===" | tee -a "$LOG"
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG" | tail -30

echo "F log: $LOG"
