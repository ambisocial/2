#!/usr/bin/env bash
# Gera masto-rss-feeds.txt a partir de MASTO_RSS_EDITORIAS (slugs WP category).
# Opcional: MASTO_RSS_USE_FILTER=1 encaminha via rss-filter (rede Docker).
set -euo pipefail

STACK_DIR="$(cd "$(dirname "$0")" && pwd)"
OUT="${STACK_DIR}/masto-rss-feeds.txt"
BASE="${ESTRATO_FEED_BASE:-https://estrato.cc}"
FILTER_BASE="${MASTO_RSS_FILTER_BASE:-http://rss-filter}"
USE_FILTER="${MASTO_RSS_USE_FILTER:-0}"
# Editorias publicadas no Fediverse (filtro). Use "all" para todas.
EDITORIAS="${MASTO_RSS_EDITORIAS:-mercados,economia,negocios,financas-pessoais}"

ALL_SLUGS=(economia mercados negocios financas-pessoais criptomoedas agronegocio mundo)

urlencode() {
  python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1], safe=''))" "$1"
}

if [[ "$EDITORIAS" == "all" ]]; then
  SLUGS=("${ALL_SLUGS[@]}")
else
  IFS=',' read -ra SLUGS <<< "$EDITORIAS"
fi

: > "$OUT"
for slug in "${SLUGS[@]}"; do
  slug="$(echo "$slug" | tr -d ' ')"
  [[ -z "$slug" ]] && continue
  FEED_URL="${BASE}/category/${slug}/feed/"
  if [[ "$USE_FILTER" == "1" ]]; then
    ENC=$(urlencode "$FEED_URL")
    echo "${FILTER_BASE}/?feed_url=${ENC}&out=rss" >> "$OUT"
  else
    echo "$FEED_URL" >> "$OUT"
  fi
done

echo "masto-rss feeds (${#SLUGS[@]} editorias, filter=${USE_FILTER}) → $OUT"
