#!/usr/bin/env bash
# S7 próximos passos — longforms, GSC+, Lighthouse, schema, gate.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"

LOG="${REPO}/logs/s7-next-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

{
echo "=== S7 next steps ==="
echo "1. E3 longforms"
bash "$SCRIPT_DIR/setup-sprint-e3-all-portals.sh"

echo ""
echo "2. GSC multi-portal + cron"
bash "$SCRIPT_DIR/setup-estrato-gsc.sh"

echo ""
echo "3. Schema sample"
bash "$SCRIPT_DIR/check-schema-sample.sh" || echo "WARN: schema sample com falhas"

echo ""
echo "4. Lighthouse (PSI mobile)"
bash "$SCRIPT_DIR/check-portal-lighthouse-all.sh" || echo "WARN: Lighthouse com falhas (ver log)"

echo ""
echo "5. Gate strict"
bash "$SCRIPT_DIR/check-portal-regression-all.sh" --strict | tail -20
} 2>&1 | tee "$LOG"

echo "S7 log: $LOG"
