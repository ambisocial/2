#!/usr/bin/env bash
# Auditoria Pacote E — RSS-Bridge, n8n, rss-filter, masto-rss multi-feed, bot flag
set -euo pipefail

STRICT=0
[[ "${1:-}" == "--strict" ]] && STRICT=1

STACK_DIR="${ESTRATO_SYNDICATION_DIR:-/opt/estrato-syndication}"
BRIDGE_HOST="${ESTRATO_BRIDGE_HOST:-bridge.estrato.cc}"
N8N_HOST="${ESTRATO_N8N_HOST:-n8n.estrato.cc}"
SOCIAL_HOST="${ESTRATO_SOCIAL_HOST:-social.estrato.cc}"
VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"
PASS=0
WARN=0
FAIL=0

ok() { echo "OK  $*"; PASS=$((PASS+1)); }
warn() { echo "WARN $*"; WARN=$((WARN+1)); }
fail() { echo "FAIL $*"; FAIL=$((FAIL+1)); }

# rss-filter (upstream v1.2.0 panics em alguns feeds — checamos porta)
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-rss-filter$'; then
  ok "rss-filter container"
  if (echo >/dev/tcp/127.0.0.1/8090) 2>/dev/null; then
    ok "rss-filter :8090 listening"
  else
    warn "rss-filter :8090 não escuta"
  fi
else
  warn "rss-filter não rodando"
fi

# RSS-Bridge
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-rss-bridge$'; then
  ok "rss-bridge container"
  CODE=$(curl -sS -o /dev/null -w '%{http_code}' http://127.0.0.1:3000/ 2>/dev/null || echo 000)
  if [[ "$CODE" == "200" || "$CODE" == "302" ]]; then
    ok "rss-bridge local :3000"
  else
    warn "rss-bridge local HTTP ${CODE}"
  fi
else
  warn "rss-bridge não rodando"
fi

# n8n
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-n8n$'; then
  ok "n8n container"
  CODE=$(curl -sS -o /dev/null -w '%{http_code}' http://127.0.0.1:5679/ 2>/dev/null || echo 000)
  if [[ "$CODE" == "200" || "$CODE" == "302" || "$CODE" == "401" ]]; then
    ok "n8n local :5679"
  else
    warn "n8n local HTTP ${CODE}"
  fi
else
  warn "n8n não rodando"
fi

# masto-rss feeds por editoria
FEEDS_FILE="${STACK_DIR}/masto-rss-feeds.txt"
if [[ -f "$FEEDS_FILE" ]]; then
  LINES=$(grep -c . "$FEEDS_FILE" 2>/dev/null || echo 0)
  if [[ "$LINES" -ge 1 ]]; then
    ok "masto-rss-feeds.txt (${LINES} editorias)"
  else
    fail "masto-rss-feeds.txt vazio"
  fi
else
  fail "masto-rss-feeds.txt ausente"
fi

# masto-rss container
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-masto-rss$'; then
  ok "masto-rss container"
else
  warn "masto-rss não rodando"
fi

# Bot flag (GoToSocial)
ENV_FILE="${STACK_DIR}/.env"
if [[ -f "$ENV_FILE" ]]; then
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  if [[ -n "${MASTODON_ACCESS_TOKEN:-}" ]]; then
    BOT_JSON=$(curl -sS -H "Authorization: Bearer ${MASTODON_ACCESS_TOKEN}" \
      "https://${SOCIAL_HOST}/api/v1/accounts/verify_credentials" 2>/dev/null \
      || curl -sS -H "Authorization: Bearer ${MASTODON_ACCESS_TOKEN}" \
      "http://127.0.0.1:8085/api/v1/accounts/verify_credentials" 2>/dev/null || true)
    IS_BOT=$(echo "$BOT_JSON" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('bot', False))" 2>/dev/null || echo False)
    if [[ "$IS_BOT" == "True" || "$IS_BOT" == "true" ]]; then
      ok "bot flag estrato_bot"
    else
      warn "bot flag estrato_bot não definido"
    fi
  else
    warn "MASTODON_ACCESS_TOKEN ausente"
  fi
fi

# DNS público
for HOST in "$BRIDGE_HOST" "$N8N_HOST"; do
  if dig +short "$HOST" A 2>/dev/null | grep -q .; then
    ok "DNS ${HOST}"
  else
    warn "DNS ${HOST} sem registro"
  fi
done

# HTTPS público
for HOST in "$BRIDGE_HOST" "$N8N_HOST"; do
  CODE=$(curl -sS -o /dev/null -w '%{http_code}' "https://${HOST}/" 2>/dev/null || echo 000)
  if [[ "$CODE" == "200" || "$CODE" == "302" || "$CODE" == "401" ]]; then
    ok "HTTPS ${HOST}"
  else
    warn "HTTPS ${HOST} HTTP ${CODE}"
  fi
done

# nginx local
for HOST in "$BRIDGE_HOST" "$N8N_HOST"; do
  if curl -sf -k -H "Host: ${HOST}" "https://${VPS_IP}/" -o /dev/null 2>/dev/null; then
    ok "nginx proxy ${HOST}"
  else
    warn "nginx proxy ${HOST} indisponível"
  fi
done

echo "--- $PASS ok / $WARN warn / $FAIL fail ---"
if [[ "$STRICT" == "1" && "$FAIL" -gt 0 ]]; then exit 1; fi
