#!/usr/bin/env bash
# S7.3 — Lighthouse home + single nos 6 portais (PageSpeed Insights).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/lighthouse-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"
PASS_N=0
FAIL_N=0

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)

echo "=== Lighthouse S7.3 (mobile PSI) ===" | tee "$LOG"
echo "Meta: LCP ≤ ${ESTRATO_LCP_MAX_MS:-2500}ms · CLS ≤ ${ESTRATO_CLS_MAX:-0.1}" | tee -a "$LOG"
echo "" | tee -a "$LOG"

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  base="https://${PORTAL_DOMAIN}"
  post_url=$(portal_wp post list --post_status=publish --posts_per_page=1 --field=url 2>/dev/null || true)

  for label_url in "$base/" "$post_url"; do
    [[ -z "$label_url" || "$label_url" == "/" ]] && continue
    echo "--- $PORTAL_ID $label_url ---" | tee -a "$LOG"
    if bash "$SCRIPT_DIR/check-portal-lighthouse.sh" "$label_url" 2>&1 | tee -a "$LOG"; then
      PASS_N=$((PASS_N + 1))
    else
      FAIL_N=$((FAIL_N + 1))
    fi
    sleep 3
  done
done

echo "" | tee -a "$LOG"
echo "=== RESUMO Lighthouse ===" | tee -a "$LOG"
echo "Pass: $PASS_N | Fail: $FAIL_N" | tee -a "$LOG"
echo "Log: $LOG"
[[ "$FAIL_N" -eq 0 ]]
