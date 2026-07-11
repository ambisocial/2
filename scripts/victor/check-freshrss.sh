#!/usr/bin/env bash
# Auditoria Pacote D — FreshRSS (local + público)
set -euo pipefail

STRICT=0
[[ "${1:-}" == "--strict" ]] && STRICT=1

RSS_HOST="${ESTRATO_RSS_HOST:-rss.estrato.cc}"
RSS_USER="${ESTRATO_FRESHRSS_USER:-estrato-syndication}"
VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"
PASS=0
WARN=0
FAIL=0

ok() { echo "OK  $*"; PASS=$((PASS+1)); }
warn() { echo "WARN $*"; WARN=$((WARN+1)); }
fail() { echo "FAIL $*"; FAIL=$((FAIL+1)); }

# Container
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-freshrss$'; then
  ok "FreshRSS container"
else
  fail "FreshRSS container ausente"
fi

# Local UI
if curl -sf -o /dev/null http://127.0.0.1:8088/i/ 2>/dev/null; then
  ok "FreshRSS local :8088"
else
  fail "FreshRSS local indisponível"
fi

# Usuário dedicado
if docker exec -u www-data -w /var/www/FreshRSS estrato-freshrss \
  php ./cli/list-users.php 2>/dev/null | grep -q "^${RSS_USER}$"; then
  ok "usuário ${RSS_USER}"
else
  fail "usuário ${RSS_USER} ausente"
fi

# Feed importado
FEED_COUNT=$(docker exec -u www-data -w /var/www/FreshRSS estrato-freshrss \
  php ./cli/user-info.php --user "$RSS_USER" --json 2>/dev/null \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(d[0].get('feeds',0) if d else 0)" 2>/dev/null || echo 0)
if [[ "${FEED_COUNT:-0}" -gt 0 ]]; then
  ok "feed estrato.cc (${FEED_COUNT})"
else
  fail "nenhum feed em ${RSS_USER}"
fi

# DNS público
if dig +short "$RSS_HOST" A 2>/dev/null | grep -q .; then
  ok "DNS ${RSS_HOST}"
else
  warn "DNS ${RSS_HOST} sem registro"
fi

# HTTPS público
CODE=$(curl -sS -o /dev/null -w '%{http_code}' "https://${RSS_HOST}/i/" 2>/dev/null || echo 000)
if [[ "$CODE" == "200" || "$CODE" == "302" ]]; then
  ok "FreshRSS HTTPS ${RSS_HOST}"
else
  warn "FreshRSS HTTPS ${CODE}"
fi

# nginx local
if curl -sf -k -H "Host: ${RSS_HOST}" "https://${VPS_IP}/i/" -o /dev/null 2>/dev/null; then
  ok "nginx reverse proxy rss"
else
  warn "nginx reverse proxy rss indisponível"
fi

echo "--- $PASS ok / $WARN warn / $FAIL fail ---"
if [[ "$STRICT" == "1" && "$FAIL" -gt 0 ]]; then exit 1; fi
