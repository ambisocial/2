#!/usr/bin/env bash
# Pacote B — syndication outbound + stack Docker (FreshRSS, GoToSocial, masto-rss)
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="${ESTRATO_WP:-/var/www/estrato.cc}"
STACK_SRC="$REPO/scripts/victor/syndication-stack"
STACK_DIR="${ESTRATO_SYNDICATION_DIR:-/opt/estrato-syndication}"
LOG_DIR="/var/log/estrato"

echo "=== Estrato Pacote B: Syndication ==="
mkdir -p "$LOG_DIR" "$STACK_DIR"

# 1) Scripts Python
chmod 755 "$REPO/scripts/victor/syndicate-outbound.py" 2>/dev/null || true
touch "$LOG_DIR/syndicate.log"
chmod 664 "$LOG_DIR/syndicate.log" 2>/dev/null || true

# 2) Plugin syndication.php (deploy esperado via repo/plugins)
if [[ -f "$WP/wp-content/plugins/estrato-portal-bootstrap/syndication.php" ]]; then
  echo "Plugin syndication.php OK"
else
  echo "AVISO: syndication.php ausente no plugin — publique via deploy"
fi

# 3) Backfill últimos 10 posts
echo "--- backfill syndication (10 posts) ---"
python3 "$REPO/scripts/victor/syndicate-outbound.py" --recent 10 || true

# 4) Stack Docker
if command -v docker >/dev/null 2>&1; then
  echo "--- docker stack ---"
  rsync -a "$STACK_SRC/" "$STACK_DIR/"
  chmod +x "$STACK_DIR/bootstrap-gotosocial.sh" 2>/dev/null || true
  cd "$STACK_DIR"
  docker compose pull gotosocial freshrss 2>/dev/null || true
  docker compose up -d gotosocial freshrss
  mkdir -p "$STACK_DIR/gotosocial" && chown -R 1000:1000 "$STACK_DIR/gotosocial" 2>/dev/null || true
  for _ in $(seq 1 12); do
    if curl -sf "http://127.0.0.1:8085/.well-known/nodeinfo" >/dev/null 2>&1; then
      echo "GoToSocial OK (127.0.0.1:8085)"
      bash "$STACK_DIR/bootstrap-gotosocial.sh" || echo "AVISO: bootstrap masto-rss falhou (retry manual)"
      break
    fi
    sleep 5
  done
  if ! curl -sf "http://127.0.0.1:8085/.well-known/nodeinfo" >/dev/null 2>&1; then
    echo "AVISO: GoToSocial ainda iniciando — verifique docker logs estrato-gotosocial"
  fi

  # FreshRSS: instalação + usuário + feed via OPML (idempotente)
  if docker ps --format '{{.Names}}' | grep -q '^estrato-freshrss$'; then
    freshrss_cli() {
      docker exec -u www-data -w /var/www/FreshRSS estrato-freshrss \
        php "./cli/$1" "${@:2}"
    }
    if ! freshrss_cli list-users.php 2>/dev/null | grep -q .; then
      freshrss_cli do-install.php \
        --default-user admin \
        --environment production \
        --base-url http://127.0.0.1:8088 \
        --language pt-BR \
        --title "Estrato Syndication" \
        --auth-type form \
        --db-type sqlite
      freshrss_cli create-user.php \
        --user admin \
        --password "$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 18)" \
        --language pt-BR \
        --email admin@estrato.cc
    fi
    freshrss_cli create-user.php \
      --user estrato-syndication \
      --password "$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 18)" \
      --language pt-BR \
      --email syndication@estrato.cc \
      --no-default-feeds 2>/dev/null || true
    FEED_COUNT=$(freshrss_cli user-info.php --user estrato-syndication --json 2>/dev/null \
      | python3 -c "import sys,json; d=json.load(sys.stdin); print(d[0].get('feeds',0) if d else 0)" 2>/dev/null || echo 0)
    if [[ "${FEED_COUNT:-0}" == "0" ]]; then
      docker cp "$STACK_DIR/estrato-feed.opml" estrato-freshrss:/tmp/estrato-feed.opml
      freshrss_cli import-for-user.php \
        --user estrato-syndication \
        --filename /tmp/estrato-feed.opml 2>/dev/null || true
    fi
    freshrss_cli access-permissions.sh 2>/dev/null || true
    echo "FreshRSS OK (127.0.0.1:8088)"
  fi
else
  echo "AVISO: Docker ausente — pulando stack"
fi

# 5) Crons
CRON_SYN='5 * * * * python3 '"$REPO"'/scripts/victor/syndicate-outbound.py --recent 5 >> '"$LOG_DIR"'/syndicate.log 2>&1'
if ! crontab -l 2>/dev/null | grep -qF 'syndicate-outbound.py'; then
  (crontab -l 2>/dev/null; echo "$CRON_SYN") | crontab -
  echo "Cron horário syndication (:05)"
fi

# 6) Auditoria
bash "$REPO/scripts/victor/check-syndication.sh" --strict || true

echo "=== Pacote B concluído ==="
