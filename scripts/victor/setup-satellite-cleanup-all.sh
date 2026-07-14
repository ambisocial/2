#!/usr/bin/env bash
# Limpeza categorias legado financeiro nos satélites + dedup títulos (mind e rede).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/satellite-cleanup-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

log() { echo "[$(date -Iseconds)] $*" | tee -a "$LOG"; }

SATELLITES=(
  estrato-mind
  estrato-lifestyle
  estrato-science
  estrato-agro
  estrato-esg
  estrato-viagem
  estrato-culture
  estrato-politica
  estrato-esporte
  estrato-saude
  estrato-educacao
  estrato-tech
  estrato-carros
)

log "=== Limpeza satélites + dedup ==="

for PORTAL_ID in "${SATELLITES[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  log "--- cleanup legacy $PORTAL_ID ---"
  portal_wp eval-file "$REPO/scripts/victor/cleanup-satellite-legacy-categories.php" 2>&1 | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/dedupe-posts-by-title.php" 2>&1 | tee -a "$LOG"
  portal_wp cache flush 2>/dev/null || true
done

log "=== Dedup estrato-finance ==="
portal_resolve estrato-finance
export ESTRATO_PORTAL=estrato-finance
portal_wp eval-file "$REPO/scripts/victor/dedupe-posts-by-title.php" 2>&1 | tee -a "$LOG"
portal_wp cache flush 2>/dev/null || true

log "=== Gate strict ==="
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG" | tail -15

log "Concluído: $LOG"
