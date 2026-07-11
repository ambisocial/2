#!/usr/bin/env bash
# Sprint 2 — schema NewsMediaOrganization, Yoast org/OG, anti-dup PressGrid.
set -euo pipefail

WEB="${WEB_ROOT:-/var/www/estrato.cc}"
REPO="${ESTRATO_REPO:-/var/www/estrato/repo}"
WP="sudo -u www-data wp --path=$WEB"

echo "=== Schema setup (Sprint 2) @ $WEB ==="

if [[ -d "$REPO/estrato-portal-bootstrap" ]]; then
  rsync -a "$REPO/estrato-portal-bootstrap/" "$WEB/wp-content/plugins/estrato-portal-bootstrap/"
fi

$WP plugin activate wordpress-seo estrato-portal-bootstrap --quiet

LOGO_ID=$($WP eval 'echo (int) get_theme_mod("custom_logo");' 2>/dev/null || echo "0")
OG_ID=$($WP post list --post_type=attachment --name=dolar_moeda_0803221210 --field=ID --format=csv 2>/dev/null | head -1)
if [[ -z "$OG_ID" || "$OG_ID" == "0" ]]; then
  OG_ID=$($WP post list --post_type=attachment --post_mime_type=image --orderby=ID --order=desc --field=ID --format=csv 2>/dev/null | head -1)
fi

# Yoast Organization / publisher
$WP option patch update wpseo_titles company_name 'Estrato'
$WP option patch update wpseo_titles company_or_person 'company'
$WP option patch update wpseo_titles website_name 'Estrato'
$WP option patch update wpseo_titles org-description 'Portal de economia, mercados financeiros e finanças pessoais no Brasil.'
$WP option patch update wpseo_titles schema-article-type-post 'NewsArticle'

if [[ -n "$LOGO_ID" && "$LOGO_ID" != "0" ]]; then
  $WP option patch update wpseo_titles company_logo_id "$LOGO_ID"
  LOGO_URL=$($WP post get "$LOGO_ID" --field=guid 2>/dev/null || true)
  if [[ -n "$LOGO_URL" ]]; then
    $WP option patch update wpseo_titles company_logo "$LOGO_URL"
  fi
  echo "company_logo_id=$LOGO_ID"
fi

# Social / sameAs
$WP option patch update wpseo_social opengraph true
$WP option patch update wpseo_social twitter true
$WP option patch update wpseo_social twitter_card_type 'summary_large_image'
$WP option patch update wpseo_social linkedin_url 'https://www.linkedin.com/company/estrato'
$WP option patch update wpseo_social instagram_url 'https://www.instagram.com/estrato.cc'
$WP option patch update wpseo_social twitter_site '@estrato_cc'
$WP option patch update wpseo_social facebook_site 'https://www.facebook.com/estrato.cc'

if [[ -n "$OG_ID" && "$OG_ID" != "0" ]]; then
  OG_URL=$($WP post get "$OG_ID" --field=guid 2>/dev/null || true)
  $WP option patch update wpseo_social og_default_image_id "$OG_ID"
  if [[ -n "$OG_URL" ]]; then
    $WP option patch update wpseo_social og_default_image "$OG_URL"
    $WP option patch update wpseo_social og_frontpage_image_id "$OG_ID"
    $WP option patch update wpseo_social og_frontpage_image "$OG_URL"
  fi
  echo "og_default_image_id=$OG_ID"
fi

# Autor padrão: jobTitle para schema Person
$WP user meta update 1 estrato_job_title 'Editor-chefe' 2>/dev/null || true
$WP user meta update 1 wpseo_job_title 'Editor-chefe' 2>/dev/null || true

$WP yoast index --reindex --skip-confirmation 2>/dev/null || true
systemctl restart php8.3-fpm-estrato.cc 2>/dev/null || systemctl restart php8.3-fpm 2>/dev/null || true

echo "--- validação schema ---"
HOME=$(curl -sk -H "Host: estrato.cc" "https://187.127.12.186/" 2>/dev/null || true)
echo "$HOME" | grep -oE "NewsMediaOrganization|NewsArticle|Organization" | sort | uniq -c || true
POST_URL=$($WP post list --post_type=post --post_status=publish --orderby=date --order=desc --field=url --format=csv 2>/dev/null | head -1)
if [[ -n "$POST_URL" ]]; then
  PATH_ONLY="${POST_URL#https://estrato.cc}"
  curl -sk -H "Host: estrato.cc" "https://187.127.12.186${PATH_ONLY}" 2>/dev/null | grep -oE "NewsArticle|@type\":\"Article" | sort | uniq -c || true
  curl -sk -H "Host: estrato.cc" "https://187.127.12.186${PATH_ONLY}" 2>/dev/null | grep -oE "article:published_time|article:modified_time|og:image" | head -5 || true
fi

echo "=== Sprint 2 Schema done ==="
