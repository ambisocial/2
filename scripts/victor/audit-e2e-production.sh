#!/usr/bin/env bash
# Auditoria E2E — prontidão produção da rede Estrato (6 portais).
# Uso: bash scripts/victor/audit-e2e-production.sh [--strict]
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

STRICT="${1:-}"
REPORT_DIR="${ESTRATO_REPORT_DIR:-$REPO/logs}"
mkdir -p "$REPORT_DIR"
REPORT="$REPORT_DIR/e2e-audit-$(date +%Y%m%d-%H%M%S).log"
IP="${ESTRATO_VPS_IP:-187.127.12.186}"

PASS_N=0
WARN_N=0
FAIL_N=0
GATE_RC=0
STATE_FILE=""

ok()   { echo "  ✅ $1" | tee -a "$REPORT"; PASS_N=$((PASS_N+1)); }
warn() { echo "  ⚠️  $1" | tee -a "$REPORT"; WARN_N=$((WARN_N+1)); }
fail() { echo "  ❌ $1" | tee -a "$REPORT"; FAIL_N=$((FAIL_N+1)); }

{
echo "=== ESTRATO E2E PRODUCTION AUDIT ==="
echo "Date: $(date -Iseconds)"
echo "Host: Victor $IP"
echo ""

echo "## 1. Anti-regressão strict"
set +e
bash "$REPO/scripts/victor/check-portal-regression-all.sh" --strict 2>&1 | tee -a "$REPORT" | grep -E "^(────────|→ |Portais:)" 
GATE_RC=${PIPESTATUS[0]}
set -e
if [[ "$GATE_RC" -eq 0 ]]; then ok "Gate rede 6/6 PASS"; else fail "Gate rede FAIL (exit $GATE_RC)"; fi
echo ""

echo "## 2. Conteúdo por portal"
PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  pub=$(portal_wp post list --post_status=publish --format=count 2>/dev/null || echo 0)
  thin=$(portal_wp eval 'echo function_exists("estrato_regression_thin_posts")?estrato_regression_thin_posts():-1;' 2>/dev/null || echo -1)
  thumb=$(portal_wp eval 'echo function_exists("estrato_regression_posts_without_thumbnail")?estrato_regression_posts_without_thumbnail():-1;' 2>/dev/null || echo -1)
  gn=$(portal_wp eval 'echo function_exists("estrato_google_news_recent_posts_count")?estrato_google_news_recent_posts_count():0;' 2>/dev/null || echo 0)
  echo "  $PORTAL_ID: publish=$pub thin=$thin no_thumb=$thumb news_48h=$gn"
  [[ "$thin" == "0" ]] && ok "$PORTAL_ID zero thin" || warn "$PORTAL_ID thin=$thin"
  [[ "$thumb" == "0" ]] && ok "$PORTAL_ID thumbs OK" || warn "$PORTAL_ID no_thumb=$thumb"
  [[ "$gn" -ge 1 ]] && ok "$PORTAL_ID news-sitemap 48h=$gn" || warn "$PORTAL_ID news_48h=$gn (pode ficar vazio fora de janela RSS)"
done
echo ""

echo "## 3. HTTP smoke (origem $IP)"
PATHS=(/ /robots.txt /news-sitemap.xml /sitemap_index.xml /feed/ /llms.txt /sobre/)
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  echo "  --- $PORTAL_DOMAIN ---"
  for path in "${PATHS[@]}"; do
    code=$(curl -sk -o /dev/null -w "%{http_code}" -H "Host: ${PORTAL_DOMAIN}" "https://${IP}${path}" --max-time 15 2>/dev/null || echo 000)
    if [[ "$code" == "200" ]]; then ok "$PORTAL_DOMAIN $path"; else fail "$PORTAL_DOMAIN $path HTTP $code"; fi
  done
  if [[ "$PORTAL_ID" == "estrato-finance" ]]; then
    code=$(curl -sk -o /dev/null -w "%{http_code}" -H "Host: ${PORTAL_DOMAIN}" "https://${IP}/cotacoes/" --max-time 15 2>/dev/null || echo 000)
    [[ "$code" == "200" ]] && ok "estrato.cc /cotacoes/" || fail "estrato.cc /cotacoes/ HTTP $code"
  fi
done
echo ""

echo "## 4. Syndication Docker"
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-gotosocial$'; then ok "GoToSocial container"; else warn "GoToSocial down"; fi
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-freshrss$'; then ok "FreshRSS container"; else warn "FreshRSS down"; fi
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-rss-filter$'; then ok "rss-filter container"; else warn "rss-filter down"; fi
bash "$REPO/scripts/victor/check-syndication.sh" 2>&1 | tee -a "$REPORT" | tail -5
echo ""

echo "## 5. GSC + ops"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  w=$(portal_wp eval 'echo function_exists("estrato_regression_ops_weekly_report_age_days")?estrato_regression_ops_weekly_report_age_days():-1;' 2>/dev/null || echo -1)
  c=$(portal_wp eval 'echo function_exists("estrato_regression_ops_crons_scheduled")&&estrato_regression_ops_crons_scheduled()?"1":"0";' 2>/dev/null || echo 0)
  if [[ "$w" -ge 0 && "$w" -le 8 ]]; then ok "$PORTAL_ID relatório semanal ${w}d"; else warn "$PORTAL_ID relatório semanal age=$w"; fi
  [[ "$c" == "1" ]] && ok "$PORTAL_ID crons ops" || warn "$PORTAL_ID crons ops ausentes"
done
if [[ -f /root/estrato-gsc-service-account.json \
  || -f "${GSC_SERVICE_ACCOUNT_FILE:-}" \
  || -f /root/.secrets/gsc-service-account.env \
  || -f /root/.secrets/gsc-service-account ]]; then
  ok "GSC service account presente"
elif python3 "$REPO/scripts/victor/index-bot/gsc_api.py" sites >/dev/null 2>&1; then
  ok "GSC API acessível (SA configurada)"
else
  warn "GSC SA ausente — métricas GSC manuais/slot vazio no relatório"
fi
echo ""

echo "## 6. Google News (Publisher Center — manual)"
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  ready=$(portal_wp eval '$p=get_option("estrato_google_news_readiness",[]);echo is_array($p)&&!empty($p["ready"])?"yes":"no";' 2>/dev/null || echo no)
  json=$(find "$PORTAL_WEB_ROOT/wp-content/uploads/estrato-logs" -name "google-news-*.json" 2>/dev/null | head -1)
  [[ -n "$json" ]] && ok "$PORTAL_ID checklist $json" || warn "$PORTAL_ID checklist ausente"
  [[ "$ready" == "yes" ]] && ok "$PORTAL_ID GN ready" || warn "$PORTAL_ID GN ready=no (posts 48h)"
done
echo "  📋 Publisher Center: https://publishercenter.google.com/ (aprovação humana por domínio)"
echo ""

echo "## 7. Infra"
systemctl is-active nginx >/dev/null 2>&1 && ok "nginx active" || fail "nginx inactive"
systemctl is-active php8.3-fpm >/dev/null 2>&1 && ok "php-fpm active" || warn "php-fpm status unknown"
echo ""

echo "=== VEREDITO ==="
echo "Pass checks: $PASS_N | Warn: $WARN_N | Fail: $FAIL_N"
if [[ "$FAIL_N" -eq 0 && "$GATE_RC" -eq 0 ]]; then
  echo "RESULT: PRODUCTION_READY (com ressalvas em WARN)"
elif [[ "$GATE_RC" -eq 0 ]]; then
  echo "RESULT: GATE_OK — revisar FAILs operacionais"
else
  echo "RESULT: NOT_READY — corrigir gate antes de escalar tráfego"
fi
echo "Log: $REPORT"
echo "GATE_RC=$GATE_RC" > "${REPORT}.state"
echo "FAIL_N=$FAIL_N" >> "${REPORT}.state"
echo "WARN_N=$WARN_N" >> "${REPORT}.state"
echo "PASS_N=$PASS_N" >> "${REPORT}.state"
} | tee -a "$REPORT"

# shellcheck source=/dev/null
[[ -f "${REPORT}.state" ]] && source "${REPORT}.state"
rm -f "${REPORT}.state"

if [[ "$STRICT" == "--strict" && ( "$FAIL_N" -gt 0 || "$GATE_RC" -ne 0 ) ]]; then
  exit 1
fi
exit 0
