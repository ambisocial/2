#!/usr/bin/env bash
# Baixa woff2 (Fontsource) para estrato-portal-bootstrap/assets/fonts — zero CDN no front.
set -euo pipefail

REPO="${ESTRATO_REPO:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
FONT_DIR="${REPO}/estrato-portal-bootstrap/assets/fonts"
JSdelivr="https://cdn.jsdelivr.net/npm"

mkdir -p "$FONT_DIR"

download() {
  local pkg="$1"
  local file="$2"
  local dest="${FONT_DIR}/${file}"
  if [[ -s "$dest" ]]; then
    echo "skip $file"
    return 0
  fi
  echo "get $file"
  curl -fsSL "${JSdelivr}/${pkg}/files/${file}" -o "$dest"
}

# Inter 400–700
for w in 400 500 600 700; do
  download '@fontsource/inter@5.2.5' "inter-latin-${w}-normal.woff2"
done

# Newsreader 600–700
for w in 600 700; do
  download '@fontsource/newsreader@5.2.5' "newsreader-latin-${w}-normal.woff2"
done

# IBM Plex Mono 500
download '@fontsource/ibm-plex-mono@5.2.5' 'ibm-plex-mono-latin-500-normal.woff2'

echo "Fontes em ${FONT_DIR}:"
ls -lh "$FONT_DIR"/*.woff2 2>/dev/null | awk '{print $9, $5}'

# Sync para os 6 portais (se portal-env disponível)
if [[ -f "${REPO}/scripts/victor/lib/portal-env.sh" ]]; then
  # shellcheck source=/dev/null
  source "${REPO}/scripts/victor/lib/portal-env.sh"
  PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture estrato-politica estrato-esporte estrato-saude estrato-educacao estrato-tech estrato-carros)
  for PORTAL_ID in "${PORTALS[@]}"; do
    portal_resolve "$PORTAL_ID" 2>/dev/null || continue
    portal_sync_plugins 2>/dev/null || true
    portal_wp cache flush 2>/dev/null || true
    portal_wp eval 'echo function_exists("estrato_fonts_selfhosted_ready") && estrato_fonts_selfhosted_ready() ? "selfhosted=1" : "selfhosted=0";' 2>/dev/null || true
  done
fi

echo "=== self-hosted fonts OK ==="
