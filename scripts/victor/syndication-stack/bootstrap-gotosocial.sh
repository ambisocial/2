#!/usr/bin/env bash
# Cria conta bot + app OAuth para masto-rss (GoToSocial local).
set -euo pipefail

STACK_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="${STACK_DIR}/.env"
GTS_URL="${GTS_URL:-http://127.0.0.1:8085}"
BOT_USER="${GTS_BOT_USER:-estrato-bot}"
BOT_EMAIL="${GTS_BOT_EMAIL:-estrato-bot@authors.estrato.cc}"
BOT_PASS="${GTS_BOT_PASS:-$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)}"

cd "$STACK_DIR"

if ! docker compose ps gotosocial 2>/dev/null | grep -qE 'running|Up'; then
  echo "GoToSocial não está rodando"
  exit 1
fi

# Conta local (idempotente — ignora se já existe).
docker compose exec -T gotosocial /gotosocial/gotosocial admin account create \
  --username "$BOT_USER" \
  --email "$BOT_EMAIL" \
  --password "$BOT_PASS" 2>/dev/null || true

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

# Token via password grant (instância local).
TOKEN_JSON=$(curl -sS -X POST "${GTS_URL}/oauth/token" \
  -H 'Content-Type: application/json' \
  -d "{\"client_id\":\"$CLIENT_ID\",\"client_secret\":\"$CLIENT_SECRET\",\"grant_type\":\"password\",\"username\":\"$BOT_USER\",\"password\":\"$BOT_PASS\",\"scope\":\"read write\",\"redirect_uri\":\"urn:ietf:wg:oauth:2.0:oob\"}")

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
chmod 600 "$ENV_FILE"

docker compose up -d masto-rss
echo "GoToSocial bot OK — env em $ENV_FILE"
