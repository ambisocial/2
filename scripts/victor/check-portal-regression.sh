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
  na=$( (grep -o 'NewsArticle' "$POST_HTML" 2>/dev/null || true) | wc -l | tr -d ' ')
  ar=$( (grep -o '@type":"Article"' "$POST_HTML" 2>/dev/null || true) | wc -l | tr -d ' ')
  if [[ "$na" -gt 0 && "$ar" -gt 0 ]]; then warn "AR-SCHEMA-006 schema Article+NewsArticle duplicado"; else ok "AR-SCHEMA-006 sem duplicação crítica"; fi
  if grep -qE 'rel=["'\'']canonical["'\'']' "$POST_HTML"; then ok "AR-SCHEMA-007 canonical"; else block "AR-SCHEMA-007 sem canonical"; fi
  if grep -qE 'property=["'\'']og:title["'\'']' "$POST_HTML"; then ok "AR-SCHEMA-008 og:title"; else block "AR-SCHEMA-008 sem og:title"; fi
  if grep -q 'article:published_time' "$POST_HTML"; then ok "AR-SCHEMA-009 article:published_time"; else warn "AR-SCHEMA-009 sem article:published_time"; fi
  rm -f "$POST_HTML"
fi

cat_code=$(http_status "$BASE/category/economia/")
cat_html=$(body "$BASE/category/economia/?nocache=$(date +%s)")
cat_title=$(echo "$cat_html" | grep -oiE '<title[^>]*>[^<]+</title>' | head -1)
if echo "$cat_title" | grep -qi "Archives"; then warn "AR-SCHEMA-010 title categoria com 'Archives'"; else ok "AR-SCHEMA-010 title categoria pt_BR"; fi

# ─── E. E-E-A-T ──────────────────────────────────────────────────
echo "## E. E-E-A-T / institucional"
for path in sobre politica-editorial contato politica-de-privacidade; do
  code=$(http_status "$BASE/$path/")
  if [[ "$code" == "200" ]]; then ok "AR-EEAT página /$path/ OK"; else block "AR-EEAT-$path HTTP $code (esperado 200)"; fi
done

if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  users=$($WP user list --format=count 2>/dev/null || echo 0)
  real_authors=$($WP user list --role=author --format=count 2>/dev/null || echo 0)
  if [[ "$users" -ge 7 && "$real_authors" -ge 7 ]]; then ok "AR-EEAT-005 $users usuários ($real_authors autores)"; else warn "AR-EEAT-005 $users usuários / $real_authors autores (meta: 7+)"; fi
  no_bio=$($WP eval 'echo function_exists("estrato_regression_authors_without_bio") ? estrato_regression_authors_without_bio() : -1;' 2>/dev/null || echo -1)
  if [[ "$no_bio" == "0" ]]; then ok "AR-EEAT-006 todos autores com bio"; elif [[ "$no_bio" -ge 0 ]]; then warn "AR-EEAT-006 $no_bio posts de autores sem bio"; else warn "AR-EEAT-006 função indisponível"; fi
  sem=$($WP post list --category_name=sem-categoria --post_status=publish --format=count 2>/dev/null || echo 0)
  if [[ "$sem" -eq 0 ]]; then ok "AR-CONTENT-003 zero sem-categoria"; else warn "AR-CONTENT-003 $sem posts sem-categoria"; fi
  thin=$($WP eval 'echo function_exists("estrato_regression_thin_posts") ? estrato_regression_thin_posts() : -1;' 2>/dev/null || echo -1)
  if [[ "$thin" == "0" ]]; then ok "AR-CONTENT-001 zero posts <200 palavras"; elif [[ "$thin" -ge 0 ]]; then block "AR-CONTENT-001 $thin posts <200 palavras"; else warn "AR-CONTENT-001 helper indisponível"; fi
  ratio=$($WP eval 'echo function_exists("estrato_regression_word_ratio") ? estrato_regression_word_ratio() : -1;' 2>/dev/null || echo -1)
  if awk -v r="$ratio" 'BEGIN{exit !(r>=0.80)}' 2>/dev/null; then ok "AR-CONTENT-002 ratio 300+ palavras=$ratio"; else warn "AR-CONTENT-002 ratio 300+=$ratio (meta 0.80)"; fi
  no_thumb=$($WP eval 'echo function_exists("estrato_regression_posts_without_thumbnail") ? estrato_regression_posts_without_thumbnail() : -1;' 2>/dev/null || echo -1)
  if [[ "$no_thumb" == "0" ]]; then ok "AR-CONTENT-004 todos com featured image"; elif [[ "$no_thumb" -ge 0 ]]; then block "AR-CONTENT-004 $no_thumb posts sem thumbnail"; else warn "AR-CONTENT-004 helper indisponível"; fi
  no_src=$($WP eval 'echo function_exists("estrato_regression_pipeline_without_source") ? estrato_regression_pipeline_without_source() : -1;' 2>/dev/null || echo -1)
  if [[ "$no_src" -le 5 && "$no_src" -ge 0 ]]; then ok "AR-CONTENT-005 pipeline sem source=$no_src"; else warn "AR-CONTENT-005 pipeline sem source=$no_src (meta ≤5)"; fi
  logo=$($WP eval 'echo (int) get_theme_mod("custom_logo");' 2>/dev/null || echo "")
  if [[ -n "$logo" && "$logo" != "0" ]]; then ok "AR-VISUAL-001 custom_logo=$logo"; else block "AR-VISUAL-001 sem custom_logo"; fi
  accent=$($WP eval 'echo (string) get_theme_mod("pressgrid_accent_color");' 2>/dev/null || echo "")
  if [[ "$accent" == "#9AFF33" || "$accent" == "#9aff33" ]]; then ok "AR-VISUAL-002 accent $accent"; else warn "AR-VISUAL-002 accent=$accent (esperado #9AFF33)"; fi
else
  warn "WP-CLI indisponível — pulando checks WordPress"
fi

# ─── F. NAVEGAÇÃO & AEO ──────────────────────────────────────────
echo "## F. navegação & AEO"
if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  primary=$($WP eval '$l=get_theme_mod("nav_menu_locations"); echo isset($l["primary"]) ? (int)$l["primary"] : 0;' 2>/dev/null || echo 0)
  if [[ -n "$primary" && "$primary" != "0" ]]; then ok "AR-NAV-001 menu primary=$primary"; else block "AR-NAV-001 sem menu primary"; fi
  menu_count=$($WP menu item list estrato-principal --format=count 2>/dev/null || echo 0)
  if [[ "$menu_count" -ge 8 ]]; then ok "AR-NAV-002 menu $menu_count itens"; else block "AR-NAV-002 menu apenas $menu_count itens (meta 8+)"; fi
  breaking=$($WP eval 'echo (int) get_theme_mod("pressgrid_breaking_news_category");' 2>/dev/null || echo 0)
  forex=$($WP eval 'echo get_theme_mod("pressgrid_forex_force") ? "1" : "0";' 2>/dev/null || echo 0)
  if [[ -n "$breaking" && "$breaking" != "0" ]] || [[ "$forex" == "1" ]]; then ok "AR-VISUAL-004 ticker/forex ativo"; else warn "AR-VISUAL-004 sem ticker mercados/forex"; fi
else
  warn "WP-CLI indisponível — pulando AR-NAV-001/002"
fi

code=$(http_status "$BASE/feed/")
if [[ "$code" == "200" ]]; then ok "AR-NAV-004 RSS feed OK"; else warn "AR-NAV-004 RSS HTTP $code"; fi
code=$(http_status "$BASE/llms.txt")
if [[ "$code" == "200" ]]; then ok "AR-AEO-001 llms.txt OK"; else warn "AR-AEO-001 llms.txt ausente"; fi

hubs_ok=0
for hub in selic ibovespa dolar cripto inflacao tributacao agronegocio; do
  c=$(http_status "$BASE/tudo-sobre/$hub/")
  [[ "$c" == "200" ]] && hubs_ok=$((hubs_ok+1))
done
if [[ "$hubs_ok" -ge 7 ]]; then ok "AR-NAV-003 $hubs_ok/7 hubs /tudo-sobre/"; elif [[ "$hubs_ok" -ge 3 ]]; then ok "AR-NAV-003 $hubs_ok hubs /tudo-sobre/ (mín 3)"; else warn "AR-NAV-003 apenas $hubs_ok/7 hubs"; fi
code=$(http_status "$BASE/cotacoes/")
if [[ "$code" == "200" ]]; then ok "AR-NAV página /cotacoes/ OK"; else warn "AR-NAV /cotacoes/ HTTP $code"; fi

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
