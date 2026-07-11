#!/usr/bin/env bash
# Ajuste taxonomia v2 — categorias, subcategorias, RSS e keywords (estrato.cc)
set -euo pipefail

REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="${ESTRATO_WP:-/var/www/estrato.cc}"

echo "=== Estrato: Taxonomia + RSS + keywords ==="

# 1) CI estático (URLs duplicadas no PHP canônico)
if [[ -f "$REPO/scripts/victor/check-portal-regression-ci.sh" ]]; then
  bash "$REPO/scripts/victor/check-portal-regression-ci.sh" || true
fi

# 2) Sync taxonomia v2 (WP terms, matrix, autores)
if [[ -f "$REPO/scripts/victor/setup-sprint11-taxonomy-v2.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/setup-sprint11-taxonomy-v2.php"
else
  echo "AVISO: setup-sprint11-taxonomy-v2.php ausente"
fi

# 3) Curadoria RSS + health + pipeline_primary
if [[ -f "$REPO/scripts/victor/setup-sprint9-rss-curation.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/setup-sprint9-rss-curation.php"
else
  echo "AVISO: setup-sprint9-rss-curation.php ausente"
fi

# 4) PressGrid 1:1 (se sprint 8 disponível)
if [[ -f "$REPO/scripts/victor/setup-sprint8-taxonomy.php" ]]; then
  sudo -u www-data wp --path="$WP" eval-file "$REPO/scripts/victor/setup-sprint8-taxonomy.php" 2>/dev/null || true
fi

# 5) Auditoria
if [[ -f "$REPO/scripts/victor/check-portal-regression.sh" ]]; then
  bash "$REPO/scripts/victor/check-portal-regression.sh" --strict || true
fi

echo "=== Taxonomia RSS concluída ==="
echo "Fonte canônica: $REPO/portals/estrato-finance-taxonomy.php"
