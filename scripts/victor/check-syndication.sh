#!/usr/bin/env bash
# Auditoria Pacote B — syndication + feed + stack local
set -euo pipefail

STRICT=0
[[ "${1:-}" == "--strict" ]] && STRICT=1

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
LOG="/var/log/estrato/syndicate.log"
PASS=0
WARN=0
FAIL=0

ok() { echo "OK  $*"; PASS=$((PASS+1)); }
warn() { echo "WARN $*"; WARN=$((WARN+1)); }
fail() { echo "FAIL $*"; FAIL=$((FAIL+1)); }

# Feed
CODE=$(curl -sS -o /dev/null -w '%{http_code}' https://estrato.cc/feed/ || echo 000)
if [[ "$CODE" == "200" ]]; then ok "feed HTTP 200"; else fail "feed HTTP $CODE"; fi

# Script
if [[ -x "$REPO/scripts/victor/syndicate-outbound.py" ]]; then ok "syndicate-outbound.py"; else fail "syndicate-outbound.py ausente"; fi

# Log
if [[ -f "$LOG" ]] && [[ -s "$LOG" ]]; then
  LINES=$(wc -l < "$LOG")
  ok "syndicate.log ($LINES linhas)"
else
  warn "syndicate.log vazio"
fi

# Plugin hook
if [[ -f /var/www/estrato.cc/wp-content/plugins/estrato-portal-bootstrap/syndication.php ]] \
   && grep -q 'estrato_syndicate_on_publish' /var/www/estrato.cc/wp-content/plugins/estrato-portal-bootstrap/syndication.php 2>/dev/null; then
  ok "hook publish syndication"
else
  warn "syndication.php hook não encontrado no plugin live"
fi

# GoToSocial
if curl -sf http://127.0.0.1:8085/.well-known/nodeinfo >/dev/null 2>&1; then
  ok "GoToSocial local :8085"
else
  warn "GoToSocial local indisponível"
fi

# FreshRSS
if curl -sf -o /dev/null http://127.0.0.1:8088/i/ 2>/dev/null; then
  ok "FreshRSS local :8088"
  if docker exec -u www-data -w /var/www/FreshRSS estrato-freshrss \
    php ./cli/list-users.php 2>/dev/null | grep -q '^estrato-syndication$'; then
    ok "FreshRSS usuário estrato-syndication"
  else
    warn "FreshRSS usuário estrato-syndication ausente"
  fi
else
  warn "FreshRSS local indisponível"
fi

# masto-rss
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-masto-rss$'; then
  ok "masto-rss container"
else
  warn "masto-rss não rodando"
fi

# masto-rss feeds por editoria
STACK_DIR="${ESTRATO_SYNDICATION_DIR:-/opt/estrato-syndication}"
if [[ -f "${STACK_DIR}/masto-rss-feeds.txt" ]]; then
  LINES=$(grep -c . "${STACK_DIR}/masto-rss-feeds.txt" 2>/dev/null || echo 0)
  if [[ "$LINES" -ge 1 ]]; then
    ok "masto-rss-feeds (${LINES} editorias)"
  else
    warn "masto-rss-feeds.txt vazio"
  fi
else
  warn "masto-rss-feeds.txt ausente"
fi

# rss-filter
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-rss-filter$'; then
  ok "rss-filter container"
else
  warn "rss-filter não rodando"
fi

echo "--- $PASS ok / $WARN warn / $FAIL fail ---"
if [[ "$STRICT" == "1" && "$FAIL" -gt 0 ]]; then exit 1; fi
