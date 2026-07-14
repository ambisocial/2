#!/usr/bin/env bash
# Aplica as correções da auditoria visual 2026-07-13 (Sprints V1 + V2):
#   - B1: og:image corrompido → seo-yoast-defaults.php refatorado
#   - B3: kickers &AMP; → home-layout.php com mb_strtoupper + html_entity_decode
#   - B7: aria-label &amp; → home-layout.php
#   - B2 fase 1: purga posts off-matrix em cada satélite (trash)
#   - B2 fase 2: gate AR-CONTENT-006 registrado no gate-safeguards.php
#
# Referência: docs/ESTRATO-VISUAL-AUDIT-2026-07-13.md
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(
  estrato-finance
  estrato-mind
  estrato-lifestyle
  estrato-science
  estrato-agro
  estrato-esg
  estrato-viagem
  estrato-culture
)

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN=1
  echo "== DRY-RUN =="
fi
export ESTRATO_PURGE_DRY_RUN="$DRY_RUN"

echo "== V1: sincronizando plugin estrato-portal-bootstrap =="
BOOTSTRAP_SRC="$REPO_ROOT/estrato-portal-bootstrap"
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo "-- $portal (https://$PORTAL_DOMAIN) --"
  sudo rsync -a --delete "$BOOTSTRAP_SRC/" "$PORTAL_WEB_ROOT/wp-content/plugins/estrato-portal-bootstrap/"
  portal_wp cache flush >/dev/null 2>&1 || true
  portal_wp plugin activate estrato-portal-bootstrap >/dev/null 2>&1 || true
done

echo ""
echo "== V2: purga posts off-matriz (satélites) =="
for portal in estrato-mind estrato-lifestyle estrato-science estrato-agro estrato-esg estrato-viagem estrato-culture; do
  portal_resolve "$portal"
  echo "-- $portal --"
  sudo -u www-data env "ESTRATO_PURGE_DRY_RUN=$DRY_RUN" wp --path="$PORTAL_WEB_ROOT" eval-file "$SCRIPT_DIR/purge-off-matrix-posts.php" 2>&1 | tail -80
  echo ""
done

echo ""
echo "== Verificação post-deploy: métricas de regressão =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo -n "-- $portal → "
  portal_wp eval 'echo function_exists("estrato_regression_off_matrix_posts") ? "off_matrix=" . estrato_regression_off_matrix_posts() : "(fn ausente)";' 2>/dev/null
  echo ""
done

echo ""
echo "== Amostra og:image na home de cada portal (esperado: sem \"meta%20\") =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo -n "  https://$PORTAL_DOMAIN/ → "
  curl -sSL "https://$PORTAL_DOMAIN/" 2>/dev/null | grep -oE 'og:image"[^>]+' | head -1 | cut -c1-160 || true
  echo ""
done

echo ""
echo "== Amostra kicker na home (esperado: sem &AMP; ou dupla codificação) =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  count=$(curl -sSL "https://$PORTAL_DOMAIN/" 2>/dev/null | grep -oc '&amp;AMP;\|&AMP;' || true)
  echo "  https://$PORTAL_DOMAIN/ → duplicações: ${count:-0}"
done

echo ""
echo "== FIM =="
