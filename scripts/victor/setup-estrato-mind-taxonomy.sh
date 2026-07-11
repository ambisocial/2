#!/usr/bin/env bash
# Taxonomia v2 — portal Conhecimento, Mente e Desenvolvimento Pessoal
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="${ESTRATO_WP:-/var/www/mente.estrato.cc}"
PORTAL="${ESTRATO_PORTAL:-estrato-mind}"

echo "=== Estrato Mente: Taxonomia + RSS + keywords ==="

# 0) Validar feeds (estático, fora do WP)
if [[ -f "$REPO/scripts/victor/validate-rss-feeds.py" ]]; then
  python3 "$REPO/scripts/victor/validate-rss-feeds.py" "$REPO/portals/estrato-mind-taxonomy.php" || true
fi

# 1) CI estático
if [[ -f "$REPO/scripts/victor/check-portal-regression-ci.sh" ]]; then
  bash "$REPO/scripts/victor/check-portal-regression-ci.sh" || true
fi

# 2) Config portal + preset brasil-mind
export ESTRATO_PORTAL="$PORTAL"
if [[ -f "$REPO/scripts/victor/apply-portal-config.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/apply-portal-config.php"
else
  echo "AVISO: apply-portal-config.php ausente — aplicando preset direto"
  sudo -u www-data wp --path="$WP" eval '
    if ( function_exists( "estrato_rss_apply_preset" ) ) {
      estrato_rss_apply_preset( "brasil-mind" );
    }
  '
fi

# 3) Curadoria RSS
if [[ -f "$REPO/scripts/victor/setup-sprint9-rss-curation.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/setup-sprint9-rss-curation.php"
fi

echo "=== Taxonomia Mente concluída ==="
echo "Fonte canônica: $REPO/portals/estrato-mind-taxonomy.php"
echo "Preset RSS: brasil-mind"
