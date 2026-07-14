#!/usr/bin/env bash
# Aplica política "só publicar com imagem original" nos 6 portais.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID"
  portal_sync_plugins
  portal_wp eval-file "$REPO/scripts/victor/purge-fallback-image-posts.php" 2>&1 || true
  portal_wp cache flush 2>/dev/null || true
  echo "$PORTAL_ID policy_applied"
done

systemctl reload php8.3-fpm 2>/dev/null || true
echo "Política imagem original ativa (sem fallback OG)"
