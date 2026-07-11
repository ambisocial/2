#!/usr/bin/env bash
# Cria conta bot + app OAuth para masto-rss (GoToSocial local).
set -euo pipefail

STACK_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="${STACK_DIR}/.env"
COOKIE_JAR="${STACK_DIR}/.gts-oauth-cookies"
GTS_URL="${GTS_URL:-http://127.0.0.1:8085}"
BOT_USER="${GTS_BOT_USER:-estrato_bot}"
BOT_EMAIL="${GTS_BOT_EMAIL:-estrato-bot@authors.estrato.cc}"
BOT_PASS="${GTS_BOT_PASS:-}"

cd "$STACK_DIR"

# Pacote C: OAuth deve usar o host público quando GoToSocial roda com GTS_PUBLIC_HOST.
GTS_PUBLIC_HOST_SAVED=""
GTS_PUBLIC_PROTOCOL_SAVED=""
GTS_TRUSTED_PROXIES_SAVED=""
if [[ -f "$ENV_FILE" ]]; then
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  GTS_PUBLIC_HOST_SAVED="${GTS_PUBLIC_HOST:-}"
  GTS_PUBLIC_PROTOCOL_SAVED="${GTS_PUBLIC_PROTOCOL:-}"
  GTS_TRUSTED_PROXIES_SAVED="${GTS_TRUSTED_PROXIES:-}"
  if [[ -n "${GTS_PUBLIC_HOST:-}" && "${GTS_PUBLIC_PROTOCOL:-}" == "https" ]]; then
    GTS_URL="${GTS_PUBLIC_PROTOCOL}://${GTS_PUBLIC_HOST}"
  fi
fi

if ! docker compose ps gotosocial 2>/dev/null | grep -qE 'running|Up'; then
  echo "GoToSocial não está rodando"
  exit 1
fi

# Reutiliza credenciais existentes quando válidas.
if [[ -f "$ENV_FILE" ]]; then
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  if [[ -n "${MASTODON_ACCESS_TOKEN:-}" ]]; then
    if curl -sf -H "Authorization: Bearer ${MASTODON_ACCESS_TOKEN}" \
      "${GTS_URL}/api/v1/accounts/verify_credentials" >/dev/null 2>&1; then
      docker compose up -d masto-rss
      echo "GoToSocial bot OK (token existente) — env em $ENV_FILE"
      exit 0
    fi
  fi
  BOT_PASS="${GTS_BOT_PASS:-$BOT_PASS}"
fi

if [[ -z "$BOT_PASS" ]]; then
  BOT_PASS="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)"
fi

# Conta local (idempotente — ignora se já existe).
docker compose exec -T gotosocial /gotosocial/gotosocial admin account create \
  --username "$BOT_USER" \
  --email "$BOT_EMAIL" \
  --password "$BOT_PASS" 2>/dev/null || true
docker compose exec -T gotosocial /gotosocial/gotosocial admin account confirm \
  --username "$BOT_USER" 2>/dev/null || true

# Registrar app OAuth (Mastodon-compatible API).
APP_JSON=$(curl -sS -X POST "${GTS_URL}/api/v1/apps" \
  -H 'Content-Type: application/json' \
  -d "{\"client_name\":\"EstratoRSS\",\"redirect_uris\":\"urn:ietf:wg:oauth:2.0:oob\",\"scopes\":\"read write\"}")

CLIENT_ID=$(echo "$APP_JSON" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('client_id',''))")
CLIENT_SECRET=$(echo "$APP_JSON" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('client_secret',''))")

if [[ -z "$CLIENT_ID" || -z "$CLIENT_SECRET" ]]; then
  echo "Falha ao registrar app OAuth: $APP_JSON"
  exit 1
fi

# GoToSocial 0.17+ não aceita password grant — fluxo authorization_code via curl.
AUTH_URL="${GTS_URL}/oauth/authorize?client_id=${CLIENT_ID}&redirect_uri=urn:ietf:wg:oauth:2.0:oob&response_type=code&scope=read+write"
rm -f "$COOKIE_JAR"
curl -sS -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$AUTH_URL" >/dev/null
curl -sS -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "${GTS_URL}/auth/sign_in" \
  -d "username=${BOT_EMAIL}&password=${BOT_PASS}" >/dev/null
curl -sS -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$AUTH_URL" >/dev/null
AUTH_HDRS=$(curl -sS -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "${GTS_URL}/oauth/authorize" -D - -o /dev/null)
AUTH_CODE=$(echo "$AUTH_HDRS" | grep -i '^Location:' | sed -n 's/.*code=//p' | tr -d '\r')
rm -f "$COOKIE_JAR"

if [[ -z "$AUTH_CODE" ]]; then
  echo "Falha ao obter authorization code (GoToSocial OAuth)"
  exit 1
fi

TOKEN_JSON=$(curl -sS -X POST "${GTS_URL}/oauth/token" \
  -H 'Content-Type: application/json' \
  -d "{\"redirect_uri\":\"urn:ietf:wg:oauth:2.0:oob\",\"client_id\":\"$CLIENT_ID\",\"client_secret\":\"$CLIENT_SECRET\",\"grant_type\":\"authorization_code\",\"code\":\"$AUTH_CODE\"}")

ACCESS_TOKEN=$(echo "$TOKEN_JSON" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('access_token',''))")

if [[ -z "$ACCESS_TOKEN" ]]; then
  echo "Falha ao obter token: $TOKEN_JSON"
  exit 1
fi

cat > "$ENV_FILE" <<EOF
MASTODON_CLIENT_ID=$CLIENT_ID
MASTODON_CLIENT_SECRET=$CLIENT_SECRET
MASTODON_ACCESS_TOKEN=$ACCESS_TOKEN
GTS_BOT_USER=$BOT_USER
GTS_BOT_PASS=$BOT_PASS
EOF
# Preserva variáveis Pacote C (bootstrap reescreve .env)
if [[ -n "${GTS_PUBLIC_HOST_SAVED:-}" ]]; then
  cat >> "$ENV_FILE" <<EOF
GTS_PUBLIC_HOST=${GTS_PUBLIC_HOST_SAVED}
GTS_PUBLIC_PROTOCOL=${GTS_PUBLIC_PROTOCOL_SAVED:-https}
GTS_TRUSTED_PROXIES=${GTS_TRUSTED_PROXIES_SAVED:-127.0.0.1/32,::1,172.16.0.0/12}
EOF
fi
chmod 600 "$ENV_FILE"

docker compose up -d masto-rss
echo "GoToSocial bot OK — env em $ENV_FILE"
