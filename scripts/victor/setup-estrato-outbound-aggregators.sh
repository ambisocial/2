#!/usr/bin/env bash
# Pacote F — agregadores externos (não self-hosted): IndexNow, PlatPhorm, GSC,
# Freespoke, Neo Times, MSN feed, Google News (sitemaps).
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="${ESTRATO_WP:-/var/www/estrato.cc}"
LOG_DIR="/var/log/estrato"
SECRETS="/root/.secrets"

echo "=== Estrato Pacote F: Outbound aggregators ==="

chmod 755 "$REPO/scripts/victor/syndicate-outbound.py" 2>/dev/null || true
mkdir -p "$LOG_DIR" "$SECRETS"
touch "$LOG_DIR/syndicate.log"
chmod 664 "$LOG_DIR/syndicate.log" 2>/dev/null || true

# Template de secrets (não sobrescreve existentes)
if [[ ! -f "$SECRETS/freespoke.env" ]]; then
  cat > "$SECRETS/freespoke.env.example" <<'EOF'
# Obtenha em https://docs.freespoke.com/developers/partner-api/
FREESPOKE_PUBLISHER_API_KEY=
# FREESPOKE_TEST_MODE=true
EOF
  chmod 600 "$SECRETS/freespoke.env.example"
  echo "AVISO: configure $SECRETS/freespoke.env (copie de .example)"
fi
if [[ ! -f "$SECRETS/neotimes.env" ]]; then
  cat > "$SECRETS/neotimes.env.example" <<'EOF'
# Solicite em https://thetimes.neoworlder.com/submissions
NEO_TIMES_API_TOKEN=
EOF
  chmod 600 "$SECRETS/neotimes.env.example"
  echo "AVISO: configure $SECRETS/neotimes.env (copie de .example)"
fi

# Plugin hook
if [[ -f "$WP/wp-content/plugins/estrato-portal-bootstrap/syndication.php" ]]; then
  echo "syndication.php hook OK"
else
  echo "AVISO: syndication.php ausente no plugin live"
fi

# MSN feed + backfill
echo "--- MSN feed ---"
python3 "$REPO/scripts/victor/syndicate-outbound.py" --generate-msn-feed --msn-limit 50

echo "--- backfill outbound (10 posts) ---"
python3 "$REPO/scripts/victor/syndicate-outbound.py" --recent 10 --ping-sitemaps || true

# Crons
CRON_SYN='5 * * * * python3 '"$REPO"'/scripts/victor/syndicate-outbound.py --recent 5 >> '"$LOG_DIR"'/syndicate.log 2>&1'
CRON_MSN='*/30 * * * * python3 '"$REPO"'/scripts/victor/syndicate-outbound.py --generate-msn-feed >> '"$LOG_DIR"'/msn-feed.log 2>&1'
CRON_MAPS='0 */6 * * * python3 '"$REPO"'/scripts/victor/syndicate-outbound.py --ping-sitemaps >> '"$LOG_DIR"'/sitemap-ping.log 2>&1'

for entry in "$CRON_SYN" "$CRON_MSN" "$CRON_MAPS"; do
  pattern=$(echo "$entry" | awk '{print $6}')
  if ! crontab -l 2>/dev/null | grep -qF "$pattern"; then
    (crontab -l 2>/dev/null; echo "$entry") | crontab -
    echo "Cron instalado: $pattern"
  fi
done

# GSC bot (se chave existir)
if [[ -f /root/estrato-gsc-service-account.json ]]; then
  bash "$REPO/scripts/victor/setup-estrato-gsc.sh" 2>/dev/null || true
fi

bash "$REPO/scripts/victor/check-outbound-aggregators.sh" --strict || true

echo "=== Pacote F concluído ==="
echo "MSN feed: https://estrato.cc/msn-feed.xml"
echo "Google News: algorítmico via news-sitemap.xml + GSC"
echo "MSN Partner Hub: submeter manualmente https://estrato.cc/msn-feed.xml"
