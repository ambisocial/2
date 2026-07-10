#!/usr/bin/env bash
# Sprint 1 — robots, news-sitemap, Yoast titles pt_BR, limpeza legado.
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== estrato.cc SEO setup (Sprint 1) ==="

# Plugins atualizados
if [[ -d "$REPO/estrato-portal-bootstrap" ]]; then
  rsync -a "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
fi

$WP plugin activate wordpress-seo estrato-portal-bootstrap --quiet

# Yoast title templates pt_BR (sem "Archives")
$WP option patch update wpseo_titles title-home-wpseo 'Estrato | Economia, mercados e finanças'
$WP option patch update wpseo_titles metadesc-home-wpseo 'Notícias de economia, mercados financeiros, negócios e finanças pessoais. Acompanhe Ibovespa, dólar, Selic e mais.'
$WP option patch update wpseo_titles title-tax-category '%%term_title%%: notícias e análises %%page%% | Estrato'
$WP option patch update wpseo_titles metadesc-tax-category 'Acompanhe as últimas notícias de %%term_title%% no Estrato. Análises, dados e contexto para investidores.'
$WP option patch update wpseo_titles title-post '%%title%% | Estrato'
$WP option patch update wpseo_titles title-tax-post_tag '%%term_title%%: notícias %%page%% | Estrato'
$WP option patch update wpseo_titles social-title-tax-category '%%term_title%% | Estrato'
$WP option patch update wpseo_titles social-title-tax-post_tag '%%term_title%% | Estrato'

# Sitemap: posts e categorias ativas; excluir legado do índice
$WP option patch update wpseo_titles noindex-tax-category false
$WP option patch update wpseo_titles noindex-attachment true

# Ocultar categorias legado do sitemap (noindex por termo)
LEGACY_CATS=(politica tecnologia brasil sem-categoria)
for slug in "${LEGACY_CATS[@]}"; do
  tid=$($WP term list category --slug="$slug" --field=term_id 2>/dev/null || true)
  if [[ -n "$tid" && "$tid" != "0" ]]; then
    $WP term meta update "$tid" wpseo_noindex noindex 2>/dev/null || true
    echo "noindex categoria: $slug (#$tid)"
  fi
done

# Rewrite news-sitemap.xml
$WP rewrite flush --hard

# Limpeza legado + sem-categoria
if [[ -f "$REPO/scripts/victor/archive-legacy-posts.php" ]]; then
  echo "--- archive legacy posts ---"
  $WP eval-file "$REPO/scripts/victor/archive-legacy-posts.php"
fi

if [[ -f "$REPO/scripts/victor/fix-uncategorized.php" ]]; then
  echo "--- fix uncategorized ---"
  $WP eval-file "$REPO/scripts/victor/fix-uncategorized.php"
fi

# Purge Yoast sitemap cache
$WP yoast index --reindex 2>/dev/null || $WP eval 'if ( class_exists("WPSEO_Sitemaps_Cache") ) { WPSEO_Sitemaps_Cache::clear(); echo "sitemap cache cleared\n"; }'

echo "--- validação rápida ---"
curl -s "https://estrato.cc/robots.txt" | tail -15
code=$(curl -sI -o /dev/null -w "%{http_code}" "https://estrato.cc/news-sitemap.xml")
echo "news-sitemap.xml HTTP $code"

echo "=== Sprint 1 SEO done ==="
