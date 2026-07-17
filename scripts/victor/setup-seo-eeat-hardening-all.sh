#!/usr/bin/env bash
# Roda hardening SEO/EEAT/GEO em todos os portais Estrato no Victor.
set -euo pipefail

ROOTS=(
  /var/www/estrato.cc
  /var/www/mente.estrato.cc
  /var/www/saude.estrato.cc
  /var/www/esporte.estrato.cc
  /var/www/science.estrato.cc
  /var/www/tech.estrato.cc
  /var/www/politica.estrato.cc
  /var/www/agro.estrato.cc
  /var/www/viagem.estrato.cc
  /var/www/educacao.estrato.cc
  /var/www/culture.estrato.cc
  /var/www/lifestyle.estrato.cc
  /var/www/esg.estrato.cc
  /var/www/carros.estrato.cc
  /var/www/sustain.estrato.cc
)

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
FILE="${SCRIPT_DIR}/setup-seo-eeat-hardening.php"
# Por padrão faz retratos; use ESTRATO_SKIP_PORTRAITS=1 para pular.
export ESTRATO_SKIP_PORTRAITS="${ESTRATO_SKIP_PORTRAITS:-0}"
export ESTRATO_FORCE_PORTRAITS="${ESTRATO_FORCE_PORTRAITS:-0}"
export ESTRATO_PORTRAIT_FAST="${ESTRATO_PORTRAIT_FAST:-0}"

for root in "${ROOTS[@]}"; do
  if [[ ! -f "$root/wp-config.php" ]]; then
    echo "SKIP missing $root"
    continue
  fi
  echo "==== HARDEN $(basename "$root") ===="
  wp --allow-root --path="$root" eval-file "$FILE" || echo "WARN fail $root"
done

echo "DONE hardening-all"
