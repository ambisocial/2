#!/usr/bin/env bash
# FreshRSS — instalação idempotente (usuário, feed OPML, refresh).
# Chamado por setup-estrato-syndication.sh e setup-estrato-freshrss.sh
set -euo pipefail

STACK_DIR="${1:?STACK_DIR required}"
RSS_USER="${ESTRATO_FRESHRSS_USER:-estrato-syndication}"
RSS_BASE_URL="${ESTRATO_FRESHRSS_BASE_URL:-http://127.0.0.1:8088}"

if ! docker ps --format '{{.Names}}' | grep -q '^estrato-freshrss$'; then
  echo "FreshRSS container não está rodando"
  exit 1
fi

freshrss_cli() {
  docker exec -u www-data -w /var/www/FreshRSS estrato-freshrss \
    php "./cli/$1" "${@:2}"
}

if ! freshrss_cli list-users.php 2>/dev/null | grep -q .; then
  ADMIN_PASS="$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 18)"
  freshrss_cli do-install.php \
    --default-user admin \
    --environment production \
    --base-url "$RSS_BASE_URL" \
    --language pt-BR \
    --title "Estrato Syndication" \
    --auth-type form \
    --db-type sqlite
  freshrss_cli create-user.php \
    --user admin \
    --password "$ADMIN_PASS" \
    --language pt-BR \
    --email admin@estrato.cc
  echo "FreshRSS admin criado (senha em /opt/estrato-syndication/freshrss-admin.pass)"
  echo "$ADMIN_PASS" > "${STACK_DIR}/freshrss-admin.pass"
  chmod 600 "${STACK_DIR}/freshrss-admin.pass"
fi

if ! freshrss_cli list-users.php 2>/dev/null | grep -q "^${RSS_USER}$"; then
  SYND_PASS="$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 18)"
  freshrss_cli create-user.php \
    --user "$RSS_USER" \
    --password "$SYND_PASS" \
    --language pt-BR \
    --email syndication@estrato.cc
  echo "$SYND_PASS" > "${STACK_DIR}/freshrss-syndication.pass"
  chmod 600 "${STACK_DIR}/freshrss-syndication.pass"
  echo "FreshRSS usuário ${RSS_USER} criado"
fi

OPML_FILE="${STACK_DIR}/estrato-feeds.opml"
[[ -f "$OPML_FILE" ]] || OPML_FILE="${STACK_DIR}/estrato-feed.opml"
FEED_COUNT=$(freshrss_cli user-info.php --user "$RSS_USER" --json 2>/dev/null \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(d[0].get('feeds',0) if d else 0)" 2>/dev/null || echo 0)
if [[ "${FEED_COUNT:-0}" -lt 7 ]]; then
  docker cp "$OPML_FILE" estrato-freshrss:/tmp/estrato-feeds.opml
  freshrss_cli import-for-user.php \
    --user "$RSS_USER" \
    --filename /tmp/estrato-feeds.opml
  echo "FreshRSS OPML multi-feed importado para ${RSS_USER}"
fi

freshrss_cli actualize-user.php --user "$RSS_USER" 2>/dev/null || true
docker exec estrato-freshrss cli/access-permissions.sh 2>/dev/null || true

FEED_COUNT=$(freshrss_cli user-info.php --user "$RSS_USER" --json 2>/dev/null \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(d[0].get('feeds',0) if d else 0)" 2>/dev/null || echo 0)
UNREAD=$(freshrss_cli user-info.php --user "$RSS_USER" --json 2>/dev/null \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print(d[0].get('reads',0) if d else 0)" 2>/dev/null || echo 0)
echo "FreshRSS OK — ${RSS_USER}: ${FEED_COUNT} feeds, ${UNREAD} lidos"
