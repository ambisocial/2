#!/usr/bin/env bash
# Valida regras anti-regressão por portal (finance ou satélite).
# Uso: bash scripts/victor/check-portal-regression.sh [--strict]
#      ESTRATO_PORTAL=estrato-mind WEB_ROOT=/var/www/mente.estrato.cc bash ...
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PORTAL_ID="${ESTRATO_PORTAL:-estrato-finance}"
DOMAIN="${ESTRATO_DOMAIN:-estrato.cc}"
WEB_ROOT="${WEB_ROOT:-/var/www/estrato.cc}"

if [[ -f "$SCRIPT_DIR/lib/portal-env.sh" && -f "${ESTRATO_REPO:-/var/www/estrato/repo}/portals/${PORTAL_ID}.yaml" ]]; then
  # shellcheck source=/dev/null
  source "$SCRIPT_DIR/lib/portal-env.sh"
  portal_resolve "$PORTAL_ID"
  DOMAIN="$PORTAL_DOMAIN"
  WEB_ROOT="$PORTAL_WEB_ROOT"
fi

IS_FINANCE=0
[[ "$PORTAL_ID" == "estrato-finance" ]] && IS_FINANCE=1

PORTAL_YAML="${ESTRATO_REPO:-/var/www/estrato/repo}/portals/${PORTAL_ID}.yaml"
SATELLITE_CATS=()
if [[ $IS_FINANCE -eq 0 && -f "$PORTAL_YAML" ]]; then
  while IFS= read -r line; do
    [[ -n "$line" ]] && SATELLITE_CATS+=( "$line" )
  done < <(awk '/^categories:/{f=1;next} f && /^  - /{print $2; next} f && /^[^ #]/{exit}' "$PORTAL_YAML")
fi
SATELLITE_SAMPLE_CAT="${SATELLITE_CATS[0]:-aprendizado-cognicao}"

BASE="https://${DOMAIN}"
# No Victor, consulta origem direta (evita CF challenge em curl servidor→servidor).
if [[ -f "${WEB_ROOT}/wp-config.php" ]]; then
  BASE="https://187.127.12.186"
  CURL_HOST=( -H "Host: ${DOMAIN}" -k )
else
  CURL_HOST=()
fi
WP="${WP_CLI:-sudo -u www-data wp --path=$WEB_ROOT}"
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
  local timeout="${2:-15}"
  curl -sI -o /dev/null -w "%{http_code}" --max-time "$timeout" "${CURL_HOST[@]}" "$url" 2>/dev/null || echo "000"
}

body() {
  local follow="-L"
  if [[ -f "${WEB_ROOT}/wp-config.php" ]]; then
    follow=""
  fi
  # shellcheck disable=SC2086
  curl -s $follow --max-time 20 -H "Cache-Control: no-cache" -A "EstratoRegressionCheck/1.0" "${CURL_HOST[@]}" "$1" 2>/dev/null || true
}

# No Victor, reescreve URLs estrato.cc → IP origem.
normalize_url() {
  local url="$1"
  if [[ -f "${WEB_ROOT}/wp-config.php" && "$url" == https://${DOMAIN}* ]]; then
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
echo "Portal: $PORTAL_ID | Domain: $DOMAIN | Web: $WEB_ROOT"
echo "Domain: $BASE"
echo "Date: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo

# ─── A. ROBOTS ───────────────────────────────────────────────────
echo "## A. robots.txt"
ROBOTS_TIMEOUT=15
[[ $IS_FINANCE -eq 1 ]] && ROBOTS_TIMEOUT=30
code=$(http_status "$BASE/robots.txt" "$ROBOTS_TIMEOUT")
if [[ "$code" == "200" ]]; then ok "AR-ROBOTS-001 robots.txt HTTP 200"; else block "AR-ROBOTS-001 robots.txt HTTP $code"; fi

robots=$(body "$BASE/robots.txt?nocache=$(date +%s)")
if echo "$robots" | grep -q "sitemap_index.xml"; then ok "AR-ROBOTS-002 sitemap declarado"; else block "AR-ROBOTS-002 sitemap ausente"; fi
if echo "$robots" | grep -qi "Just a moment"; then block "AR-ROBOTS-006 Cloudflare challenge em robots"; else ok "AR-ROBOTS-006 sem CF challenge"; fi
if echo "$robots" | grep -q "wp-admin"; then ok "AR-ROBOTS-003 bloqueia wp-admin"; else warn "AR-ROBOTS-003 falta Disallow wp-admin"; fi
if echo "$robots" | grep -q "?s="; then ok "AR-ROBOTS-004 bloqueia busca"; else warn "AR-ROBOTS-004 falta Disallow ?s="; fi
if echo "$robots" | grep -qi "news-sitemap"; then ok "AR-ROBOTS-005 news-sitemap declarado"; else warn "AR-ROBOTS-005 falta news-sitemap"; fi

# ─── B. SITEMAP ──────────────────────────────────────────────────
echo "## B. sitemap"
SM_TIMEOUT=15
[[ $IS_FINANCE -eq 1 ]] && SM_TIMEOUT=45
for sm in sitemap_index.xml post-sitemap.xml; do
  code=$(http_status "$BASE/$sm" "$SM_TIMEOUT")
  if [[ "$code" == "200" ]]; then ok "sitemap $sm OK"; else block "sitemap $sm HTTP $code"; fi
done
code=$(http_status "$BASE/news-sitemap.xml" "$SM_TIMEOUT")
if [[ "$code" == "200" ]]; then ok "AR-SITEMAP-003 news-sitemap.xml OK"; else warn "AR-SITEMAP-003 news-sitemap.xml ausente (HTTP $code)"; fi

# AR-SITEMAP-004: posts publicados antes de 2024 (não anos em slugs como "messi-2022").
old=0
if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  old=$($WP eval 'echo function_exists("estrato_regression_pre2024_posts") ? estrato_regression_pre2024_posts() : -1;' 2>/dev/null || echo -1)
  if [[ "$old" -lt 0 ]]; then
    old=$($WP eval 'echo count(get_posts(array("post_type"=>"post","post_status"=>"publish","posts_per_page"=>-1,"fields"=>"ids","date_query"=>array(array("before"=>"2024-01-01 00:00:00","inclusive"=>false,"column"=>"post_date")))));' 2>/dev/null || echo 0)
  fi
else
  old=$(body "$BASE/post-sitemap.xml" | grep -oE '<loc>[^<]+</loc>' | sed 's/<loc>//;s/<\/loc>//' \
    | grep -cE '/(201[89]|202[0-3])(/|$)' || true)
fi
if [[ "$old" -gt 0 ]]; then warn "AR-SITEMAP-004 $old posts pré-2024 ainda publicados"; else ok "AR-SITEMAP-004 sem posts legados pré-2024"; fi

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
# HOME_HTML retained for AR-MOBILE-* (cleaned up at end)

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

cat_code=$(http_status "$BASE/category/$( [[ $IS_FINANCE -eq 1 ]] && echo economia || echo "$SATELLITE_SAMPLE_CAT" )/")
cat_html=$(body "$BASE/category/$( [[ $IS_FINANCE -eq 1 ]] && echo economia || echo "$SATELLITE_SAMPLE_CAT" )/?nocache=$(date +%s)")
cat_title=$(echo "$cat_html" | grep -oiE '<title[^>]*>[^<]+</title>' | head -1)
if echo "$cat_title" | grep -qi "Archives"; then warn "AR-SCHEMA-010 title categoria com 'Archives'"; else ok "AR-SCHEMA-010 title categoria pt_BR"; fi

# ─── E. E-E-A-T ──────────────────────────────────────────────────
echo "## E. E-E-A-T / institucional"
for path in sobre politica-editorial contato; do
  code=$(http_status "$BASE/$path/")
  if [[ "$code" == "200" ]]; then ok "AR-EEAT página /$path/ OK"; else block "AR-EEAT-$path HTTP $code (esperado 200)"; fi
done
priv_code=$(http_status "$BASE/privacidade/")
pol_code=$(http_status "$BASE/politica-de-privacidade/")
if [[ "$priv_code" == "200" || "$pol_code" == "200" ]]; then ok "AR-EEAT página privacidade OK"; else block "AR-EEAT-privacidade HTTP $priv_code/$pol_code (esperado 200)"; fi

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
  if [[ $IS_FINANCE -eq 1 ]]; then
    if [[ "$accent" == "#9AFF33" || "$accent" == "#9aff33" ]]; then ok "AR-VISUAL-002 accent $accent"; else warn "AR-VISUAL-002 accent=$accent (esperado #9AFF33)"; fi
  elif [[ -n "$accent" && "$accent" != "0" ]]; then ok "AR-VISUAL-002 accent $accent"; else warn "AR-VISUAL-002 accent não configurado"; fi
else
  warn "WP-CLI indisponível — pulando checks WordPress"
fi

# ─── F. NAVEGAÇÃO & AEO ──────────────────────────────────────────
echo "## F. navegação & AEO"
if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  primary=$($WP eval '$l=get_theme_mod("nav_menu_locations"); echo isset($l["primary"]) ? (int)$l["primary"] : 0;' 2>/dev/null || echo 0)
  if [[ -n "$primary" && "$primary" != "0" ]]; then ok "AR-NAV-001 menu primary=$primary"; else block "AR-NAV-001 sem menu primary"; fi
  menu_count=$($WP menu item list estrato-principal --format=count 2>/dev/null || $WP menu item list estrato-mente --format=count 2>/dev/null || echo 0)
  min_menu=3
  [[ $IS_FINANCE -eq 1 ]] && min_menu=8
  if [[ "$menu_count" -ge $min_menu ]]; then ok "AR-NAV-002 menu $menu_count itens"; else block "AR-NAV-002 menu apenas $menu_count itens (meta $min_menu+)"; fi
  if [[ $IS_FINANCE -eq 1 ]]; then
    breaking=$($WP eval 'echo (int) get_theme_mod("pressgrid_breaking_news_category");' 2>/dev/null || echo 0)
    forex=$($WP eval 'echo get_theme_mod("pressgrid_forex_force") ? "1" : "0";' 2>/dev/null || echo 0)
    if [[ -n "$breaking" && "$breaking" != "0" ]] || [[ "$forex" == "1" ]]; then ok "AR-VISUAL-004 ticker/forex ativo"; else warn "AR-VISUAL-004 sem ticker mercados/forex"; fi
  else
    ok "AR-VISUAL-004 ticker/forex N/A satélite"
  fi
else
  warn "WP-CLI indisponível — pulando AR-NAV-001/002"
fi

code=$(http_status "$BASE/feed/")
if [[ "$code" == "200" ]]; then ok "AR-NAV-004 RSS feed OK"; else warn "AR-NAV-004 RSS HTTP $code"; fi
code=$(http_status "$BASE/llms.txt")
if [[ "$code" == "200" ]]; then ok "AR-AEO-001 llms.txt OK"; else warn "AR-AEO-001 llms.txt ausente"; fi
code=$(http_status "$BASE/llms-full.txt")
if [[ "$code" == "200" ]]; then ok "AR-AEO llms-full.txt OK"; else warn "AR-AEO llms-full.txt ausente"; fi
if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  hub_faq=$($WP eval 'echo function_exists("estrato_regression_hub_faq_schema") ? estrato_regression_hub_faq_schema() : -1;' 2>/dev/null || echo -1)
  if [[ "$hub_faq" -ge 3 ]]; then ok "AR-AEO-002 $hub_faq hubs com FAQPage"; else warn "AR-AEO-002 $hub_faq hubs FAQ (meta 3+)"; fi
  idx=$($WP option get estrato_bridge_indexnow_enabled 2>/dev/null || echo "")
  if [[ "$idx" == "1" || "$idx" == "true" ]]; then ok "AR-INDEX-002 IndexNow habilitado"; else warn "AR-INDEX-002 IndexNow desabilitado"; fi
fi
code=$(http_status "$BASE/estrato-indexnow-key.txt")
if [[ "$code" == "200" ]]; then ok "AR-INDEX-001 IndexNow key file OK"; else warn "AR-INDEX-001 key file HTTP $code"; fi
code=$(http_status "$BASE/ads.txt")
if [[ "$code" == "200" ]]; then ok "AR-AEO ads.txt OK"; else warn "AR-AEO ads.txt ausente"; fi

if [[ $IS_FINANCE -eq 1 ]]; then
hubs_ok=0
for hub in selic ibovespa dolar cripto inflacao tributacao agronegocio; do
  c=$(http_status "$BASE/tudo-sobre/$hub/")
  [[ "$c" == "200" ]] && hubs_ok=$((hubs_ok+1))
done
if [[ "$hubs_ok" -ge 7 ]]; then ok "AR-NAV-003 $hubs_ok/7 hubs /tudo-sobre/"; elif [[ "$hubs_ok" -ge 3 ]]; then ok "AR-NAV-003 $hubs_ok hubs /tudo-sobre/ (mín 3)"; else warn "AR-NAV-003 apenas $hubs_ok/7 hubs"; fi
code=$(http_status "$BASE/cotacoes/")
if [[ "$code" == "200" ]]; then ok "AR-NAV página /cotacoes/ OK"; else warn "AR-NAV /cotacoes/ HTTP $code"; fi
else
  hubs_ok=0
  for slug in "${SATELLITE_CATS[@]}"; do
    hub="${slug%%-*}"
    c=$(http_status "$BASE/tudo-sobre/$hub/")
    [[ "$c" == "200" ]] && hubs_ok=$((hubs_ok+1))
  done
  if [[ "$hubs_ok" -ge 1 ]]; then ok "AR-NAV-003 $hubs_ok hubs /tudo-sobre/ (satélite)"; else warn "AR-NAV-003 nenhum hub /tudo-sobre/ no satélite"; fi
fi

# ─── H. TAXONOMIA (Sprint 8) ─────────────────────────────────────
echo "## H. taxonomia $( [[ $IS_FINANCE -eq 1 ]] && echo financeira || echo portal )"
if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  if [[ $IS_FINANCE -eq 1 ]]; then
  for slug in economia mercados negocios financas-pessoais criptomoedas agronegocio mundo; do
    cnt=$($WP post list --category_name="$slug" --post_status=publish --format=count 2>/dev/null || echo 0)
    if [[ "$cnt" -eq 0 ]]; then warn "AR-TAX-001 $slug sem posts publicados"; else ok "AR-TAX-001 $slug posts=$cnt"; fi
  done
  legacy=0
  for slug in politica tecnologia brasil; do
    n=$($WP post list --category_name="$slug" --post_status=publish --format=count 2>/dev/null || echo 0)
    legacy=$((legacy + n))
  done
  if [[ "$legacy" -eq 0 ]]; then ok "AR-TAX-002 zero posts em categorias legado"; else warn "AR-TAX-002 $legacy posts em politica/tecnologia/brasil"; fi
  else
  for slug in "${SATELLITE_CATS[@]}"; do
    cnt=$($WP post list --category_name="$slug" --post_status=publish --format=count 2>/dev/null || echo 0)
    if [[ "$cnt" -eq 0 ]]; then warn "AR-TAX-001 $slug sem posts publicados"; else ok "AR-TAX-001 $slug posts=$cnt"; fi
  done
  ok "AR-TAX-002 legado N/A satélite"
  fi
  if [[ $IS_FINANCE -eq 1 ]]; then
    pg_secs=$($WP eval 'echo function_exists("estrato_regression_pressgrid_finance_sections") ? estrato_regression_pressgrid_finance_sections() : -1;' 2>/dev/null || echo -1)
    if [[ "$pg_secs" -ge 7 ]]; then ok "AR-TAX-003 PressGrid $pg_secs seções por editoria"; elif [[ "$pg_secs" -ge 4 ]]; then ok "AR-TAX-003 PressGrid $pg_secs seções (mín 4)"; elif [[ "$pg_secs" -ge 0 ]]; then warn "AR-TAX-003 PressGrid apenas $pg_secs seções editoria"; else warn "AR-TAX-003 helper indisponível"; fi
  else
    pg_secs=$($WP eval 'echo function_exists("estrato_regression_pressgrid_portal_sections") ? estrato_regression_pressgrid_portal_sections() : -1;' 2>/dev/null || echo -1)
    editoria_n=${#SATELLITE_CATS[@]}
    if [[ "$pg_secs" -ge "$editoria_n" && "$editoria_n" -gt 0 ]]; then ok "AR-TAX-003 PressGrid $pg_secs/$editoria_n seções por editoria"; elif [[ "$pg_secs" -ge 1 ]]; then ok "AR-TAX-003 PressGrid $pg_secs seções (satélite)"; elif [[ "$pg_secs" -ge 0 ]]; then warn "AR-TAX-003 PressGrid apenas $pg_secs seções editoria"; else warn "AR-TAX-003 helper indisponível"; fi
  fi
  branded=$($WP eval 'echo function_exists("estrato_regression_branded_editorias_count") ? estrato_regression_branded_editorias_count() : -1;' 2>/dev/null || echo -1)
  if [[ "$branded" -ge 7 ]]; then ok "AR-TAX-004 $branded/7 editorias com branding"; elif [[ "$branded" -ge 4 ]]; then ok "AR-TAX-004 $branded editorias com branding (mín 4)"; elif [[ "$branded" -ge 0 ]]; then warn "AR-TAX-004 apenas $branded editorias com branding"; else warn "AR-TAX-004 branding editorias indisponível"; fi
  schema_v=$($WP eval '$t=function_exists("estrato_rss_load_finance_taxonomy")?estrato_rss_load_finance_taxonomy():(function_exists("estrato_rss_load_portal_taxonomy")?estrato_rss_load_portal_taxonomy():array()); echo (int)($t["schema_version"]??0);' 2>/dev/null || echo 0)
  if [[ "$schema_v" -ge 2 ]]; then ok "AR-TAX-005 schema taxonomia v$schema_v"; else warn "AR-TAX-005 schema taxonomia v$schema_v (meta v2)"; fi
  matrix_n=$($WP eval 'echo count(get_option("estrato_rss_import_matrix", array()));' 2>/dev/null || echo -1)
  if [[ "$matrix_n" -ge 7 ]]; then ok "AR-TAX-006 matriz RSS $matrix_n nós"; elif [[ "$matrix_n" -ge 0 ]]; then warn "AR-TAX-006 matriz RSS $matrix_n nós"; else warn "AR-TAX-006 matriz RSS indisponível"; fi
else
  warn "WP-CLI indisponível — pulando AR-TAX-*"
fi

# ─── I. RSS CURADORIA (Sprint 9) ─────────────────────────────────
echo "## I. RSS curadoria"
if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  dupes=$($WP eval 'echo function_exists("estrato_rss_count_duplicate_feed_urls") ? estrato_rss_count_duplicate_feed_urls() : -1;' 2>/dev/null || echo -1)
  if [[ "$dupes" == "0" ]]; then ok "AR-RSS-001 zero URLs de feed duplicadas"; elif [[ "$dupes" -gt 0 ]]; then warn "AR-RSS-001 $dupes URLs duplicadas"; else warn "AR-RSS-001 helper indisponível"; fi
  rss_json=$($WP option get estrato_rss_settings --format=json 2>/dev/null || echo "{}")
  if echo "$rss_json" | grep -q '"items_per_feed":2' && echo "$rss_json" | grep -q '"max_per_run":15'; then
    ok "AR-RSS-002 pipeline_primary 2/feed max 15"
  else
    warn "AR-RSS-002 settings RSS fora do pipeline_primary"
  fi
  ratio=$($WP eval 'echo function_exists("estrato_rss_feed_health_ratio") ? estrato_rss_feed_health_ratio() : -1;' 2>/dev/null || echo -1)
  if awk -v r="$ratio" 'BEGIN{exit !(r>=0.70)}' 2>/dev/null; then ok "AR-RSS-003 saúde feeds=$ratio"; elif awk -v r="$ratio" 'BEGIN{exit !(r>=0)}' 2>/dev/null; then warn "AR-RSS-003 saúde feeds=$ratio (meta 0.70)"; else warn "AR-RSS-003 saúde feeds não validada"; fi
  menu_n=$($WP menu item list estrato-principal --format=count 2>/dev/null || $WP menu item list estrato-mente --format=count 2>/dev/null || echo 0)
  min_rss_menu=3
  [[ $IS_FINANCE -eq 1 ]] && min_rss_menu=8
  if [[ "$menu_n" -ge $min_rss_menu ]]; then ok "AR-RSS-004 menu $menu_n itens"; else warn "AR-RSS-004 menu $menu_n itens"; fi
else
  warn "WP-CLI indisponível — pulando AR-RSS-*"
fi

# ─── J. OPERAÇÃO (Sprint 10) ─────────────────────────────────────
echo "## J. operação contínua"
if command -v wp &>/dev/null || $WP option get blogname &>/dev/null 2>&1; then
  crons=$($WP eval 'echo function_exists("estrato_regression_ops_crons_scheduled") && estrato_regression_ops_crons_scheduled() ? 1 : 0;' 2>/dev/null || echo 0)
  if [[ "$crons" == "1" ]]; then ok "AR-OPS-001 crons semanal/mensal agendados"; else warn "AR-OPS-001 crons ops não agendados"; fi
  w_age=$($WP eval 'echo function_exists("estrato_regression_ops_weekly_report_age_days") ? estrato_regression_ops_weekly_report_age_days() : -1;' 2>/dev/null || echo -1)
  if [[ "$w_age" -ge 0 && "$w_age" -le 8 ]]; then ok "AR-OPS-002 relatório semanal (${w_age}d)"; elif [[ "$w_age" -ge 0 ]]; then warn "AR-OPS-002 relatório semanal antigo (${w_age}d)"; else warn "AR-OPS-002 sem relatório semanal"; fi
  m_age=$($WP eval 'echo function_exists("estrato_regression_ops_monthly_age_days") ? estrato_regression_ops_monthly_age_days() : -1;' 2>/dev/null || echo -1)
  if [[ "$m_age" -ge 0 && "$m_age" -le 35 ]]; then ok "AR-OPS-003 manutenção mensal (${m_age}d)"; elif [[ "$m_age" -ge 0 ]]; then warn "AR-OPS-003 manutenção mensal antiga (${m_age}d)"; else warn "AR-OPS-003 sem manutenção mensal"; fi
else
  warn "WP-CLI indisponível — pulando AR-OPS-*"
fi

# ─── G. PERFORMANCE ──────────────────────────────────────────────
echo "## G. performance & segurança"
code=$(http_status "$BASE/")
if [[ "$code" == "200" ]]; then ok "AR-PERF-001 HTTPS 200"; else block "AR-PERF-001 HTTPS $code"; fi
hdr=$(curl -sI --max-time 10 "${CURL_HOST[@]}" "$BASE/" | grep -i "x-content-type-options" || true)
if echo "$hdr" | grep -qi nosniff; then ok "AR-PERF-002 X-Content-Type-Options"; else warn "AR-PERF-002 sem nosniff"; fi

# ─── H. MOBILE-FIRST ──────────────────────────────────────────────
echo "## H. mobile-first"
if grep -qiE '<meta[^>]+name=["'\'']?viewport["'\'']?[^>]*width=device-width' "$HOME_HTML" \
  || grep -qiE '<meta[^>]+content=["'\''][^"'\'']*width=device-width' "$HOME_HTML"; then
  ok "AR-MOBILE-002 viewport device-width"
else
  block "AR-MOBILE-002 viewport ausente ou incorreto"
fi
mf_live=$(grep -oE '@media[^{]*max-width[^{]*\{' "$HOME_HTML" || true)
if [[ -z "$mf_live" ]]; then
  ok "AR-MOBILE-003 home sem @media max-width (mobile-first)"
else
  warn "AR-MOBILE-003 home ainda tem @media max-width (possível CSS de tema)"
fi
rm -f "$HOME_HTML"

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
if [[ "$WARN" -gt 0 && "$STRICT" == "--strict" ]]; then
  echo "RESULT: FAIL (strict: $WARN warnings)"
  exit 1
fi
if [[ "$WARN" -gt 0 ]]; then
  echo "RESULT: PASS WITH WARNINGS"
  exit 0
fi
echo "RESULT: PASS"
exit 0
