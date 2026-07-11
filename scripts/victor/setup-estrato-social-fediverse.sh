#!/usr/bin/env bash
# Pacote C — Fediverse público: DNS social.estrato.cc + nginx + GoToSocial HTTPS
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
STACK_SRC="$REPO/scripts/victor/syndication-stack"
STACK_DIR="${ESTRATO_SYNDICATION_DIR:-/opt/estrato-syndication}"
NGINX_CONF="/etc/nginx/conf.d/domains/social.estrato.cc.conf"
CF_SECRETS="${CLOUDFLARE_CREDENTIALS:-/root/.secrets/cloudflare.ini}"
VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"
SOCIAL_HOST="${ESTRATO_SOCIAL_HOST:-social.estrato.cc}"
SOCIAL_ZONE="${ESTRATO_SOCIAL_ZONE:-estrato.cc}"
CERT_EMAIL="${ESTRATO_CERT_EMAIL:-tpb@ambi.social}"

echo "=== Estrato Pacote C: Fediverse público ($SOCIAL_HOST) ==="

mkdir -p "$STACK_DIR"
rsync -a "$STACK_SRC/" "$STACK_DIR/"
chmod +x "$STACK_DIR/bootstrap-gotosocial.sh" 2>/dev/null || true

# 1) DNS Cloudflare (A proxied, idempotente)
if [[ -f "$CF_SECRETS" ]]; then
  CF_EMAIL=$(grep -E '^dns_cloudflare_email' "$CF_SECRETS" | cut -d= -f2- | tr -d ' "')
  CF_KEY=$(grep -E '^dns_cloudflare_api_key' "$CF_SECRETS" | cut -d= -f2- | tr -d ' "')
  ZONE_ID=$(curl -sS -X GET "https://api.cloudflare.com/client/v4/zones?name=${SOCIAL_ZONE}" \
    -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
    | python3 -c "import sys,json; r=json.load(sys.stdin).get('result',[]); print(r[0]['id'] if r else '')")
  if [[ -n "$ZONE_ID" ]]; then
    EXISTING=$(curl -sS -X GET \
      "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records?name=${SOCIAL_HOST}" \
      -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
      | python3 -c "import sys,json; r=json.load(sys.stdin).get('result',[]); print(r[0]['id'] if r else '')")
    if [[ -z "$EXISTING" ]]; then
      curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records" \
        -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
        -d "{\"type\":\"A\",\"name\":\"social\",\"content\":\"${VPS_IP}\",\"proxied\":true,\"ttl\":1}" \
        | python3 -c "import sys,json; d=json.load(sys.stdin); print('DNS criado' if d.get('success') else d)"
    else
      echo "DNS ${SOCIAL_HOST} já existe"
    fi
  else
    echo "AVISO: zone Cloudflare ${SOCIAL_ZONE} não encontrada"
  fi
else
  echo "AVISO: ${CF_SECRETS} ausente — configure DNS manualmente: ${SOCIAL_HOST} → ${VPS_IP} (proxied)"
fi

# 2) TLS Let's Encrypt (DNS challenge)
if [[ ! -f "/etc/letsencrypt/live/${SOCIAL_HOST}/fullchain.pem" ]]; then
  if [[ -f "$CF_SECRETS" ]] && command -v certbot >/dev/null 2>&1; then
    echo "Aguardando propagação DNS (60s)..."
    sleep 60
    certbot certonly --dns-cloudflare \
      --dns-cloudflare-credentials "$CF_SECRETS" \
      --dns-cloudflare-propagation-seconds 90 \
      -d "$SOCIAL_HOST" \
      --non-interactive --agree-tos -m "$CERT_EMAIL" || echo "AVISO: certbot falhou (retry manual)"
  else
    echo "AVISO: certificado ausente — rode certbot para ${SOCIAL_HOST}"
  fi
fi

# 3) nginx reverse proxy
if [[ -f "/etc/letsencrypt/live/${SOCIAL_HOST}/fullchain.pem" ]]; then
  install -m 644 "$STACK_DIR/social.estrato.cc.nginx.conf" "$NGINX_CONF"
  nginx -t
  systemctl reload nginx
  echo "nginx ${SOCIAL_HOST} OK"
else
  echo "AVISO: pulando nginx até certificado existir"
fi

# 4) GoToSocial — domínio público (reset DB se host local anterior)
ENV_FILE="${STACK_DIR}/.env"
touch "$ENV_FILE"
PREV_HOST=$(grep -E '^GTS_PUBLIC_HOST=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- || true)
NEEDS_RESET=0
if [[ -f "${STACK_DIR}/gotosocial/sqlite.db" && "$PREV_HOST" != "$SOCIAL_HOST" ]]; then
  NEEDS_RESET=1
fi

grep -v -E '^(GTS_PUBLIC_HOST|GTS_PUBLIC_PROTOCOL|GTS_TRUSTED_PROXIES)=' "$ENV_FILE" > "${ENV_FILE}.tmp" 2>/dev/null || true
cat >> "${ENV_FILE}.tmp" <<EOF
GTS_PUBLIC_HOST=${SOCIAL_HOST}
GTS_PUBLIC_PROTOCOL=https
GTS_TRUSTED_PROXIES=127.0.0.1/32,::1,172.16.0.0/12
EOF
mv "${ENV_FILE}.tmp" "$ENV_FILE"
chmod 600 "$ENV_FILE"

if [[ "$NEEDS_RESET" == "1" ]]; then
  echo "--- reset GoToSocial DB (host local → público) ---"
  docker compose -f "$STACK_DIR/docker-compose.yml" stop gotosocial masto-rss 2>/dev/null || true
  BK="${STACK_DIR}/gotosocial/sqlite.db.bak.$(date +%Y%m%d%H%M%S)"
  cp -a "${STACK_DIR}/gotosocial/sqlite.db" "$BK" 2>/dev/null || true
  rm -f "${STACK_DIR}/gotosocial/sqlite.db" "${STACK_DIR}/gotosocial/sqlite.db-wal" "${STACK_DIR}/gotosocial/sqlite.db-shm"
  sed -i '/^MASTODON_/d' "$ENV_FILE" 2>/dev/null || true
fi

mkdir -p "${STACK_DIR}/gotosocial" && chown -R 1000:1000 "${STACK_DIR}/gotosocial" 2>/dev/null || true
cd "$STACK_DIR"
docker compose up -d gotosocial
for _ in $(seq 1 18); do
  if curl -sf "http://127.0.0.1:8085/.well-known/nodeinfo" >/dev/null 2>&1; then
    break
  fi
  sleep 5
done

if [[ "$NEEDS_RESET" == "1" ]] || ! grep -q '^MASTODON_ACCESS_TOKEN=' "$ENV_FILE" 2>/dev/null; then
  bash "$STACK_DIR/bootstrap-gotosocial.sh" || echo "AVISO: bootstrap masto-rss falhou"
fi

# 5) Auditoria
bash "$REPO/scripts/victor/check-social-fediverse.sh" --strict || true

echo "=== Pacote C concluído ==="
echo "Fediverse: https://${SOCIAL_HOST}/"
echo "Bot: https://${SOCIAL_HOST}/@estrato_bot"
