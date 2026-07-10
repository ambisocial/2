#!/usr/bin/env bash
# Sprint 4 — qualidade de conteúdo: enrich, thumbnails, linker, writer patch.
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
PIPE="/var/www/estrato/pipeline"
WP="sudo -u www-data wp --path=$WEB"

echo "=== estrato.cc Content setup (Sprint 4) ==="

if [[ -d "$REPO/estrato-portal-bootstrap" ]]; then
  rsync -a "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
fi
if [[ -d "$REPO/estrato-publisher-bridge" ]]; then
  rsync -a "$REPO/estrato-publisher-bridge/" "$WEB/wp-content/plugins/estrato-publisher-bridge/"
fi
if [[ -f "$REPO/scripts/victor/wp_bridge.py" ]]; then
  cp "$REPO/scripts/victor/wp_bridge.py" "$PIPE/wp_bridge.py"
fi

$WP plugin activate estrato-portal-bootstrap estrato-publisher-bridge --quiet

# Patch writer.py (mínimo 300 palavras / rascunho <200)
if [[ -f "$REPO/scripts/victor/patch-writer-s4.py" ]]; then
  python3 "$REPO/scripts/victor/patch-writer-s4.py" || echo "writer patch skipped"
fi

# Enriquecer posts finos em rodadas
echo "--- enrich thin posts ---"
for offset in $(seq 0 100 900); do
  out=$(ESTRATO_ENRICH_BATCH=100 ESTRATO_ENRICH_OFFSET="$offset" $WP eval-file "$REPO/scripts/victor/enrich-thin-posts.php" 2>/dev/null || true)
  echo "offset=$offset $out"
  updated=$(echo "$out" | grep -o '"updated": [0-9]*' | grep -o '[0-9]*' || echo 0)
  processed=$(echo "$out" | grep -o '"processed": [0-9]*' | head -1 | grep -o '[0-9]*' || echo 0)
  [[ "${processed:-0}" -eq 0 ]] && break
  [[ "${updated:-0}" -eq 0 && "$offset" -gt 0 ]] && break
done

# Linker interno WP
echo "--- internal links ---"
for _ in $(seq 1 12); do
  out=$(ESTRATO_LINKER_BATCH=80 $WP eval-file "$REPO/scripts/victor/wp-internal-linker.php" 2>/dev/null || true)
  echo "$out"
  updated=$(echo "$out" | grep -o '"updated": [0-9]*' | grep -o '[0-9]*' || echo 0)
  [[ "${updated:-0}" -eq 0 ]] && break
done

# Thumbnails
echo "--- thumbnail backfill ---"
if [[ -f "$REPO/scripts/victor/backfill-featured-images.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/backfill-featured-images.php" 2>/dev/null || true
fi
if [[ -f "$REPO/scripts/victor/backfill-fallback-thumbnails.php" ]]; then
  $WP eval-file "$REPO/scripts/victor/backfill-fallback-thumbnails.php" 2>/dev/null || true
fi

$WP yoast index --reindex --skip-confirmation 2>/dev/null || true
systemctl restart php8.3-fpm-estrato.cc 2>/dev/null || systemctl restart php8.3-fpm 2>/dev/null || true

echo "--- métricas conteúdo ---"
$WP eval '
if (!function_exists("estrato_regression_thin_posts")) { echo "helpers missing\n"; return; }
echo "thin<200=".estrato_regression_thin_posts()."\n";
echo "ratio300=".estrato_regression_word_ratio()."\n";
echo "no_thumb=".estrato_regression_posts_without_thumbnail()."\n";
echo "no_source=".estrato_regression_pipeline_without_source()."\n";
' 2>/dev/null

echo "=== Sprint 4 Content done ==="
