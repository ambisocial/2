#!/usr/bin/env bash
# Gate live SEO/GEO/EEAT/YMYL — falha se regressão.
set -euo pipefail

FAIL=0
pass() { echo "  ✅ $1"; }
fail() { echo "  ❌ $1"; FAIL=$((FAIL + 1)); }

check_gpt_allow() {
  local host="$1"
  local body
  body=$(curl -fsS -m 25 "https://${host}/robots.txt?nocache=$RANDOM" || true)
  if echo "$body" | awk 'BEGIN{IGNORECASE=1}/User-agent:[[:space:]]*GPTBot/{getline; if ($0 ~ /Allow:[[:space:]]*\//) ok=1} END{exit ok?0:1}'; then
    pass "GEO GPTBot Allow — $host"
  else
    fail "GEO GPTBot Allow — $host"
  fi
}

echo "== SEO live gate =="
# sustain.estrato.cc redireciona 301→agro no edge; validamos agro + hosts vivos.
for h in estrato.cc mente.estrato.cc saude.estrato.cc agro.estrato.cc tech.estrato.cc; do
  check_gpt_allow "$h"
done
# Confirma redirect legado sustain→agro
sust_loc=$(curl -sS -m 20 -D - -o /dev/null "https://sustain.estrato.cc/" | tr -d '\r' | awk 'tolower($1)=="location:"{print $2; exit}')
if [[ "$sust_loc" == *"agro.estrato.cc"* ]]; then
  pass "Sustain legado 301→agro"
else
  fail "Sustain legado redirect (got: $sust_loc)"
fi

# Aliases 301
loc=$(curl -sS -m 20 -D - -o /dev/null "https://estrato.cc/ciencia/" | tr -d '\r' | awk 'tolower($1)=="location:"{print $2; exit}')
if [[ "$loc" == *"estrato.cc/science"* ]]; then
  pass "Alias /ciencia/ → /science/"
else
  fail "Alias /ciencia/ (got: $loc)"
fi

# Avisos YMYL
for p in aviso-medico aviso-financeiro metodologia correcoes; do
  code=$(curl -sS -m 20 -o /dev/null -w "%{http_code}" "https://estrato.cc/${p}/" || echo 000)
  if [[ "$code" == "200" ]]; then pass "YMYL page /$p/ $code"; else fail "YMYL page /$p/ $code"; fi
done

# Path hub title
tmp=$(mktemp)
curl -sS -m 25 -o "$tmp" "https://estrato.cc/financas/" || true
title=$(grep -oi '<title>[^<]*' "$tmp" | head -1 | sed 's/<title>//I')
if echo "$title" | grep -qi 'Finanças'; then pass "Path hub title financas"; else fail "Path hub title financas ($title)"; fi

# llms-full has Top 50
curl -sS -m 25 -o "$tmp" "https://estrato.cc/llms-full.txt" || true
if grep -q 'Top 50' "$tmp"; then
  pass "llms-full Top 50"
else
  fail "llms-full Top 50"
fi

# Article signals
art=$(curl -sS -m 25 "https://estrato.cc/post-sitemap.xml" | grep -oE 'https://estrato.cc/[a-z0-9-]+/' | grep -vE 'sitemap|wp-content|category|tag|author|page' | head -1 || true)
if [[ -n "$art" ]]; then
  curl -sS -m 35 -L -o "$tmp" "$art" || true
  grep -q 'FAQPage' "$tmp" && pass "FAQPage article" || fail "FAQPage article"
  grep -qi 'linkedin.com/in/' "$tmp" && fail "LinkedIn fake no HTML" || pass "Sem LinkedIn fake"
  grep -q 'estrato-single-avatar\|estrato-single-byline' "$tmp" && pass "Byline EEAT" || fail "Byline EEAT"
  grep -q 'reviewedBy' "$tmp" && pass "reviewedBy schema" || fail "reviewedBy schema"
else
  fail "Não achou artigo para amostrar"
fi
rm -f "$tmp"

if [[ "$FAIL" -gt 0 ]]; then
  echo "RESULT: FAIL ($FAIL)"
  exit 1
fi
echo "RESULT: PASS"
exit 0
