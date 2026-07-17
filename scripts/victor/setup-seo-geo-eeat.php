<?php
/**
 * Setup SEO / GEO / AEO / EEAT — staff, FAQ, YMYL, entity hubs, path hubs.
 *
 * Uso:
 *   ESTRATO_PORTAL=estrato-finance wp eval-file setup-seo-geo-eeat.php
 *   # em todos os portais via setup-pending-all / loop
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$result = array(
	'portal' => function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '',
	'host'   => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
);

if ( function_exists( 'estrato_ymyl_ensure_institutional_pages' ) ) {
	$result['institutional'] = estrato_ymyl_ensure_institutional_pages();
}

if ( function_exists( 'estrato_staff_provision_current_portal' ) ) {
	$result['staff'] = estrato_staff_provision_current_portal();
}

if ( function_exists( 'estrato_staff_scrub_fake_same_as' ) ) {
	$result['same_as_scrub'] = estrato_staff_scrub_fake_same_as();
}

if ( function_exists( 'estrato_staff_reassign_inventory' ) && (string) getenv( 'ESTRATO_REASSIGN' ) === '1' ) {
	$result['reassign'] = estrato_staff_reassign_inventory( 0 );
}

if ( function_exists( 'estrato_entity_ensure_all_hubs' ) ) {
	$result['entity_hubs'] = array_keys( estrato_entity_ensure_all_hubs() );
}

if ( function_exists( 'estrato_network_ensure_path_hubs' ) ) {
	$result['path_hubs'] = array_keys( estrato_network_ensure_path_hubs() );
}

if ( function_exists( 'estrato_aeo_deploy_static_files' ) ) {
	$result['geo_files'] = estrato_aeo_deploy_static_files();
}

if ( function_exists( 'flush_rewrite_rules' ) ) {
	flush_rewrite_rules( false );
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::success( wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
} else {
	echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
