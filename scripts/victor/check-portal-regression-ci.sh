#!/usr/bin/env bash
# Validação anti-regressão para CI (sem WordPress/Victor).
# Uso: bash scripts/victor/check-portal-regression-ci.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

FAIL=0
ok()   { echo "  ✅ $1"; }
fail() { echo "  ❌ $1"; FAIL=$((FAIL+1)); }

echo "=== Estrato Anti-Regression CI (static) ==="

if [[ -f portals/estrato-anti-regression.yaml ]]; then
  ok "estrato-anti-regression.yaml presente"
else
  fail "estrato-anti-regression.yaml ausente"
fi

for ar in mind lifestyle science agro esg viagem culture politica esporte saude educacao tech carros sustain; do
  f="portals/estrato-anti-regression-${ar}.yaml"
  if [[ -f "$f" ]]; then
    ok "$(basename "$f") presente"
  else
    fail "$(basename "$f") ausente"
  fi
done

if [[ -f scripts/victor/check-portal-regression-all.sh ]]; then
  bash -n scripts/victor/check-portal-regression-all.sh && ok "bash -n check-portal-regression-all.sh"
else
  fail "check-portal-regression-all.sh ausente"
fi

if [[ -f scripts/victor/setup-sprint-c1-multi-regression.sh ]]; then
  bash -n scripts/victor/setup-sprint-c1-multi-regression.sh && ok "bash -n setup-sprint-c1-multi-regression.sh"
else
  fail "setup-sprint-c1-multi-regression.sh ausente"
fi

if [[ -f portals/estrato-finance-taxonomy.php ]]; then
  ok "estrato-finance-taxonomy.php presente"
else
  fail "estrato-finance-taxonomy.php ausente"
fi

if [[ -f portals/estrato-mind-taxonomy.php ]]; then
  ok "estrato-mind-taxonomy.php presente"
else
  fail "estrato-mind-taxonomy.php ausente"
fi

if [[ -f portals/estrato-lifestyle-taxonomy.php ]]; then
  ok "estrato-lifestyle-taxonomy.php presente"
else
  fail "estrato-lifestyle-taxonomy.php ausente"
fi

if [[ -f portals/estrato-lifestyle.yaml ]]; then
  ok "estrato-lifestyle.yaml presente"
else
  fail "estrato-lifestyle.yaml ausente"
fi

if [[ -f portals/estrato-science-taxonomy.php ]]; then
  ok "estrato-science-taxonomy.php presente"
else
  fail "estrato-science-taxonomy.php ausente"
fi

if [[ -f portals/estrato-science.yaml ]]; then
  ok "estrato-science.yaml presente"
else
  fail "estrato-science.yaml ausente"
fi

for f in portals/estrato-agro-taxonomy.php portals/estrato-esg-taxonomy.php portals/estrato-viagem-taxonomy.php \
          portals/estrato-agro.yaml portals/estrato-esg.yaml portals/estrato-viagem.yaml \
          portals/estrato-politica-taxonomy.php portals/estrato-esporte-taxonomy.php portals/estrato-saude-taxonomy.php \
          portals/estrato-educacao-taxonomy.php portals/estrato-tech-taxonomy.php portals/estrato-carros-taxonomy.php \
          portals/estrato-politica.yaml portals/estrato-esporte.yaml portals/estrato-saude.yaml \
          portals/estrato-educacao.yaml portals/estrato-tech.yaml portals/estrato-carros.yaml; do
  if [[ -f "$f" ]]; then
    ok "$(basename "$f") presente"
  else
    fail "$(basename "$f") ausente"
  fi
done

if [[ -f portals/estrato-culture-taxonomy.php ]]; then
  ok "estrato-culture-taxonomy.php presente"
else
  fail "estrato-culture-taxonomy.php ausente"
fi

if [[ -f portals/estrato-culture.yaml ]]; then
  ok "estrato-culture.yaml presente"
else
  fail "estrato-culture.yaml ausente"
fi

for sh in scripts/victor/check-portal-regression.sh \
          scripts/victor/check-portal-regression-all.sh \
          scripts/victor/setup-sprint-c1-multi-regression.sh \
          scripts/victor/setup-estrato-gate.sh \
          scripts/victor/setup-estrato-taxonomy.sh \
          scripts/victor/setup-estrato-rss-curation.sh \
          scripts/victor/setup-estrato-ops.sh \
          scripts/victor/setup-estrato-mind-taxonomy.sh \
          scripts/victor/setup-estrato-lifestyle-taxonomy.sh \
          scripts/victor/setup-estrato-science-taxonomy.sh \
          scripts/victor/setup-estrato-agro-taxonomy.sh \
          scripts/victor/setup-estrato-esg-taxonomy.sh \
          scripts/victor/setup-estrato-viagem-taxonomy.sh \
          scripts/victor/setup-estrato-culture-taxonomy.sh; do
  if [[ -f "$sh" ]]; then
    bash -n "$sh" && ok "bash -n $sh"
  else
    fail "ausente: $sh"
  fi
done

if command -v php >/dev/null 2>&1; then
  for php in estrato-portal-bootstrap/*.php estrato-rss-bootstrap/*.php; do
    [[ -f "$php" ]] || continue
    php -l "$php" >/dev/null 2>&1 && ok "php -l $(basename "$php")" || fail "php -l $php"
  done
else
  ok "php CLI ausente — pulando php -l (CI instala php-cli)"
fi

# Matriz RSS: sem URL duplicada nos taxonomy files (editorias + subs + colunas).
check_taxonomy_dupes() {
  local file="$1"
  if command -v php >/dev/null 2>&1; then
    php -r '
$t = include "'"$file"'";
$s = array();
$d = 0;
$layers = array("categories", "subcategories", "columns");
foreach ($layers as $layer) {
  if (empty($t[$layer]) || !is_array($t[$layer])) continue;
  foreach ($t[$layer] as $node) {
    foreach (($node["feeds"] ?? array()) as $f) {
      $u = strtolower(rtrim($f["url"] ?? "", "/"));
      if ($u === "") continue;
      if (isset($s[$u])) $d++;
      $s[$u] = 1;
    }
  }
}
exit($d > 0 ? 1 : 0);
' 2>/dev/null
  else
    python3 -c "
import re, sys
t = open('$file').read()
urls = re.findall(r\"url\\s*=>\\s*'([^']+)'\", t)
seen = set()
d = 0
for u in urls:
    k = u.lower().rstrip('/')
    if k in seen:
        d += 1
    seen.add(k)
sys.exit(1 if d else 0)" 2>/dev/null
  fi
}

for tax in portals/estrato-finance-taxonomy.php portals/estrato-mind-taxonomy.php portals/estrato-lifestyle-taxonomy.php portals/estrato-science-taxonomy.php portals/estrato-agro-taxonomy.php portals/estrato-esg-taxonomy.php portals/estrato-viagem-taxonomy.php portals/estrato-culture-taxonomy.php portals/estrato-politica-taxonomy.php portals/estrato-esporte-taxonomy.php portals/estrato-saude-taxonomy.php portals/estrato-educacao-taxonomy.php portals/estrato-tech-taxonomy.php portals/estrato-carros-taxonomy.php; do
  if [[ -f "$tax" ]]; then
    if command -v php >/dev/null 2>&1; then
      php -l "$tax" >/dev/null 2>&1 && ok "php -l $(basename "$tax")" || fail "php -l $tax"
    fi
    if check_taxonomy_dupes "$tax"; then
      ok "$(basename "$tax") sem feeds duplicados"
    else
      fail "$(basename "$tax") com URLs de feed duplicadas"
    fi
  fi
done

# Validador de feeds (syntax only — não faz HTTP no CI por padrão)
if [[ -f scripts/victor/validate-rss-feeds.py ]]; then
  python3 -m py_compile scripts/victor/validate-rss-feeds.py 2>/dev/null && ok "validate-rss-feeds.py syntax OK" || fail "validate-rss-feeds.py syntax error"
fi

if [[ -f scripts/victor/sync-firesfera-feeds.py ]]; then
  python3 -m py_compile scripts/victor/sync-firesfera-feeds.py 2>/dev/null && ok "sync-firesfera-feeds.py syntax OK" || fail "sync-firesfera-feeds.py syntax error"
fi

if [[ -f portals/firesfera-feeds.json ]]; then
  ok "firesfera-feeds.json manifest presente"
fi

# Mobile-first: zero @media (max-width) / matchMedia max-width no CSS/JS Estrato.
if command -v rg >/dev/null 2>&1; then
  mf_hits=$(rg -n --glob '*.php' --glob '*.js' --glob '*.css' '@media\s*\([^)]*max-width|matchMedia\([^\)]*max-width' estrato-portal-bootstrap 2>/dev/null || true)
else
  mf_hits=$(grep -RInE --include='*.php' --include='*.js' --include='*.css' '@media[[:space:]]*\([^)]*max-width|matchMedia\([^)]*max-width' estrato-portal-bootstrap 2>/dev/null || true)
fi
if [[ -z "$mf_hits" ]]; then
  ok "AR-MOBILE-001 CSS/JS Estrato sem @media/matchMedia max-width (mobile-first)"
else
  fail "AR-MOBILE-001 desktop-first restante:"$'\n'"$mf_hits"
fi

# Checklist UI/UX mobile — P0 markers (chrome, home, ticker, single).
# Usa grep quando ripgrep não está no CI.
file_has() {
  local pattern="$1" file="$2"
  if command -v rg >/dev/null 2>&1; then
    rg -q -- "$pattern" "$file" 2>/dev/null
  else
    grep -qE -- "$pattern" "$file" 2>/dev/null
  fi
}
tree_has() {
  local pattern="$1"
  if command -v rg >/dev/null 2>&1; then
    rg -q -- "$pattern" estrato-portal-bootstrap 2>/dev/null
  else
    grep -RIqE --include='*.php' --include='*.js' --include='*.css' -- "$pattern" estrato-portal-bootstrap 2>/dev/null
  fi
}
check_mobile_marker() {
  local id="$1" file="$2" pattern="$3"
  if file_has "$pattern" "$file"; then
    ok "$id"
  else
    fail "$id ausente em $file (pattern: $pattern)"
  fi
}
check_mobile_marker "AR-MOBILE-UX-A3 sticky magro" estrato-portal-bootstrap/nav-header-g1.php 'estrato-g1-header__sticky'
check_mobile_marker "AR-MOBILE-UX-A7 trilho editorias" estrato-portal-bootstrap/nav-header-g1.php 'estrato-g1-rail'
check_mobile_marker "AR-MOBILE-UX-A4 drawer fullscreen" estrato-portal-bootstrap/nav-header-g1.php 'position:fixed;inset:0'
check_mobile_marker "AR-MOBILE-UX-B2 Outras marcas" estrato-portal-bootstrap/nav-header-g1.php 'Outras marcas'
check_mobile_marker "AR-MOBILE-UX-C3 Ver mais em" estrato-portal-bootstrap/home-layout.php 'Ver mais em'
check_mobile_marker "AR-MOBILE-UX-C2 Ver todas" estrato-portal-bootstrap/home-layout.php 'Ver todas'
check_mobile_marker "AR-MOBILE-UX-B3 network hub" estrato-portal-bootstrap/home-layout.php 'estrato_nav_network_hub_html'
if file_has "estrato-finance" estrato-portal-bootstrap/ticker-br.php && file_has "estrato_ticker_inject" estrato-portal-bootstrap/ticker-br.php && file_has "!== \\\$portal|!== \$portal" estrato-portal-bootstrap/ticker-br.php; then
  ok "AR-MOBILE-UX-E3 ticker finance-only"
else
  fail "AR-MOBILE-UX-E3 ticker finance-only ausente"
fi
check_mobile_marker "AR-MOBILE-UX-D3 share fixed + Copiar" estrato-portal-bootstrap/single-article.php 'data-estrato-copy'
check_mobile_marker "AR-MOBILE-UX-D5 sidebar mobile oculta" estrato-portal-bootstrap/single-article.php 'aside.pg-sidebar\{display:none'
check_mobile_marker "AR-MOBILE-UX-F2 footer cols off mobile" estrato-portal-bootstrap/nav-footer-ft.php 'estrato-ft-cols\{display:none'
check_mobile_marker "AR-MOBILE-UX-H3 reduced-motion" estrato-portal-bootstrap/design-system.php 'prefers-reduced-motion'
check_mobile_marker "AR-MOBILE-UX-A9 busca suggest" estrato-portal-bootstrap/nav-header-g1.php 'estrato-g1-search-suggest'
check_mobile_marker "AR-MOBILE-UX-B4 label clicável" estrato-portal-bootstrap/nav-header-g1.php 'estrato-g1-header__editoria-label'
check_mobile_marker "AR-MOBILE-UX-G3 tipografia DS" estrato-portal-bootstrap/nav-header-g1.php 'estrato-font-body'
check_mobile_marker "AR-MOBILE-UX-E1 hide PressGrid Urgente" estrato-portal-bootstrap/nav-header-g1.php 'pg-breaking-bar.*display:none|pg-breaking-bar,.pg-breaking-label'
if tree_has 'function estrato_nav_columns_ribbon|estrato-columns-ribbon'; then
  fail "AR-MOBILE-UX-A10 ribbon morto ainda presente"
else
  ok "AR-MOBILE-UX-A10 ribbon morto removido"
fi
check_mobile_marker "AR-MOBILE-UX-C2 title dedupe" estrato-portal-bootstrap/home-layout.php 'estrato_home_normalize_title'
check_mobile_marker "AR-MOBILE-UX-title portal" estrato-portal-bootstrap/seo-yoast-defaults.php 'estrato_yoast_portal_document_title'

# Removido bloco antigo de dup único finance
true

echo
if [[ "$FAIL" -gt 0 ]]; then
  echo "RESULT: FAIL ($FAIL checks)"
  exit 1
fi
echo "RESULT: PASS"
exit 0
