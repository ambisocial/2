<?php
/**
 * Hardening SEO/EEAT/GEO — reassign inventário, scrub LinkedIn fake,
 * path hubs+aliases, backfill retratos.
 *
 * Uso:
 *   wp --allow-root eval-file setup-seo-eeat-hardening.php
 *   ESTRATO_SKIP_PORTRAITS=1 wp --allow-root eval-file setup-seo-eeat-hardening.php
 *   ESTRATO_FORCE_PORTRAITS=1 wp --allow-root eval-file setup-seo-eeat-hardening.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$skip_portraits  = (string) getenv( 'ESTRATO_SKIP_PORTRAITS' ) === '1';
$force_portraits = (string) getenv( 'ESTRATO_FORCE_PORTRAITS' ) === '1';

$result = array(
	'portal' => function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '',
	'host'   => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
	'plugin' => defined( 'ESTRATO_PORTAL_BOOTSTRAP_VERSION' )
		? ESTRATO_PORTAL_BOOTSTRAP_VERSION
		: ( function_exists( 'get_plugin_data' ) ? '' : '1.36.2+' ),
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

if ( function_exists( 'estrato_entity_ensure_all_hubs' ) ) {
	$result['entity_hubs'] = array_keys( estrato_entity_ensure_all_hubs() );
}

if ( function_exists( 'estrato_network_ensure_path_hubs' ) ) {
	$result['path_hubs'] = array_keys( estrato_network_ensure_path_hubs() );
}

if ( function_exists( 'estrato_aeo_deploy_static_files' ) ) {
	$result['geo_files'] = estrato_aeo_deploy_static_files();
}

if ( ! $skip_portraits && function_exists( 'estrato_staff_backfill_portraits' ) ) {
	// Evita hang longo em Pollinations em massa: filtra HTTP opcionalmente.
	if ( (string) getenv( 'ESTRATO_PORTRAIT_FAST' ) === '1' ) {
		add_filter(
			'pre_http_request',
			static function ( $pre, $args, $url ) {
				if ( false !== stripos( (string) $url, 'pollinations.ai' ) ) {
					return new WP_Error( 'estrato_skip_pollinations', 'Portrait download skipped (FAST)' );
				}
				return $pre;
			},
			10,
			3
		);
	}
	$result['portraits'] = estrato_staff_backfill_portraits( $force_portraits );
} else {
	$result['portraits'] = array( 'skipped' => true );
}

if ( function_exists( 'flush_rewrite_rules' ) ) {
	flush_rewrite_rules( false );
}

// Invalida caches comuns de robots/HTML se existirem.
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}
if ( function_exists( 'rocket_clean_domain' ) ) {
	rocket_clean_domain();
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::success( wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
} else {
	echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
