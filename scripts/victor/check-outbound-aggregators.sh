#!/usr/bin/env bash
# Auditoria Pacote F — agregadores externos (não self-hosted)
set -euo pipefail

STRICT=0
[[ "${1:-}" == "--strict" ]] && STRICT=1

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
DOMAIN="${ESTRATO_DOMAIN:-https://estrato.cc}"
SECRETS="${ESTRATO_SECRETS_DIR:-/root/.secrets}"
PASS=0
WARN=0
FAIL=0

ok() { echo "OK  $*"; PASS=$((PASS+1)); }
warn() { echo "WARN $*"; WARN=$((WARN+1)); }
fail() { echo "FAIL $*"; FAIL=$((FAIL+1)); }

# IndexNow
CODE=$(curl -sS -o /dev/null -w '%{http_code}' "${DOMAIN}/estrato-indexnow-key.txt" 2>/dev/null || echo 000)
if [[ "$CODE" == "200" ]]; then ok "IndexNow key file"; else fail "IndexNow key HTTP $CODE"; fi

# Feeds / sitemaps
for PATH_URL in /feed/ /news-sitemap.xml /msn-feed.xml /sitemap_index.xml; do
  CODE=$(curl -sS -o /dev/null -w '%{http_code}' "${DOMAIN}${PATH_URL}" 2>/dev/null || echo 000)
  if [[ "$CODE" == "200" ]]; then ok "${PATH_URL} HTTP 200"; else fail "${PATH_URL} HTTP $CODE"; fi
done

# MSN feed spec básica
MSN_BODY=$(curl -sS --max-time 20 "${DOMAIN}/msn-feed.xml" 2>/dev/null | head -c 50000 || true)
if echo "$MSN_BODY" | grep -qF '<rss'; then
  ok "MSN feed RSS válido"
  if echo "$MSN_BODY" | grep -q 'xmlns:media'; then ok "MSN media namespace"; else warn "MSN sem media:"; fi
  if echo "$MSN_BODY" | grep -q '<item>'; then ok "MSN feed com items"; else warn "MSN feed sem items"; fi
else
  fail "MSN feed inválido"
fi

# Script outbound
if [[ -x "$REPO/scripts/victor/syndicate-outbound.py" ]]; then
  ok "syndicate-outbound.py"
else
  fail "syndicate-outbound.py ausente"
fi

# Log syndication
if [[ -f /var/log/estrato/syndicate.log ]] && [[ -s /var/log/estrato/syndicate.log ]]; then
  ok "syndicate.log"
else
  warn "syndicate.log vazio"
fi

# Hook WP
if [[ -f /var/www/estrato.cc/wp-content/plugins/estrato-portal-bootstrap/syndication.php ]]; then
  ok "hook publish syndication"
else
  warn "syndication.php hook ausente"
fi

# GSC
if [[ -f /root/estrato-gsc-service-account.json ]]; then
  ok "GSC service account"
  if python3 "$REPO/scripts/victor/index-bot/gsc_api.py" sites >/dev/null 2>&1; then
    ok "GSC API acessível"
  else
    warn "GSC API sem acesso"
  fi
else
  warn "GSC service account ausente"
fi

# Freespoke
if [[ -f "$SECRETS/freespoke.env" ]] && grep -qE '^FREESPOKE_PUBLISHER_API_KEY=.+$' "$SECRETS/freespoke.env" 2>/dev/null; then
  ok "Freespoke API key configurada"
else
  warn "Freespoke API key ausente ($SECRETS/freespoke.env)"
fi

# Neo Times
if [[ -f "$SECRETS/neotimes.env" ]] && grep -qE '^NEO_TIMES_API_TOKEN=.+$' "$SECRETS/neotimes.env" 2>/dev/null; then
  ok "Neo Times API token configurado"
else
  warn "Neo Times token ausente ($SECRETS/neotimes.env)"
fi

# PlatPhorm smoke (docs endpoint)
CODE=$(curl -sS -o /dev/null -w '%{http_code}' https://docs.platphormnews.com/api/v1/submissions/latest 2>/dev/null || echo 000)
if [[ "$CODE" == "200" ]]; then ok "PlatPhorm API reachable"; else warn "PlatPhorm API HTTP $CODE"; fi

# Crons
if crontab -l 2>/dev/null | grep -qF 'syndicate-outbound.py'; then ok "cron syndication"; else warn "cron syndication ausente"; fi
if crontab -l 2>/dev/null | grep -qF 'generate-msn-feed'; then ok "cron MSN feed"; else warn "cron MSN feed ausente"; fi

echo "--- $PASS ok / $WARN warn / $FAIL fail ---"
if [[ "$STRICT" == "1" && "$FAIL" -gt 0 ]]; then exit 1; fi
