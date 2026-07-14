#!/usr/bin/env bash
# S7 autônomo — tudo que não depende de credenciais do usuário.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/s7-autonomous-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture)

{
echo "=== S7 autonomous ==="

echo "0. Fontes self-hosted"
bash "$SCRIPT_DIR/setup-selfhosted-fonts.sh" 2>&1 | tail -12

echo ""
echo "1. Sync plugin + perf 1.22"
bash "$SCRIPT_DIR/setup-s7-perf-all-portals.sh" 2>&1 | tail -25

echo ""
echo "2. E3 longforms (analysis ≥1500 pal)"
bash "$SCRIPT_DIR/setup-sprint-e3-all-portals.sh" 2>&1 | tail -30

echo ""
echo "2b. Fix gate strict (autor bio)"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  portal_wp eval-file "$REPO/scripts/victor/fix-gate-satellites.php" 2>&1 | tail -1 || true
done

echo ""
echo "3b. Editorial thumbnails"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  portal_wp eval-file "$REPO/scripts/victor/backfill-editorial-thumbnails.php" 2>&1 | tail -1 || true
done

echo ""
echo "3. Backfill purge (RSS sem imagem original)"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  portal_wp eval-file "$REPO/scripts/victor/backfill-fallback-thumbnails.php" 2>&1 | tail -2 || true
done

echo ""
echo "4. Schema sample"
bash "$SCRIPT_DIR/check-schema-sample.sh" || echo "WARN: schema sample"

echo ""
echo "5. Lighthouse homes"
ESTRATO_LIGHTHOUSE_MODE=local bash "$SCRIPT_DIR/check-portal-lighthouse-all.sh" 2>&1 | tail -15 || true

echo ""
echo "6. Gate strict"
bash "$SCRIPT_DIR/check-portal-regression-all.sh" --strict | tail -15

echo ""
echo "7. Longforms por portal"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  lf=$(portal_wp eval 'echo function_exists("estrato_regression_longform_count")?estrato_regression_longform_count():-1;' 2>/dev/null || echo -1)
  echo "  $PORTAL_ID longforms=$lf"
done
} 2>&1 | tee "$LOG"

echo "S7 autonomous log: $LOG"
