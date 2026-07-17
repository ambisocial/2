#!/usr/bin/env bash
# Provisiona SEO/GEO/AEO/EEAT em todos os portais + landings na página-mãe.
# Uso (Victor): bash scripts/victor/setup-seo-geo-eeat-all.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg estrato-viagem estrato-culture estrato-politica estrato-esporte
  estrato-saude estrato-educacao estrato-tech estrato-carros)

for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID" ESTRATO_REPO="$REPO"
  echo "──────── SEO/GEO/EEAT $PORTAL_ID ($PORTAL_DOMAIN) ────────"
  portal_sync_plugins || true
  portal_wp eval-file "$REPO/scripts/victor/setup-seo-geo-eeat.php" || true
done

echo "=== setup-seo-geo-eeat-all done ==="
