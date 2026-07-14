#!/usr/bin/env bash
# Higiene pós-boost: arquiva pré-2024 publicados + restaura pipeline_primary.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="${ESTRATO_REPO:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
# shellcheck source=/dev/null
source "$SCRIPT_DIR/lib/portal-env.sh"

PORTALS=(estrato-finance estrato-mind estrato-lifestyle estrato-science estrato-agro
  estrato-esg
  estrato-viagem estrato-culture)
for PORTAL_ID in "${PORTALS[@]}"; do
  portal_resolve "$PORTAL_ID"
  export ESTRATO_PORTAL="$PORTAL_ID" ESTRATO_REPO="$REPO"
  echo "── $PORTAL_ID ──"
  portal_wp eval '
$ids=get_posts(["post_type"=>"post","post_status"=>"publish","posts_per_page"=>-1,"fields"=>"ids","date_query"=>[["before"=>"2024-01-01","inclusive"=>false,"column"=>"post_date"]]]);
$n=0;
foreach($ids as $id){
  wp_update_post(["ID"=>(int)$id,"post_status"=>"draft"]);
  update_post_meta((int)$id,"_estrato_skip_reason","pre2024_sitemap");
  $n++;
}
echo "pre2024_drafted=$n\n";
if(function_exists("estrato_rss_apply_content_mode")){
  echo "rss_mode=".wp_json_encode(estrato_rss_apply_content_mode("pipeline_primary"))."\n";
}
'
done
