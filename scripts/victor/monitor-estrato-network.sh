#!/usr/bin/env bash
# Monitoramento consolidado — Publisher Center, syndication Docker, GSC.
# Agendar no Victor: 0 7 * * 1  bash /var/www/estrato/repo/scripts/victor/monitor-estrato-network.sh
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

LOG_DIR="${ESTRATO_REPORT_DIR:-$REPO/logs}"
mkdir -p "$LOG_DIR" /var/log/estrato
LOG="$LOG_DIR/monitor-$(date +%Y%m%d-%H%M%S).log"
ALERT=0

log() { echo "[$(date -Iseconds)] $*" | tee -a "$LOG"; }
alert() { log "ALERT: $*"; ALERT=1; }

PORTALS=(
  estrato-finance
  estrato-mind
  estrato-lifestyle
  estrato-science
  estrato-sustain
  estrato-culture
)

log "=== Monitor rede Estrato ==="

# ─── 1. Gate rápido ─────────────────────────────────────────────
set +e
OUT=$(bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1)
GATE_RC=$?
set -e
echo "$OUT" | grep -E "Portais:|PASS|FAIL" | tee -a "$LOG"
if [[ "$GATE_RC" -ne 0 ]]; then
  echo "$OUT" | grep -E "🛑|⚠️|FAIL" | head -20 | tee -a "$LOG"
  alert "Gate strict falhou — executar hotfix (backfill thumbs, enrich, a2, trash-pre2024)"
fi

# ─── 2. Google News / Publisher Center ──────────────────────────
log "--- Google News readiness ---"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  recent=$(portal_wp eval 'echo function_exists("estrato_google_news_recent_posts_count")?estrato_google_news_recent_posts_count():0;' 2>/dev/null || echo 0)
  pre2024=$(portal_wp eval 'echo function_exists("estrato_regression_pre2024_posts")?estrato_regression_pre2024_posts():0;' 2>/dev/null || echo 0)
  log "$PORTAL_ID news_48h=$recent pre2024=$pre2024"
  if [[ "$recent" -lt 1 ]]; then
    alert "$PORTAL_ID: news-sitemap pode ficar vazio (>48h sem post)"
  fi
  if [[ "$pre2024" -gt 0 ]]; then
    alert "$PORTAL_ID: $pre2024 posts pré-2024 — rodar trash-pre2024-posts.php"
  fi
done
log "Publisher Center (manual): https://publishercenter.google.com/"
log "Checklists: wp-content/uploads/estrato-logs/google-news-*.json"

# ─── 3. Syndication Docker ───────────────────────────────────────
log "--- Syndication Docker ---"
for c in estrato-gotosocial estrato-freshrss estrato-rss-filter estrato-masto-rss; do
  if docker ps --format '{{.Names}}' 2>/dev/null | grep -q "^${c}$"; then
    log "OK container $c"
  else
    alert "Container $c não está rodando — bash scripts/victor/setup-estrato-syndication.sh"
  fi
done
bash "$REPO/scripts/victor/check-syndication.sh" 2>&1 | tee -a "$LOG" | tail -8

for f in /var/log/estrato/syndicate-*.log; do
  [[ -f "$f" ]] || continue
  age=$(( $(date +%s) - $(stat -c %Y "$f" 2>/dev/null || echo 0) ))
  lines=$(wc -l < "$f" 2>/dev/null || echo 0)
  log "syndicate log $(basename "$f"): ${lines} lines, age ${age}s"
  if [[ "$lines" -eq 0 && "$age" -gt 86400 ]]; then
    alert "$(basename "$f") vazio há >24h"
  fi
done

# ─── 4. GSC / relatórios semanais ───────────────────────────────
log "--- GSC / ops ---"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  w=$(portal_wp eval 'echo function_exists("estrato_regression_ops_weekly_report_age_days")?estrato_regression_ops_weekly_report_age_days():-1;' 2>/dev/null || echo -1)
  path=$(portal_wp option get estrato_weekly_report_path 2>/dev/null || echo "")
  log "$PORTAL_ID weekly_report_age=${w}d path=${path:-none}"
  if [[ "$w" -lt 0 || "$w" -gt 10 ]]; then
    alert "$PORTAL_ID: relatório semanal ausente ou antigo (${w}d) — wp eval-file report-gsc-weekly.php"
  fi
done
if [[ ! -f /root/.secrets/gsc-service-account.env && ! -f /root/.secrets/gsc-service-account ]]; then
  log "INFO: GSC SA não configurado — preencher estrato_gsc_manual_metrics ou instalar SA"
fi

# ─── 5. Auto-remediação leve ────────────────────────────────────
if [[ "$ALERT" -eq 1 && "${ESTRATO_MONITOR_AUTOFIX:-0}" == "1" ]]; then
  log "--- Autofix leve (thumbs + enrich) ---"
  for PORTAL_ID in "${PORTALS[@]}"; do
    portal_resolve "$PORTAL_ID"
    export ESTRATO_PORTAL="$PORTAL_ID"
    portal_wp eval-file "$REPO/scripts/victor/backfill-fallback-thumbnails.php" 2>&1 | tail -1 | tee -a "$LOG"
    portal_wp eval-file "$REPO/scripts/victor/enrich-thin-posts.php" 2>&1 | tail -1 | tee -a "$LOG"
  done
fi

log "=== Fim monitor (alert=$ALERT) ==="
log "Log: $LOG"
exit "$ALERT"
