#!/usr/bin/env bash
# Pacote E — RSS-Bridge + n8n + rss-filter + masto-rss multi-feed
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
STACK_SRC="$REPO/scripts/victor/syndication-stack"
STACK_DIR="${ESTRATO_SYNDICATION_DIR:-/opt/estrato-syndication}"
CF_SECRETS="${CLOUDFLARE_CREDENTIALS:-/root/.secrets/cloudflare.ini}"
VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"
ZONE="${ESTRATO_ZONE:-estrato.cc}"
CERT_EMAIL="${ESTRATO_CERT_EMAIL:-tpb@ambi.social}"
BRIDGE_HOST="${ESTRATO_BRIDGE_HOST:-bridge.estrato.cc}"
N8N_HOST="${ESTRATO_N8N_HOST:-n8n.estrato.cc}"

echo "=== Estrato Pacote E: RSS-Bridge + n8n + filtros ($BRIDGE_HOST, $N8N_HOST) ==="

mkdir -p "$STACK_DIR" \
  "${STACK_DIR}/rss-bridge-config" \
  "${STACK_DIR}/n8n-data"
chown -R 1000:1000 "${STACK_DIR}/n8n-data" 2>/dev/null || true
rsync -a "$STACK_SRC/" "$STACK_DIR/"
chmod +x "$STACK_DIR/bootstrap-gotosocial.sh" \
  "$STACK_DIR/generate-masto-feeds.sh" \
  "$STACK_DIR/setup-freshrss.sh" 2>/dev/null || true

# masto-rss: feeds filtrados por editoria (category feeds diretos)
export MASTO_RSS_USE_FILTER=0
bash "$STACK_DIR/generate-masto-feeds.sh"

cd "$STACK_DIR"
docker compose pull rss-filter rss-bridge n8n 2>/dev/null || true
docker compose up -d rss-filter rss-bridge n8n

for _ in $(seq 1 12); do
  if curl -sf -o /dev/null "http://127.0.0.1:8090/?feed_url=https%3A%2F%2Festrato.cc%2Ffeed%2F&filter=Title%20!%3D%20%22%22" 2>/dev/null; then
    break
  fi
  sleep 3
done

# DNS Cloudflare (bridge + n8n)
ensure_dns() {
  local name="$1"
  local host="$2"
  if [[ ! -f "$CF_SECRETS" ]]; then
    echo "AVISO: configure DNS manualmente: ${host} → ${VPS_IP}"
    return 0
  fi
  local CF_EMAIL CF_KEY ZONE_ID EXISTING
  CF_EMAIL=$(grep -E '^dns_cloudflare_email' "$CF_SECRETS" | cut -d= -f2- | tr -d ' "')
  CF_KEY=$(grep -E '^dns_cloudflare_api_key' "$CF_SECRETS" | cut -d= -f2- | tr -d ' "')
  ZONE_ID=$(curl -sS -X GET "https://api.cloudflare.com/client/v4/zones?name=${ZONE}" \
    -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
    | python3 -c "import sys,json; r=json.load(sys.stdin).get('result',[]); print(r[0]['id'] if r else '')")
  [[ -n "$ZONE_ID" ]] || { echo "AVISO: zone ${ZONE} não encontrada"; return 0; }
  EXISTING=$(curl -sS -X GET \
    "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records?name=${host}" \
    -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
    | python3 -c "import sys,json; r=json.load(sys.stdin).get('result',[]); print(r[0]['id'] if r else '')")
  if [[ -z "$EXISTING" ]]; then
    curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records" \
      -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
      -d "{\"type\":\"A\",\"name\":\"${name}\",\"content\":\"${VPS_IP}\",\"proxied\":true,\"ttl\":1}" \
      | python3 -c "import sys,json; d=json.load(sys.stdin); print('DNS criado' if d.get('success') else d)"
  else
    echo "DNS ${host} já existe"
  fi
}

ensure_dns "bridge" "$BRIDGE_HOST"
ensure_dns "n8n" "$N8N_HOST"

# TLS (ambos subdomínios)
for HOST in "$BRIDGE_HOST" "$N8N_HOST"; do
  if [[ ! -f "/etc/letsencrypt/live/${HOST}/fullchain.pem" ]]; then
    if [[ -f "$CF_SECRETS" ]] && command -v certbot >/dev/null 2>&1; then
      echo "Aguardando propagação DNS para ${HOST} (45s)..."
      sleep 45
      certbot certonly --dns-cloudflare \
        --dns-cloudflare-credentials "$CF_SECRETS" \
        --dns-cloudflare-propagation-seconds 90 \
        -d "$HOST" \
        --non-interactive --agree-tos -m "$CERT_EMAIL" || echo "AVISO: certbot falhou para ${HOST}"
    fi
  fi
done

# nginx
if [[ -f "/etc/letsencrypt/live/${BRIDGE_HOST}/fullchain.pem" ]]; then
  install -m 644 "$STACK_DIR/bridge.estrato.cc.nginx.conf" \
    "/etc/nginx/conf.d/domains/${BRIDGE_HOST}.conf"
  echo "nginx ${BRIDGE_HOST} OK"
fi
if [[ -f "/etc/letsencrypt/live/${N8N_HOST}/fullchain.pem" ]]; then
  install -m 644 "$STACK_DIR/n8n.estrato.cc.nginx.conf" \
    "/etc/nginx/conf.d/domains/${N8N_HOST}.conf"
  echo "nginx ${N8N_HOST} OK"
fi
if nginx -t 2>/dev/null; then
  systemctl reload nginx
fi

# Re-bootstrap masto-rss (bot flag + feeds)
if docker ps --format '{{.Names}}' | grep -q '^estrato-gotosocial$'; then
  bash "$STACK_DIR/bootstrap-gotosocial.sh" || echo "AVISO: bootstrap masto-rss falhou"
fi

# FreshRSS OPML multi-feed (idempotente)
if docker ps --format '{{.Names}}' | grep -q '^estrato-freshrss$'; then
  ESTRATO_FRESHRSS_BASE_URL="${ESTRATO_FRESHRSS_BASE_URL:-https://rss.estrato.cc}" \
    bash "$STACK_DIR/setup-freshrss.sh" "$STACK_DIR" || true
fi

bash "$REPO/scripts/victor/check-package-e.sh" --strict || true

echo "=== Pacote E concluído ==="
echo "RSS-Bridge: https://${BRIDGE_HOST}/"
echo "n8n: https://${N8N_HOST}/"
echo "rss-filter: http://127.0.0.1:8090/"
