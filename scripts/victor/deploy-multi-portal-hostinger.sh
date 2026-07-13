#!/usr/bin/env bash
# Deploy multi-portal via Hostinger API (boas práticas multi-categoria).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PORTAL="${ESTRATO_PORTAL:-estrato-finance}"
DOMAIN="${ESTRATO_DOMAIN:-estrato.cc}"

echo "=== Deploy Hostinger: $PORTAL @ $DOMAIN ==="

if [[ -z "${HOSTINGER_API_TOKEN:-}" ]]; then
  echo "ERRO: HOSTINGER_API_TOKEN não definido"
  exit 1
fi

cd "$ROOT"
PORTAL="$PORTAL" DOMAIN="$DOMAIN" node scripts/deploy-estrato-multi-portal.mjs

echo "=== Verificação pós-deploy ==="
curl -sS "https://${DOMAIN}/wp-json/estrato/v1/health" | head -c 500 || true
echo
