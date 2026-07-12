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

for ar in mind lifestyle science sustain culture; do
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

if [[ -f portals/estrato-sustain-taxonomy.php ]]; then
  ok "estrato-sustain-taxonomy.php presente"
else
  fail "estrato-sustain-taxonomy.php ausente"
fi

if [[ -f portals/estrato-sustain.yaml ]]; then
  ok "estrato-sustain.yaml presente"
else
  fail "estrato-sustain.yaml ausente"
fi

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
          scripts/victor/setup-estrato-sustain-taxonomy.sh \
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

for tax in portals/estrato-finance-taxonomy.php portals/estrato-mind-taxonomy.php portals/estrato-lifestyle-taxonomy.php portals/estrato-science-taxonomy.php portals/estrato-sustain-taxonomy.php portals/estrato-culture-taxonomy.php; do
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

# Removido bloco antigo de dup único finance
true

echo
if [[ "$FAIL" -gt 0 ]]; then
  echo "RESULT: FAIL ($FAIL checks)"
  exit 1
fi
echo "RESULT: PASS"
exit 0
