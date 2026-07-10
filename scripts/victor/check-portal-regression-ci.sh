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

if [[ -f portals/estrato-finance-taxonomy.php ]]; then
  ok "estrato-finance-taxonomy.php presente"
else
  fail "estrato-finance-taxonomy.php ausente"
fi

for sh in scripts/victor/check-portal-regression.sh \
          scripts/victor/setup-estrato-gate.sh \
          scripts/victor/setup-estrato-taxonomy.sh \
          scripts/victor/setup-estrato-rss-curation.sh \
          scripts/victor/setup-estrato-ops.sh; do
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

# Matriz RSS: sem URL duplicada no taxonomy file.
if command -v php >/dev/null 2>&1; then
  dup_cmd='php -r '\''$t=include "portals/estrato-finance-taxonomy.php";$s=[];$d=0;foreach(($t["categories"]??[]) as $c){foreach(($c["feeds"]??[]) as $f){$u=strtolower(rtrim($f["url"]??"", "/"));if($u==="")continue;if(isset($s[$u]))$d++;$s[$u]=1;}}exit($d>0?1:0);'\'''
else
  dup_cmd='python3 -c "import pathlib,re; t=open(\"portals/estrato-finance-taxonomy.php\").read(); urls=re.findall(r\"url\\s*=>\\s*'\''([^'\'']+)'\''\", t); seen=set(); d=0
for u in urls:
 k=u.lower().rstrip(\"/\")
 if k in seen: d+=1
 seen.add(k)
import sys; sys.exit(1 if d else 0)"'
fi
if eval "$dup_cmd" 2>/dev/null; then
  ok "taxonomy sem feeds duplicados"
else
  fail "taxonomy com URLs de feed duplicadas"
fi

echo
if [[ "$FAIL" -gt 0 ]]; then
  echo "RESULT: FAIL ($FAIL checks)"
  exit 1
fi
echo "RESULT: PASS"
exit 0
