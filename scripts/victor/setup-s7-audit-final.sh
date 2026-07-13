#!/usr/bin/env bash
# Auditoria final S7 — self-hosted + itens autônomos da auditoria UI/UX.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"

LOG="${REPO}/logs/s7-audit-final-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

{
echo "=== S7 AUDIT FINAL (self-hosted) ==="

echo "1. Fontes self-hosted"
bash "$SCRIPT_DIR/setup-selfhosted-fonts.sh"

echo ""
echo "2. Perf + plugins 1.23"
bash "$SCRIPT_DIR/setup-s7-perf-all-portals.sh" 2>&1 | tail -20

echo ""
echo "3. Newsletter secrets (se existir)"
bash "$SCRIPT_DIR/setup-newsletter-from-secrets.sh" || true

echo ""
echo "4. GSC bot + cobertura"
bash "$SCRIPT_DIR/setup-estrato-gsc.sh" 2>&1 | tail -15 || echo "WARN: GSC"
python3 "$SCRIPT_DIR/check-gsc-coverage.py" || true

echo ""
echo "5. Schema + Rich Results (S7.4)"
bash "$SCRIPT_DIR/check-schema-sample.sh"
bash "$SCRIPT_DIR/check-rich-results-sample.sh" || echo "WARN: rich results"

echo ""
echo "6. E2E + Gate"
bash "$SCRIPT_DIR/audit-e2e-production.sh" --strict 2>&1 | tail -25

echo ""
echo "7. Lighthouse sample"
ESTRATO_LIGHTHOUSE_MODE=local bash "$SCRIPT_DIR/check-portal-lighthouse-all.sh" 2>&1 | tail -12 || true

echo ""
echo "8. Runbook"
bash "$SCRIPT_DIR/runbook-production.sh" | head -20

} 2>&1 | tee "$LOG"

echo "S7 audit final log: $LOG"
