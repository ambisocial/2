#!/usr/bin/env bash
# Sprint 0 — Hotfix imediato (estrato.cc Victor)
# 0.1 Menu Colunas → secondary
# 0.2 /privacidade/ publicado
# 0.3 [estrato_home_columns] na home
# 0.4 Fix secondary ≠ primary
# 0.5 portal_id na taxonomia finance
# 0.6 CI dedup (repo)
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"
VPS_IP="${ESTRATO_VPS_IP:-187.127.12.186}"

log() { echo "[$(date -Iseconds)] S0 $*"; }

log "=== Sprint 0 — Hotfix estrato.cc ==="

# Sync repo + plugins
if [[ -d "$REPO" ]]; then
  rsync -a --delete "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
  rsync -a --delete "$REPO/estrato-rss-bootstrap/" "$WEB/wp-content/plugins/estrato-rss-bootstrap/"
  rsync -a "$REPO/portals/" "$WEB/wp-content/estrato-portals/"
fi

$WP plugin activate estrato-portal-bootstrap estrato-rss-bootstrap --quiet 2>/dev/null || true

# 0.5 + menus: re-sync taxonomia e colunas
log "Sync taxonomia v2 + menus"
$WP eval '
  if ( function_exists( "estrato_rss_sync_portal_taxonomy" ) ) {
    $r = estrato_rss_sync_portal_taxonomy( "brasil-financeiro" );
    echo wp_json_encode( $r ) . "\n";
  }
' 2>/dev/null || true

# 0.3 + 0.4: layout home com colunas + menu secondary correto
if [[ -f "$REPO/scripts/victor/setup-sprint8-taxonomy.php" ]]; then
  log "Aplicar layout Sprint 8 (colunas strip + menu fix)"
  $WP eval-file "$REPO/scripts/victor/setup-sprint8-taxonomy.php"
fi

# Garantir secondary = Estrato Colunas (não primary)
log "Atribuir Estrato Colunas → secondary"
$WP eval '
  $primary = wp_get_nav_menu_object( "Estrato Principal" );
  $columns = wp_get_nav_menu_object( "Estrato Colunas" );
  $loc = get_theme_mod( "nav_menu_locations", array() );
  if ( $primary ) {
    $loc["primary"] = (int) $primary->term_id;
  }
  if ( $columns ) {
    $loc["secondary"] = (int) $columns->term_id;
  }
  set_theme_mod( "nav_menu_locations", $loc );
  echo "primary=" . ( $loc["primary"] ?? 0 ) . " secondary=" . ( $loc["secondary"] ?? 0 ) . "\n";
'

# 0.2 Publicar /privacidade/
log "Publicar /privacidade/"
PRIV_ID=$($WP post list --post_type=page --format=ids --name=politica-de-privacidade 2>/dev/null | head -1 || true)
if [[ -z "$PRIV_ID" ]]; then
  PRIV_ID=$($WP post list --post_type=page --format=ids --name=privacidade 2>/dev/null | head -1 || true)
fi
if [[ -n "$PRIV_ID" ]]; then
  $WP post update "$PRIV_ID" --post_status=publish --post_name=privacidade 2>/dev/null || true
  log "Privacidade #$PRIV_ID publicada"
else
  log "AVISO: página privacidade não encontrada — rodar setup-institutional.php"
fi

$WP rewrite flush 2>/dev/null || true
$WP cache flush 2>/dev/null || true

log "=== Auditoria Sprint 0 ==="
PASS=0
FAIL=0

check() {
  local label="$1"
  shift
  if "$@"; then
    log "✅ $label"
    PASS=$((PASS + 1))
  else
    log "❌ $label"
    FAIL=$((FAIL + 1))
  fi
}

# 0.1/0.4 secondary menu
check "Menu secondary = Estrato Colunas" bash -c "
  $WP eval '
    \$loc = get_theme_mod(\"nav_menu_locations\", array());
    \$col = wp_get_nav_menu_object(\"Estrato Colunas\");
    \$pri = wp_get_nav_menu_object(\"Estrato Principal\");
    exit((\$col && isset(\$loc[\"secondary\"]) && (int)\$loc[\"secondary\"] === (int)\$col->term_id && (int)\$loc[\"primary\"] !== (int)\$loc[\"secondary\"]) ? 0 : 1);
  ' 2>/dev/null
"

# 0.2 privacidade
CODE=$(curl -sk -o /dev/null -w "%{http_code}" -H "Host: estrato.cc" "https://${VPS_IP}/privacidade/" 2>/dev/null || echo "000")
check "/privacidade/ HTTP $CODE" test "$CODE" = "200"

# 0.3 home columns strip
check "Home contém Radar B3 ou estrato-columns-strip" bash -c "
  HTML=\$(curl -sk -H 'Host: estrato.cc' 'https://${VPS_IP}/' 2>/dev/null)
  echo \"\$HTML\" | grep -qiE 'Radar B3|estrato-columns-strip'
"

# 0.5 portal_id
check "Taxonomia tem portal_id estrato-finance" bash -c "
  grep -q \"portal_id.*estrato-finance\" \"$REPO/portals/estrato-finance-taxonomy.php\"
"

# Layout sections inclui custom_html colunas
check "pressgrid_layout_sections tem colunas (custom_html)" bash -c "
  $WP eval '
    \$s = get_option(\"pressgrid_layout_sections\", array());
    foreach (\$s as \$row) {
      if ((\$row[\"id\"] ?? \"\") !== \"custom_html\") continue;
      \$h = \$row[\"custom_html\"] ?? \"\";
      if (strpos(\$h, \"estrato_home_columns\") !== false || strpos(\$h, \"estrato-columns-strip\") !== false || strpos(\$h, \"Radar B3\") !== false) exit(0);
    }
    exit(1);
  ' 2>/dev/null
"

log "RESULTADO Sprint 0: $PASS pass, $FAIL fail"
if [[ "$FAIL" -gt 0 ]]; then
  exit 1
fi
log "=== Sprint 0 APROVADO ==="
