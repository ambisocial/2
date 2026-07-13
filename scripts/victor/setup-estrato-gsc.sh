#!/usr/bin/env bash
# Verifica e sincroniza Google Search Console (estrato.cc Victor)
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
KEY="${GSC_SERVICE_ACCOUNT_FILE:-/root/estrato-gsc-service-account.json}"
GSC_API="$REPO/scripts/victor/index-bot/gsc_api.py"

echo "=== Estrato GSC bot ==="

if [[ ! -f "$KEY" ]]; then
  echo "ERRO: chave ausente em $KEY"
  exit 1
fi
echo "Chave: $KEY OK"

if ! python3 "$GSC_API" sites; then
  echo ""
  echo "AÇÃO NECESSÁRIA (Search Console, não Google Cloud):"
  echo "  1. https://search.google.com/search-console"
  echo "  2. Propriedade estrato.cc"
  echo "  3. Configurações → Usuários e permissões → Adicionar:"
  echo "     estrato-gsc-bot@estrato-gsc.iam.gserviceaccount.com"
  echo "  4. Permissão: Usuário com acesso total"
  exit 1
fi

echo "--- sync métricas (7 dias) ---"
python3 "$REPO/scripts/victor/sync-gsc-metrics.py" || true

echo "--- inspecionar posts recentes (rede 6 portais) ---"
GSC_INDEX_LIMIT="${GSC_INDEX_LIMIT:-30}" \
GSC_INDEX_LIMIT_PER_PORTAL="${GSC_INDEX_LIMIT_PER_PORTAL:-5}" \
python3 "$REPO/scripts/victor/index-bot/gsc-index-recent.py" || true

CRON_GSC_SYNC='0 6 * * * python3 '"$REPO"'/scripts/victor/sync-gsc-metrics.py >> /var/log/estrato/gsc-sync.log 2>&1'
CRON_GSC_INDEX='*/20 * * * * GSC_INDEX_LIMIT=30 GSC_INDEX_LIMIT_PER_PORTAL=5 python3 '"$REPO"'/scripts/victor/index-bot/gsc-index-recent.py >> /var/log/estrato/gsc-index.log 2>&1'
if ! crontab -l 2>/dev/null | grep -qF 'sync-gsc-metrics.py'; then
  (crontab -l 2>/dev/null; echo "$CRON_GSC_SYNC") | crontab -
  echo "Cron diário GSC métricas instalado (06:00 UTC)"
fi
if crontab -l 2>/dev/null | grep -qF 'gsc-index-recent.py'; then
  crontab -l 2>/dev/null | grep -vF 'gsc-index-recent.py' | crontab -
fi
(crontab -l 2>/dev/null; echo "$CRON_GSC_INDEX") | crontab -
echo "Cron GSC inspeção rede instalado (*/20 min, até 30 URLs)"

echo "=== GSC OK ==="
