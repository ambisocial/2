#!/usr/bin/env bash
# Valida regras anti-regressão do portal estrato.cc
# Uso: bash scripts/victor/check-portal-regression.sh [--strict]
set -euo pipefail

DOMAIN="${ESTRATO_DOMAIN:-estrato.cc}"
BASE="https://${DOMAIN}"
# No Victor, consulta origem direta (evita CF challenge em curl servidor→servidor).
if [[ -f /var/www/estrato.cc/wp-config.php ]]; then
  BASE="https://187.127.12.186"
  CURL_HOST=( -H "Host: ${DOMAIN}" -k )
else
  CURL_HOST=()
fi
WP="${WP_CLI:-sudo -u www-data wp --path=/var/www/estrato.cc}"
STRICT="${1:-}"
YAML="${ESTRATO_REGRESSION_YAML:-/var/www/estrato/repo/portals/estrato-anti-regression.yaml}"

PASS=0
WARN=0
FAIL=0
BLOCKERS=0

ok()   { echo "  ✅ $1"; PASS=$((PASS+1)); }
warn() { echo "  ⚠️  $1"; WARN=$((WARN+1)); }
fail() { echo "  ❌ $1"; FAIL=$((FAIL+1)); }
block(){ echo "  🛑 BLOCKER: $1"; FAIL=$((FAIL+1)); BLOCKERS=$((BLOCKERS+1)); }

http_status() {
  local url="$1"
  curl -sI -o /dev/null -w "%{http_code}" --max-time 15 "${CURL_HOST[@]}" "$url" 2>/dev/null || echo "000"
}

body() {
  local follow="-L"
  if [[ -f /var/www/estrato.cc/wp-config.php ]]; then
    follow=""
  fi
  # shellcheck disable=SC2086
  curl -s $follow --max-time 20 -H "Cache-Control: no-cache" -A "EstratoRegressionCheck/1.0" "${CURL_HOST[@]}" "$1" 2>/dev/null || true
}

# No Victor, reescreve URLs estrato.cc → IP origem.
normalize_url() {
  local url="$1"
  if [[ -f /var/www/estrato.cc/wp-config.php && "$url" == https://${DOMAIN}* ]]; then
    echo "${BASE}${url#https://${DOMAIN}}"
  else
    echo "$url"
  fi
}

latest_post_url() {
  local url
  url=$($WP post list --post_type=post --post_status=publish --orderby=date --order=desc --field=url --format=csv 2>/dev/null | head -1)
  if [[ -n "$url" && "$url" == http* ]]; then
    normalize_url "$url"
    return
  fi
  curl -sL --max-time 20 "$BASE/post-sitemap.xml" 2>/dev/null \
    | grep -oE '<loc>[^<]+</loc>' | sed 's/<loc>//;s/<\/loc>//' | tail -1
}

echo "=== Estrato Anti-Regression Check ==="
echo "Domain: $BASE"
echo "Date: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo

# ─── A. ROBOTS ───────────────────────────────────────────────────
echo "## A. robots.txt"
code=$(http_status "$BASE/robots.txt")
if [[ "$code" == "200" ]]; then ok "AR-ROBOTS-001 robots.txt HTTP 200"; else block "AR-ROBOTS-001 robots.txt HTTP $code"; fi

robots=$(body "$BASE/robots.txt?nocache=$(date +%s)")
if echo "$robots" | grep -q "sitemap_index.xml"; then ok "AR-ROBOTS-002 sitemap declarado"; else block "AR-ROBOTS-002 sitemap ausente"; fi
if echo "$robots" | grep -qi "Just a moment"; then block "AR-ROBOTS-006 Cloudflare challenge em robots"; else ok "AR-ROBOTS-006 sem CF challenge"; fi
if echo "$robots" | grep -q "wp-admin"; then ok "AR-ROBOTS-003 bloqueia wp-admin"; else warn "AR-ROBOTS-003 falta Disallow wp-admin"; fi
if echo "$robots" | grep -q "?s="; then ok "AR-ROBOTS-004 bloqueia busca"; else warn "AR-ROBOTS-004 falta Disallow ?s="; fi
if echo "$robots" | grep -qi "news-sitemap"; then ok "AR-ROBOTS-005 news-sitemap declarado"; else warn "AR-ROBOTS-005 falta news-sitemap"; fi

# ─── B. SITEMAP ──────────────────────────────────────────────────
echo "## B. sitemap"
for sm in sitemap_index.xml post-sitemap.xml; do
  code=$(http_status "$BASE/$sm")
  if [[ "$code" == "200" ]]; then ok "sitemap $sm OK"; else block "sitemap $sm HTTP $code"; fi
done
code=$(http_status "$BASE/news-sitemap.xml")
if [[ "$code" == "200" ]]; then ok "AR-SITEMAP-003 news-sitemap.xml OK"; else warn "AR-SITEMAP-003 news-sitemap.xml ausente (HTTP $code)"; fi

old=$(body "$BASE/post-sitemap.xml" | grep -oE '<loc>[^<]+</loc>' | grep -c "2018\|2019\|2020\|2021\|2022\|2023" || true)
if [[ "$old" -gt 0 ]]; then warn "AR-SITEMAP-004 sitemap contém $old URLs pré-2024"; else ok "AR-SITEMAP-004 sem URLs legadas antigas"; fi

# ─── C. SCHEMA & META (home) ─────────────────────────────────────
echo "## C. schema & meta (home)"
HOME_HTML=$(mktemp)
body "$BASE/?estrato_check=$(date +%s)" > "$HOME_HTML"
if [[ ! -s "$HOME_HTML" ]]; then
  curl -s --max-time 25 -H "Cache-Control: no-cache" -A "EstratoRegressionCheck/1.0" "${CURL_HOST[@]}" "$BASE/" > "$HOME_HTML" 2>/dev/null || true
fi
if grep -Fq 'application/ld+json' "$HOME_HTML"; then ok "AR-SCHEMA home tem JSON-LD"; else block "AR-SCHEMA home sem JSON-LD"; fi
if grep -Fq 'WebSite' "$HOME_HTML"; then ok "AR-SCHEMA-001 WebSite"; else block "AR-SCHEMA-001 sem WebSite"; fi
if grep -Fq 'NewsMediaOrganization' "$HOME_HTML" || grep -Fq 'Organization' "$HOME_HTML"; then ok "AR-SCHEMA-002 Organization"; else block "AR-SCHEMA-002 sem Organization"; fi
if grep -Fq 'NewsMediaOrganization' "$HOME_HTML"; then ok "AR-SCHEMA-003 NewsMediaOrganization"; else warn "AR-SCHEMA-003 preferir NewsMediaOrganization"; fi
if grep -Fq 'Свързани' "$HOME_HTML" || grep -Fq 'Сподели:' "$HOME_HTML"; then block "AR-VISUAL-003 strings búlgaras na home"; else ok "AR-VISUAL-003 sem búlgaro"; fi
rm -f "$HOME_HTML"

# ─── D. SCHEMA & META (post) ─────────────────────────────────────
echo "## D. schema & meta (último post)"
POST_URL=$(latest_post_url)
if [[ -z "$POST_URL" ]]; then
  block "Nenhum post publicado"
else
  POST_HTML=$(mktemp)
  body "$POST_URL" > "$POST_HTML"
  if grep -Fq 'NewsArticle' "$POST_HTML" || grep -Fq '@type":"Article"' "$POST_HTML"; then ok "AR-SCHEMA-004 NewsArticle/Article em $POST_URL"; else block "AR-SCHEMA-004 sem NewsArticle"; fi
  if grep -Fq '"Person"' "$POST_HTML" || grep -Fq '@type":"Person"' "$POST_HTML"; then ok "AR-SCHEMA-005 Person/autor"; else warn "AR-SCHEMA-005 sem Person"; fi
  na=$(grep -c 'NewsArticle' "$POST_HTML" 2>/dev/null || echo 0)
  ar=$(grep -c '@type":"Article"' "$POST_HTML" 2>/dev/null || echo 0)
  if [[ "$na" -gt 0 && "$ar" -gt 0 ]]; then warn "AR-SCHEMA-006 schema Article+NewsArticle duplicado"; else ok "AR-SCHEMA-006 sem duplicação crítica"; fi
  if grep -qE 'rel=["'\'']canonical["'\'']' "$POST_HTML"; then ok "AR-SCHEMA-007 canonical"; else block "AR-SCHEMA-007 sem canonical"; fi
  if grep -qE 'property=["'\'']og:title["'\'']' "$POST_HTML"; then ok "AR-SCHEMA-008 og:title"; else block "AR-SCHEMA-008 sem og:title"; fi
  if grep -q 'article:published_time' "$POST_HTML"; then ok "AR-SCHEMA-009 article:published_time"; else warn "AR-SCHEMA-009 sem article:published_time"; fi
  rm -f "$POST_HTML"
fi

cat_code=$(http_status "$BASE/category/economia/")
cat_html=$(body "$BASE/category/economia/?nocache=$(date +%s)")
if echo "$cat_html" | grep -qi "Archives"; then warn "AR-SCHEMA-010 title categoria com 'Archives'"; else ok "AR-SCHEMA-010 title categoria pt_BR"; fi

# ─── E. E-E-A-T ──────────────────────────────────────────────────
echo "## E. E-E-A-T / institucional"
for path in sobre politica-editorial contato politica-de-privacidade; do
  code=$(http_status "$BASE/$path/")
  if [[ "$code" == "200" ]]; then ok "AR-EEAT página /$path/ OK"; else warn "AR-EEAT página /$path/ HTTP $code (esperado 200)"; fi
done

if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  users=$($WP user list --format=count 2>/dev/null || echo 0)
  if [[ "$users" -ge 7 ]]; then ok "AR-EEAT-005 $users usuários"; else warn "AR-EEAT-005 apenas $users usuários (meta: 7+)"; fi
  sem=$($WP post list --category_name=sem-categoria --post_status=publish --format=count 2>/dev/null || echo 0)
  if [[ "$sem" -eq 0 ]]; then ok "AR-CONTENT-003 zero sem-categoria"; else warn "AR-CONTENT-003 $sem posts sem-categoria"; fi
  logo=$($WP eval 'echo (int) get_theme_mod("custom_logo");' 2>/dev/null || echo "")
  if [[ -n "$logo" && "$logo" != "0" ]]; then ok "AR-VISUAL-001 custom_logo=$logo"; else block "AR-VISUAL-001 sem custom_logo"; fi
  accent=$($WP eval 'echo (string) get_theme_mod("pressgrid_accent_color");' 2>/dev/null || echo "")
  if [[ "$accent" == "#9AFF33" || "$accent" == "#9aff33" ]]; then ok "AR-VISUAL-002 accent $accent"; else warn "AR-VISUAL-002 accent=$accent (esperado #9AFF33)"; fi
else
  warn "WP-CLI indisponível — pulando checks WordPress"
fi

# ─── F. NAVEGAÇÃO & AEO ──────────────────────────────────────────
echo "## F. navegação & AEO"
code=$(http_status "$BASE/feed/")
if [[ "$code" == "200" ]]; then ok "AR-NAV-004 RSS feed OK"; else warn "AR-NAV-004 RSS HTTP $code"; fi
code=$(http_status "$BASE/llms.txt")
if [[ "$code" == "200" ]]; then ok "AR-AEO-001 llms.txt OK"; else warn "AR-AEO-001 llms.txt ausente"; fi

hubs_ok=0
for hub in selic ibovespa dolar; do
  c=$(http_status "$BASE/tudo-sobre/$hub/")
  [[ "$c" == "200" ]] && hubs_ok=$((hubs_ok+1))
done
if [[ "$hubs_ok" -ge 3 ]]; then ok "AR-NAV-003 $hubs_ok hubs /tudo-sobre/"; else warn "AR-NAV-003 apenas $hubs_ok/3 hubs"; fi

# ─── G. PERFORMANCE ──────────────────────────────────────────────
echo "## G. performance & segurança"
code=$(http_status "$BASE/")
if [[ "$code" == "200" ]]; then ok "AR-PERF-001 HTTPS 200"; else block "AR-PERF-001 HTTPS $code"; fi
hdr=$(curl -sI --max-time 10 "$BASE/" | grep -i "x-content-type-options" || true)
if echo "$hdr" | grep -qi nosniff; then ok "AR-PERF-002 X-Content-Type-Options"; else warn "AR-PERF-002 sem nosniff"; fi

# ─── RESUMO ─────────────────────────────────────────────────────
echo
echo "=== RESUMO ==="
echo "Pass: $PASS | Warn: $WARN | Fail: $FAIL | Blockers: $BLOCKERS"

if [[ "$BLOCKERS" -gt 0 ]]; then
  echo "RESULT: FAIL (blockers encontrados)"
  exit 1
fi
if [[ "$FAIL" -gt 0 && "$STRICT" == "--strict" ]]; then
  echo "RESULT: FAIL (strict mode)"
  exit 1
fi
if [[ "$WARN" -gt 0 ]]; then
  echo "RESULT: PASS WITH WARNINGS"
  exit 0
fi
echo "RESULT: PASS"
exit 0
