#!/usr/bin/env bash
# Ativa footer estilo FT (plugin) nos 6 portais — sync + cache.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  portal_sync_plugins
  portal_wp cache flush 2>/dev/null || true
  code=$(curl -sk -o /dev/null -w "%{http_code}" -H "Host: ${PORTAL_DOMAIN}" "https://${PORTAL_VPS_IP}/" 2>/dev/null || echo 000)
  has_ft=$(curl -sk -H "Host: ${PORTAL_DOMAIN}" "https://${PORTAL_VPS_IP}/" 2>/dev/null | grep -c 'estrato-ft-footer' || true)
  echo "$PORTAL_ID HTTP=$code ft_footer=$has_ft"
done

echo "Footer FT ativo via estrato-portal-bootstrap/nav-footer-ft.php"
