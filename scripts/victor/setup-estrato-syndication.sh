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
  chmod +x "$STACK_DIR/bootstrap-gotosocial.sh" \
    "$STACK_DIR/generate-masto-feeds.sh" \
    "$STACK_DIR/setup-freshrss.sh" 2>/dev/null || true
  mkdir -p "$STACK_DIR/rss-bridge-config" "$STACK_DIR/n8n-data"
  chown -R 1000:1000 "$STACK_DIR/n8n-data" 2>/dev/null || true
  bash "$STACK_DIR/generate-masto-feeds.sh" 2>/dev/null || true
  cd "$STACK_DIR"
  docker compose pull gotosocial freshrss rss-filter 2>/dev/null || true
  docker compose up -d gotosocial freshrss rss-filter
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

  # FreshRSS: usuário dedicado + feed OPML
  if docker ps --format '{{.Names}}' | grep -q '^estrato-freshrss$'; then
    chmod +x "$STACK_DIR/setup-freshrss.sh" 2>/dev/null || true
    ESTRATO_FRESHRSS_BASE_URL="http://127.0.0.1:8088" bash "$STACK_DIR/setup-freshrss.sh" "$STACK_DIR" || true
  fi
else
  echo "AVISO: Docker ausente — pulando stack"
fi

# 5) Crons
CRON_SYN='5 * * * * python3 '"$REPO"'/scripts/victor/syndicate-outbound.py --recent 5 >> '"$LOG_DIR"'/syndicate.log 2>&1'
CRON_MSN='*/30 * * * * python3 '"$REPO"'/scripts/victor/syndicate-outbound.py --generate-msn-feed >> '"$LOG_DIR"'/msn-feed.log 2>&1'
if ! crontab -l 2>/dev/null | grep -qF 'syndicate-outbound.py'; then
  (crontab -l 2>/dev/null; echo "$CRON_SYN") | crontab -
  echo "Cron horário syndication (:05)"
fi
if ! crontab -l 2>/dev/null | grep -qF 'generate-msn-feed'; then
  (crontab -l 2>/dev/null; echo "$CRON_MSN") | crontab -
  echo "Cron MSN feed (:30)"
fi

# MSN feed inicial
python3 "$REPO/scripts/victor/syndicate-outbound.py" --generate-msn-feed 2>/dev/null || true

# 6) Auditoria
bash "$REPO/scripts/victor/check-syndication.sh" --strict || true

echo "=== Pacote B concluído ==="
