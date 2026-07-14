#!/usr/bin/env bash
# Sprint G1→G3 — Google News + syndication + hub rede (S23–S26).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG="${REPO}/logs/sprint-g-$(date +%Y%m%d-%H%M%S).log"
mkdir -p "$(dirname "$LOG")"
mkdir -p /var/log/estrato
chmod 775 /var/log/estrato 2>/dev/null || true

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

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  echo "=== G $PORTAL_ID @ $PORTAL_WEB_ROOT ===" | tee -a "$LOG"

  portal_sync_plugins 2>&1 | tee -a "$LOG" || true

  echo "--- G1 Google News ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-g1-portal-google-news.php" 2>&1 | tee -a "$LOG"

  echo "--- G2 syndication ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/setup-sprint-g2-portal-syndication.php" 2>&1 | tee -a "$LOG" || true

  if [[ "$PORTAL_ID" == "estrato-finance" ]]; then
    echo "--- G3 hub rede ---" | tee -a "$LOG"
    portal_wp eval-file "$REPO/scripts/victor/setup-sprint-g3-finance-hub.php" 2>&1 | tee -a "$LOG"
    if [[ -f "$REPO/scripts/victor/setup-estrato-syndication.sh" ]]; then
      echo "--- stack Docker (finance) ---" | tee -a "$LOG"
      bash "$REPO/scripts/victor/setup-estrato-syndication.sh" 2>&1 | tee -a "$LOG" | tail -15 || true
    fi
  fi

  echo "--- pós-G: thumbs + enrich ---" | tee -a "$LOG"
  portal_wp eval-file "$REPO/scripts/victor/backfill-fallback-thumbnails.php" 2>&1 | tee -a "$LOG" || true
  portal_wp eval-file "$REPO/scripts/victor/enrich-thin-posts.php" 2>&1 | tee -a "$LOG" || true

  portal_wp cache flush 2>/dev/null || true
done

echo "" | tee -a "$LOG"
echo "=== Gate strict — rede ===" | tee -a "$LOG"
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$LOG" | tail -30

echo "G log: $LOG"
