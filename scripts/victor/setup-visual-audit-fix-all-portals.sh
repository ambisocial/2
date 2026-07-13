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
  estrato-sustain
  estrato-culture
)

DRY_RUN=""
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN="--dry-run"
  echo "== DRY-RUN =="
fi

echo "== V1: sincronizando plugin estrato-portal-bootstrap =="
BOOTSTRAP_SRC="$REPO_ROOT/estrato-portal-bootstrap"
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo "-- $portal ($PORTAL_URL) --"
  sudo rsync -a --delete "$BOOTSTRAP_SRC/" "$PORTAL_PATH/wp-content/plugins/estrato-portal-bootstrap/"
  portal_wp cache flush >/dev/null 2>&1 || true
  portal_wp plugin activate estrato-portal-bootstrap >/dev/null 2>&1 || true
done

echo ""
echo "== V2: purga posts off-matriz (satélites) =="
for portal in estrato-mind estrato-lifestyle estrato-science estrato-sustain estrato-culture; do
  portal_resolve "$portal"
  echo "-- $portal --"
  portal_wp eval-file "$SCRIPT_DIR/purge-off-matrix-posts.php" $DRY_RUN 2>&1 | tail -50
  echo ""
done

echo ""
echo "== Verificação post-deploy: métricas de regressão =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo "-- $portal --"
  portal_wp eval 'echo "off_matrix=" . estrato_regression_off_matrix_posts() . "\n";' 2>/dev/null || echo "  (função ainda não carregada)"
done

echo ""
echo "== Amostra og:image na home de cada portal (esperado: sem \"meta%20\") =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo -n "  $PORTAL_URL/ → "
  curl -sSL "$PORTAL_URL/" 2>/dev/null | grep -oE 'og:image"[^>]+' | head -1 | cut -c1-140 || true
  echo ""
done

echo ""
echo "== Amostra kicker na home (esperado: sem &AMP; ou dupla codificação) =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  count=$(curl -sSL "$PORTAL_URL/" 2>/dev/null | grep -oc '&amp;AMP;\|&AMP;' || echo 0)
  echo "  $PORTAL_URL/ → duplicações: $count"
done

echo ""
echo "== FIM =="
