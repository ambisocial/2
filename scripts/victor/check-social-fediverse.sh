#!/usr/bin/env bash
# Auditoria Pacote C — Fediverse público (social.estrato.cc)
set -euo pipefail

STRICT=0
[[ "${1:-}" == "--strict" ]] && STRICT=1

SOCIAL_HOST="${ESTRATO_SOCIAL_HOST:-social.estrato.cc}"
VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"
PASS=0
WARN=0
FAIL=0

ok() { echo "OK  $*"; PASS=$((PASS+1)); }
warn() { echo "WARN $*"; WARN=$((WARN+1)); }
fail() { echo "FAIL $*"; FAIL=$((FAIL+1)); }

# DNS
DNS_IP=$(dig +short "$SOCIAL_HOST" A 2>/dev/null | head -1 || true)
if [[ -n "$DNS_IP" ]]; then
  ok "DNS ${SOCIAL_HOST} → ${DNS_IP}"
else
  warn "DNS ${SOCIAL_HOST} sem registro A"
fi

# HTTPS + nodeinfo
CODE=$(curl -sS -o /dev/null -w '%{http_code}' "https://${SOCIAL_HOST}/.well-known/nodeinfo" 2>/dev/null || echo 000)
if [[ "$CODE" == "200" ]]; then
  ok "nodeinfo HTTPS 200"
else
  fail "nodeinfo HTTPS ${CODE}"
fi

# Webfinger do bot
WF=$(curl -sS -o /dev/null -w '%{http_code}' \
  "https://${SOCIAL_HOST}/.well-known/webfinger?resource=acct:estrato_bot@${SOCIAL_HOST}" 2>/dev/null || echo 000)
if [[ "$WF" == "200" ]]; then
  ok "webfinger estrato_bot"
else
  warn "webfinger estrato_bot HTTP ${WF}"
fi

# nginx local proxy
if curl -sf -k -H "Host: ${SOCIAL_HOST}" "https://${VPS_IP}/.well-known/nodeinfo" >/dev/null 2>&1; then
  ok "nginx reverse proxy local"
else
  warn "nginx reverse proxy local indisponível"
fi

# GoToSocial backend
if curl -sf http://127.0.0.1:8085/.well-known/nodeinfo >/dev/null 2>&1; then
  ok "GoToSocial backend :8085"
else
  fail "GoToSocial backend indisponível"
fi

# masto-rss
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^estrato-masto-rss$'; then
  ok "masto-rss container"
else
  warn "masto-rss não rodando"
fi

# Bot flag
STACK_DIR="${ESTRATO_SYNDICATION_DIR:-/opt/estrato-syndication}"
ENV_FILE="${STACK_DIR}/.env"
if [[ -f "$ENV_FILE" ]]; then
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  if [[ -n "${MASTODON_ACCESS_TOKEN:-}" ]]; then
    BOT_JSON=$(curl -sS -H "Authorization: Bearer ${MASTODON_ACCESS_TOKEN}" \
      "https://${SOCIAL_HOST}/api/v1/accounts/verify_credentials" 2>/dev/null || true)
    IS_BOT=$(echo "$BOT_JSON" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('bot', False))" 2>/dev/null || echo False)
    if [[ "$IS_BOT" == "True" || "$IS_BOT" == "true" ]]; then
      ok "bot flag estrato_bot"
    else
      warn "bot flag estrato_bot não definido"
    fi
  fi
fi

echo "--- $PASS ok / $WARN warn / $FAIL fail ---"
if [[ "$STRICT" == "1" && "$FAIL" -gt 0 ]]; then exit 1; fi
