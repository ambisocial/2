#!/usr/bin/env bash
# Carrega newsletter.env → opções WP (quando o usuário criar /root/.secrets/newsletter.env).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

ENV_FILE="${ESTRATO_NEWSLETTER_ENV:-/root/.secrets/newsletter.env}"
if [[ ! -f "$ENV_FILE" ]]; then
  echo "newsletter.env ausente ($ENV_FILE) — newsletter self-hosted (WP option) ativo"
  exit 0
fi

# shellcheck source=/dev/null
source "$ENV_FILE"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture)
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  [[ -n "${ESTRATO_NEWSLETTER_PROVIDER:-}" ]] && portal_wp option update estrato_newsletter_provider "$ESTRATO_NEWSLETTER_PROVIDER" 2>/dev/null || true
  [[ -n "${ESTRATO_NEWSLETTER_API_KEY:-}" ]] && portal_wp option update estrato_newsletter_api_key "$ESTRATO_NEWSLETTER_API_KEY" 2>/dev/null || true
  [[ -n "${ESTRATO_NEWSLETTER_LIST_ID:-}" ]] && portal_wp option update estrato_newsletter_list_id "$ESTRATO_NEWSLETTER_LIST_ID" 2>/dev/null || true
  echo "$PORTAL_ID newsletter provider=${ESTRATO_NEWSLETTER_PROVIDER:-none}"
done
echo "=== newsletter env aplicado ==="
