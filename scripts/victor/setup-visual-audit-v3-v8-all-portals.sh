#!/usr/bin/env bash
# Aplica as sprints V3-V8 da auditoria visual 2026-07-13:
#   V3 — RSS pipeline hardening (guard por matriz)
#   V5 — Single UX (kicker fallback + main_query guard)
#   V6 — URLs de categoria (rewrite legado + slugs redundantes de subcats)
#   V7 — OG default 1200×630 por portal
#   V8 — Higiene de drafts (>30d → trash) e trash (>60d → delete)
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

echo "== V3+V5+V6 — Sincronizando plugins (bootstrap + rss-bootstrap) =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo "-- $portal (https://$PORTAL_DOMAIN) --"
  sudo rsync -a --delete "$REPO_ROOT/estrato-portal-bootstrap/" "$PORTAL_WEB_ROOT/wp-content/plugins/estrato-portal-bootstrap/"
  sudo rsync -a --delete "$REPO_ROOT/estrato-rss-bootstrap/" "$PORTAL_WEB_ROOT/wp-content/plugins/estrato-rss-bootstrap/"
  portal_wp cache flush >/dev/null 2>&1 || true
  portal_wp plugin activate estrato-portal-bootstrap estrato-rss-bootstrap >/dev/null 2>&1 || true
  portal_wp rewrite flush >/dev/null 2>&1 || true
done

echo ""
echo "== V6 — Rename slugs de subcategorias com prefixo redundante =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo "-- $portal --"
  sudo -u www-data env "ESTRATO_SLUG_DRY_RUN=$DRY_RUN" wp --path="$PORTAL_WEB_ROOT" eval-file "$SCRIPT_DIR/fix-category-urls-and-slugs.php" 2>&1 | tail -30
  echo ""
done

echo ""
echo "== V7 — OG default 1200×630 =="
if [[ "$DRY_RUN" == "1" ]]; then
  echo "  (SKIP: dry-run)"
else
  bash "$SCRIPT_DIR/setup-og-default-1200x630.sh" 2>&1 | tail -60
fi

echo ""
echo "== V8 — Higiene de drafts (>30d) e trash (>60d) =="
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  echo "-- $portal --"
  sudo -u www-data env "ESTRATO_HYGIENE_DRY_RUN=$DRY_RUN" wp --path="$PORTAL_WEB_ROOT" eval-file "$SCRIPT_DIR/drafts-hygiene.php" 2>&1 | tail -15
  echo ""
done

echo ""
echo "== Verificação pós-deploy =="
echo ""
echo "-- og:image (esperado 1200x630) --"
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  aid=$(portal_wp option get estrato_og_default_attachment_id 2>/dev/null || echo 0)
  dim=$(portal_wp eval "\$m=wp_get_attachment_metadata($aid); echo \$m?\$m['width'].'x'.\$m['height']:'n/a';" 2>/dev/null)
  echo "  $portal aid=$aid dim=$dim"
done

echo ""
echo "-- /category-root/ redirect (esperado 301 → /category/slug/) --"
declare -A ROOTS=(
  [estrato-finance]='economia'
  [estrato-mind]='aprendizado-cognicao'
  [estrato-lifestyle]='sabores-paixao'
  [estrato-science]='ia-seguranca'
  [estrato-agro
  estrato-esg
  estrato-viagem]='agro-sustentavel'
  [estrato-culture]='narrativas-som'
)
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  slug="${ROOTS[$portal]}"
  code=$(curl -sIL --max-time 10 -o /dev/null -w "%{http_code}" "https://$PORTAL_DOMAIN/$slug/")
  echo "  $portal https://$PORTAL_DOMAIN/$slug/ → HTTP $code"
done

echo ""
echo "-- Contagens finais por portal --"
for portal in "${PORTALS[@]}"; do
  portal_resolve "$portal"
  pub=$(portal_wp post list --post_type=post --post_status=publish --format=count 2>/dev/null)
  drf=$(portal_wp post list --post_type=post --post_status=draft --format=count 2>/dev/null)
  trh=$(portal_wp post list --post_type=post --post_status=trash --format=count 2>/dev/null)
  echo "  $portal: publish=$pub draft=$drf trash=$trh"
done

echo ""
echo "== FIM =="
