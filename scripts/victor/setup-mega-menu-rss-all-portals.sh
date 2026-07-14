#!/usr/bin/env bash
# Mega menu + saúde RSS nos 6 portais Victor.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/mega-menu-rss-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"

PORTALS=(
  estrato-finance
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

log() { echo "[$(date -Iseconds)] $*" | tee -a "$LOG"; }

log "=== Mega menu + RSS health — 6 portais ==="

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  log "--- $PORTAL_ID @ $PORTAL_WEB_ROOT preset=$PORTAL_PRESET ---"
  portal_sync_plugins 2>&1 | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/apply-portal-config.php" 2>&1 | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint8-portal-layout.php" 2>&1 | tee -a "$LOG" || true
  portal_wp eval-file "$REPO/scripts/victor/setup-portal-rss-health.php" 2>&1 | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/backfill-fallback-thumbnails.php" 2>&1 | tee -a "$LOG" || true
  portal_wp eval-file "$REPO/scripts/victor/enrich-thin-posts.php" 2>&1 | tee -a "$LOG" || true
  portal_wp cache flush 2>/dev/null || true
done

log "=== Gate strict rede ==="
if [[ -f "$REPO/scripts/victor/check-portal-regression-all.sh" ]]; then
  bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG" || true
fi

log "Concluído. Log: $LOG"
