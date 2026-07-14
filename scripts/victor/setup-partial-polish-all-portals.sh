#!/usr/bin/env bash
# Correções parciais pós-auditoria — sameAs, AEO estáticos, colunistas, MSN.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/partial-polish-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture estrato-politica estrato-esporte estrato-saude estrato-educacao estrato-tech estrato-carros)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "=== polish $PORTAL_ID ===" | tee -a "$LOG"
  portal_sync_plugins 2>&1 | tee -a "$LOG" | tail -2
  portal_wp eval-file "$REPO/scripts/victor/setup-p1-yoast-social.php" 2>&1 | tee -a "$LOG"
  portal_wp eval 'if(function_exists("estrato_aeo_deploy_static_files")){print_r(estrato_aeo_deploy_static_files());}' 2>&1 | tee -a "$LOG" || true
  portal_wp eval-file "$REPO/scripts/victor/setup-s7-colunistas-page.php" 2>&1 | tee -a "$LOG" || true
  portal_wp cache flush 2>/dev/null || true
done

echo "--- MSN feed finance ---" | tee -a "$LOG"
python3 "$REPO/scripts/victor/syndicate-outbound.py" --generate-msn-feed --msn-limit 50 2>&1 | tee -a "$LOG" | tail -5

systemctl reload php8.3-fpm 2>/dev/null || true
bash "$SCRIPT_DIR/check-outbound-aggregators.sh" 2>&1 | tee -a "$LOG" | tail -8
echo "Log: $LOG"
