<?php
/**
 * Executa itens do PLAN-SEO-9 em um portal.
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( getenv( 'ESTRATO_SKIP_NETWORK_BLOGS' ) === false || getenv( 'ESTRATO_SKIP_NETWORK_BLOGS' ) === '' ) {
	putenv( 'ESTRATO_SKIP_NETWORK_BLOGS=1' );
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
if ( function_exists( 'estrato_staff_reassign_inventory' ) ) {
	$result['reassign'] = estrato_staff_reassign_inventory( 0 );
}
if ( function_exists( 'estrato_ymyl_backfill_reviewed_meta' ) ) {
	$result['ymyl_reviewed'] = estrato_ymyl_backfill_reviewed_meta();
}
if ( function_exists( 'estrato_ymyl_seed_corrections_log' ) ) {
	$result['corrections'] = estrato_ymyl_seed_corrections_log( 15 );
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

$skip_portraits = (string) getenv( 'ESTRATO_SKIP_PORTRAITS' ) === '1';
if ( ! $skip_portraits && function_exists( 'estrato_staff_backfill_portraits' ) ) {
	$result['portraits'] = estrato_staff_backfill_portraits( false );
} else {
	$result['portraits'] = array( 'skipped' => $skip_portraits );
}

// Inventário fino: contagem de posts / hubs.
global $wpdb;
$result['inventory'] = array(
	'posts'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish'" ),
	'staff'   => function_exists( 'estrato_regression_staff_count' ) ? estrato_regression_staff_count() : 0,
	'avatars' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key='estrato_avatar_attachment_id' AND meta_value<>''" ),
);

if ( function_exists( 'flush_rewrite_rules' ) ) {
	flush_rewrite_rules( false );
}
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::success( wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
} else {
	echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
