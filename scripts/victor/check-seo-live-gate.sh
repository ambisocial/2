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
for h in estrato.cc mente.estrato.cc saude.estrato.cc sustain.estrato.cc tech.estrato.cc; do
  check_gpt_allow "$h"
done

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
title=$(curl -sS -m 25 "https://estrato.cc/financas/" | grep -oi '<title>[^<]*' | head -1 | sed 's/<title>//I')
if echo "$title" | grep -qi 'Finanças'; then pass "Path hub title financas"; else fail "Path hub title financas ($title)"; fi

# llms-full has Top 50
if curl -fsS -m 25 "https://estrato.cc/llms-full.txt" | grep -q 'Top 50'; then
  pass "llms-full Top 50"
else
  fail "llms-full Top 50"
fi

# Article signals (latest from sitemap-ish home link fallback)
art=$(curl -sS -m 25 "https://estrato.cc/post-sitemap.xml" | grep -oE 'https://estrato.cc/[^<]+/' | grep -v sitemap | head -1 || true)
if [[ -n "$art" ]]; then
  html=$(curl -sS -m 30 "$art" || true)
  echo "$html" | grep -q 'FAQPage' && pass "FAQPage article" || fail "FAQPage article"
  echo "$html" | grep -qi 'linkedin.com/in/' && fail "LinkedIn fake no HTML" || pass "Sem LinkedIn fake"
  echo "$html" | grep -q 'estrato-single-avatar\|estrato-single-byline' && pass "Byline EEAT" || fail "Byline EEAT"
else
  fail "Não achou artigo para amostrar"
fi

if [[ "$FAIL" -gt 0 ]]; then
  echo "RESULT: FAIL ($FAIL)"
  exit 1
fi
echo "RESULT: PASS"
exit 0
