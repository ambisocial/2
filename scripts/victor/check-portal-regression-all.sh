#!/usr/bin/env bash
# Anti-regressão multi-portal — gate strict em todos os portais Estrato.
# Uso: bash scripts/victor/check-portal-regression-all.sh [--strict]
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

STRICT="${1:-}"
REPORT_DIR="${ESTRATO_REPORT_DIR:-$REPO/logs}"
mkdir -p "$REPORT_DIR"
REPORT="$REPORT_DIR/multi-regression-$(date +%Y%m%d-%H%M%S).log"

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

TOTAL=0
PASS_N=0
FAIL_N=0

echo "=== Estrato Multi-Portal Regression ===" | tee "$REPORT"
echo "Date: $(date -Iseconds)" | tee -a "$REPORT"
echo "Mode: ${STRICT:---strict}" | tee -a "$REPORT"
echo "" | tee -a "$REPORT"

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  TOTAL=$((TOTAL + 1))

  AR_YAML="$REPO/portals/estrato-anti-regression.yaml"
  if [[ "$PORTAL_ID" != "estrato-finance" ]]; then
    slug="${PORTAL_ID#estrato-}"
    if [[ -f "$REPO/portals/estrato-anti-regression-${slug}.yaml" ]]; then
      AR_YAML="$REPO/portals/estrato-anti-regression-${slug}.yaml"
    fi
  fi

  echo "──────── $PORTAL_ID ($PORTAL_DOMAIN) ────────" | tee -a "$REPORT"
  set +e
  ESTRATO_PORTAL="$PORTAL_ID" \
    WEB_ROOT="$PORTAL_WEB_ROOT" \
    ESTRATO_REPO="$REPO" \
    ESTRATO_REGRESSION_YAML="$AR_YAML" \
    bash "$REPO/scripts/victor/check-portal-regression.sh" ${STRICT:+$STRICT} 2>&1 | tee -a "$REPORT" | tail -6
  rc=${PIPESTATUS[0]}
  set -e

  if [[ "$rc" -eq 0 ]]; then
    PASS_N=$((PASS_N + 1))
    echo "→ $PORTAL_ID: PASS" | tee -a "$REPORT"
  else
    FAIL_N=$((FAIL_N + 1))
    echo "→ $PORTAL_ID: FAIL (exit $rc)" | tee -a "$REPORT"
  fi
  echo "" | tee -a "$REPORT"
done

echo "=== RESUMO REDE ===" | tee -a "$REPORT"
echo "Portais: $TOTAL | PASS: $PASS_N | FAIL: $FAIL_N" | tee -a "$REPORT"
echo "Log: $REPORT" | tee -a "$REPORT"

if [[ "$FAIL_N" -gt 0 ]]; then
  exit 1
fi
exit 0
