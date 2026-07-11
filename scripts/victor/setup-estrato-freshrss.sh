#!/usr/bin/env bash
# Pacote D — FreshRSS público: rss.estrato.cc + usuário estrato-syndication
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
STACK_SRC="$REPO/scripts/victor/syndication-stack"
STACK_DIR="${ESTRATO_SYNDICATION_DIR:-/opt/estrato-syndication}"
NGINX_CONF="/etc/nginx/conf.d/domains/rss.estrato.cc.conf"
CF_SECRETS="${CLOUDFLARE_CREDENTIALS:-/root/.secrets/cloudflare.ini}"
VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"
RSS_HOST="${ESTRATO_RSS_HOST:-rss.estrato.cc}"
RSS_ZONE="${ESTRATO_RSS_ZONE:-estrato.cc}"
CERT_EMAIL="${ESTRATO_CERT_EMAIL:-tpb@ambi.social}"

echo "=== Estrato Pacote D: FreshRSS público ($RSS_HOST) ==="

mkdir -p "$STACK_DIR"
rsync -a "$STACK_SRC/" "$STACK_DIR/"
chmod +x "$STACK_DIR/setup-freshrss.sh" 2>/dev/null || true

cd "$STACK_DIR"
docker compose up -d freshrss

# 1) DNS Cloudflare
if [[ -f "$CF_SECRETS" ]]; then
  CF_EMAIL=$(grep -E '^dns_cloudflare_email' "$CF_SECRETS" | cut -d= -f2- | tr -d ' "')
  CF_KEY=$(grep -E '^dns_cloudflare_api_key' "$CF_SECRETS" | cut -d= -f2- | tr -d ' "')
  ZONE_ID=$(curl -sS -X GET "https://api.cloudflare.com/client/v4/zones?name=${RSS_ZONE}" \
    -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
    | python3 -c "import sys,json; r=json.load(sys.stdin).get('result',[]); print(r[0]['id'] if r else '')")
  if [[ -n "$ZONE_ID" ]]; then
    EXISTING=$(curl -sS -X GET \
      "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records?name=${RSS_HOST}" \
      -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
      | python3 -c "import sys,json; r=json.load(sys.stdin).get('result',[]); print(r[0]['id'] if r else '')")
    if [[ -z "$EXISTING" ]]; then
      curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/dns_records" \
        -H "X-Auth-Email: ${CF_EMAIL}" -H "X-Auth-Key: ${CF_KEY}" -H "Content-Type: application/json" \
        -d "{\"type\":\"A\",\"name\":\"rss\",\"content\":\"${VPS_IP}\",\"proxied\":true,\"ttl\":1}" \
        | python3 -c "import sys,json; d=json.load(sys.stdin); print('DNS criado' if d.get('success') else d)"
    else
      echo "DNS ${RSS_HOST} já existe"
    fi
  fi
else
  echo "AVISO: configure DNS manualmente: ${RSS_HOST} → ${VPS_IP}"
fi

# 2) TLS
if [[ ! -f "/etc/letsencrypt/live/${RSS_HOST}/fullchain.pem" ]]; then
  if [[ -f "$CF_SECRETS" ]] && command -v certbot >/dev/null 2>&1; then
    echo "Aguardando propagação DNS (45s)..."
    sleep 45
    certbot certonly --dns-cloudflare \
      --dns-cloudflare-credentials "$CF_SECRETS" \
      --dns-cloudflare-propagation-seconds 90 \
      -d "$RSS_HOST" \
      --non-interactive --agree-tos -m "$CERT_EMAIL" || echo "AVISO: certbot falhou"
  fi
fi

# 3) nginx
if [[ -f "/etc/letsencrypt/live/${RSS_HOST}/fullchain.pem" ]]; then
  install -m 644 "$STACK_DIR/rss.estrato.cc.nginx.conf" "$NGINX_CONF"
  nginx -t
  systemctl reload nginx
  echo "nginx ${RSS_HOST} OK"
  ESTRATO_FRESHRSS_BASE_URL="https://${RSS_HOST}"
else
  ESTRATO_FRESHRSS_BASE_URL="http://127.0.0.1:8088"
  echo "AVISO: FreshRSS permanece local até certificado existir"
fi

# 4) FreshRSS app config
export ESTRATO_FRESHRSS_BASE_URL
bash "$STACK_DIR/setup-freshrss.sh" "$STACK_DIR"

if [[ "$ESTRATO_FRESHRSS_BASE_URL" == https://* ]]; then
  docker exec -u www-data -w /var/www/FreshRSS estrato-freshrss \
    php ./cli/reconfigure.php \
      --base-url "$ESTRATO_FRESHRSS_BASE_URL" \
      --language pt-BR \
      --title "Estrato Syndication" 2>/dev/null || true
  docker exec estrato-freshrss cli/access-permissions.sh 2>/dev/null || true
fi

# 5) Cron refresh (complementa CRON_MIN do container)
CRON_RSS='*/20 * * * * docker exec -u www-data -w /var/www/FreshRSS estrato-freshrss php ./cli/actualize-user.php --user estrato-syndication >> /var/log/estrato/freshrss.log 2>&1'
mkdir -p /var/log/estrato
touch /var/log/estrato/freshrss.log
if ! crontab -l 2>/dev/null | grep -qF 'actualize-user.php --user estrato-syndication'; then
  (crontab -l 2>/dev/null; echo "$CRON_RSS") | crontab -
  echo "Cron FreshRSS (:20)"
fi

bash "$REPO/scripts/victor/check-freshrss.sh" --strict || true

echo "=== Pacote D concluído ==="
echo "FreshRSS: ${ESTRATO_FRESHRSS_BASE_URL}/"
