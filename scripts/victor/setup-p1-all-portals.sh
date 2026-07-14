#!/usr/bin/env bash
# P1 auditoria UI/UX — deploy plugins + Yoast OG + gate.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/p1-audit-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture estrato-politica estrato-esporte estrato-saude estrato-educacao estrato-tech estrato-carros)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "=== P1 $PORTAL_ID ===" | tee -a "$LOG"
  portal_sync_plugins 2>&1 | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-p1-yoast-social.php" 2>&1 | tee -a "$LOG" || true
  portal_wp cache flush 2>/dev/null || true
done

systemctl reload php8.3-fpm 2>/dev/null || true
bash "$SCRIPT_DIR/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG" | tail -15
echo "P1 log: $LOG"
