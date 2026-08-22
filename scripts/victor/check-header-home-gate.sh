#!/usr/bin/env bash
# Gate: header home mobile — trilho horizontal + rede sem “Outras marcas”.
# Uso: bash scripts/victor/check-header-home-gate.sh [URL]
# Exit 0 = PASS; 1 = FAIL.
set -euo pipefail

URL="${1:-https://estrato.cc/}"
HOST_HEADER="${ESTRATO_HOST_HEADER:-}"
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT

CURL=(curl -fsSL --max-time 25 -A 'EstratoHeaderGate/1.0')
if [[ -n "$HOST_HEADER" ]]; then
  CURL+=(-H "Host: $HOST_HEADER")
fi
# Prefer IP+Host when behind SSL on raw IP.
if [[ "$URL" == https://187.127.12.186/* ]] || [[ "$URL" == http://187.127.12.186/* ]]; then
  CURL+=(-k -H 'Host: estrato.cc')
fi

echo "== Header home gate: $URL =="
"${CURL[@]}" "$URL" > "$TMP"

fail=0
pass() { echo "PASS: $*"; }
fail_() { echo "FAIL: $*"; fail=1; }

# 1) Sem Outras marcas / toggle (só markup HTML, não nomes de classe CSS)
if grep -qE '<button[^>]*estrato-g1-header__rede-toggle' "$TMP"; then
  fail_ 'ainda contém <button rede-toggle>'
else
  pass 'sem <button rede-toggle>'
fi
if grep -qiE 'Outras marcas' "$TMP"; then
  fail_ 'ainda contém texto “Outras marcas” no HTML'
else
  pass 'sem texto “Outras marcas”'
fi

# 2) Rede sheet não hidden
if grep -qE 'id="estrato-g1-rede-sheet"[^>]*\bhidden\b' "$TMP"; then
  fail_ 'rede-sheet com atributo hidden'
else
  pass 'rede-sheet sem hidden'
fi

# 3) Rail UL class limpo
python3 - "$TMP" <<'PY'
import re, sys
html = open(sys.argv[1], encoding="utf-8", errors="ignore").read()
rail = re.search(r'<nav[^>]*class="[^"]*estrato-g1-header__rail[^"]*"[\s\S]*?</nav>', html)
if not rail:
    print("FAIL: nav.estrato-g1-header__rail ausente")
    sys.exit(2)
chunk = rail.group(0)
m = re.search(r'<ul[^>]*class="([^"]*)"', chunk)
if not m:
    print("FAIL: UL do rail ausente")
    sys.exit(2)
cls = m.group(1)
ok = True
if "estrato-g1-rail" not in cls.split():
    print("FAIL: falta classe estrato-g1-rail:", cls); ok = False
if "estrato-g1-menu" in cls.split():
    print("FAIL: drawer class estrato-g1-menu no rail:", cls); ok = False
if "estrato-mega-nav" in cls.split():
    print("FAIL: drawer class estrato-mega-nav no rail:", cls); ok = False
if "estrato-mega-toggle" in chunk:
    print("FAIL: mega-toggle vazou no rail"); ok = False
if "sub-menu" in chunk:
    print("FAIL: sub-menu vazou no rail"); ok = False
if ok:
    print("PASS: rail classes limpas:", cls)
    sys.exit(0)
sys.exit(2)
PY
rail_rc=$?
if [[ $rail_rc -ne 0 ]]; then fail=1; fi

# 4) CSS de defesa presente
if grep -q 'flex-direction:row!important' "$TMP"; then
  pass 'CSS flex-direction:row!important presente'
else
  fail_ 'CSS flex-direction:row!important ausente (cache/deploy antigo?)'
fi
if grep -q 'inset 0 -3px' "$TMP"; then
  fail_ 'ainda tem underline inset 0 -3px (CSS antigo)'
else
  pass 'sem underline inset grosso'
fi

# 5) Marcador CSS 1.36.7+ (cinto se classes do drawer vazarem)
if grep -q 'estrato-g1-header__rail ul.estrato-g1-menu' "$TMP"; then
  pass 'cinto CSS anti-leak (1.36.7+) presente'
else
  fail_ 'sem cinto CSS 1.36.7 — deploy incompleto ou cache'
fi

# 6) Contenção nuclear 1.36.9 — trilho não pode crescer em coluna no mobile
if grep -q 'max-height:48px' "$TMP"; then
  pass 'CSS max-height:48px no rail (1.36.9+) presente'
else
  fail_ 'sem max-height:48px no rail — deploy 1.36.9 incompleto ou cache'
fi

if [[ $fail -eq 0 ]]; then
  echo "== RESULT: PASS =="
  exit 0
fi
echo "== RESULT: FAIL =="
exit 1
