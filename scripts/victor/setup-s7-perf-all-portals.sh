#!/usr/bin/env bash
# S7.3+ — deploy perf LCP (plugin 1.21 + nginx cache + ticker cron + Lighthouse).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/s7-perf-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture estrato-politica estrato-esporte estrato-saude estrato-educacao estrato-tech estrato-carros)

{
echo "=== S7 perf LCP ==="
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "--- $PORTAL_ID ---"
  portal_sync_plugins
  portal_wp eval 'if(function_exists("estrato_perf_lcp_status")){print_r(estrato_perf_lcp_status());}' 2>/dev/null || true
  portal_wp eval 'if(function_exists("estrato_ticker_refresh_cache")){estrato_ticker_refresh_cache();echo "ticker warmed\n";}' 2>/dev/null || true
  portal_wp cache flush 2>/dev/null || true
done

echo ""
echo "=== nginx static cache ==="
bash "$SCRIPT_DIR/setup-estrato-nginx-perf.sh" || true

systemctl reload php8.3-fpm 2>/dev/null || true

echo ""
echo "=== Lighthouse sample (finance home + single) ==="
ESTRATO_LIGHTHOUSE_MODE=local bash "$SCRIPT_DIR/check-portal-lighthouse.sh" https://estrato.cc/ || true
ESTRATO_LIGHTHOUSE_MODE=local bash "$SCRIPT_DIR/check-portal-lighthouse.sh" https://estrato.cc/eleicao-de-israel-sera-realizada-em-27-de-outubro-diz-chefe-de-coalizao/ || true

echo ""
echo "=== Gate ==="
bash "$SCRIPT_DIR/check-portal-regression-all.sh" --strict | tail -12
} 2>&1 | tee "$LOG"

echo "S7 perf log: $LOG"
