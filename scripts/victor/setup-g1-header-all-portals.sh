#!/usr/bin/env bash
# Ativa header estilo G1 (plugin) nos 6 portais — sync + cache + PHP reload.
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
  has_g1=$(curl -sk -H "Host: ${PORTAL_DOMAIN}" "https://${PORTAL_VPS_IP}/" 2>/dev/null | grep -c 'estrato-g1-header' || true)
  echo "$PORTAL_ID HTTP=$code g1_header=$has_g1"
done

systemctl reload php8.3-fpm 2>/dev/null || true
echo "Header G1 ativo via estrato-portal-bootstrap/nav-header-g1.php"
