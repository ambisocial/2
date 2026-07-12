<?php
/**
 * Sprint F1 — sitemap por editoria + IndexNow (S20).
 *
 * - Yoast: categorias indexáveis, legado noindex
 * - IndexNow: URLs de editorias + posts recentes
 * - Ping sitemaps
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-f1-portal-indexnow.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: ( function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '' );

$legacy = array( 'politica', 'tecnologia', 'brasil', 'sem-categoria' );
foreach ( $legacy as $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( $term && ! is_wp_error( $term ) ) {
		update_term_meta( (int) $term->term_id, 'wpseo_noindex', 'noindex' );
	}
}

if ( function_exists( 'estrato_aeo_deploy_static_files' ) ) {
	estrato_aeo_write_indexnow_key_file();
	update_option( 'estrato_indexnow_key', estrato_aeo_get_indexnow_key(), false );
	update_option( 'estrato_bridge_indexnow_enabled', true, false );
}

$editorias = function_exists( 'estrato_aeo_portal_editorias' )
	? estrato_aeo_portal_editorias()
	: array();

$cat_indexed = 0;
if ( function_exists( 'estrato_rss_index_categories_indexnow' ) && $editorias ) {
	$cat_indexed = estrato_rss_index_categories_indexnow( $editorias );
}

$posts_indexed = function_exists( 'estrato_aeo_indexnow_recent_posts' )
	? estrato_aeo_indexnow_recent_posts( 40 )
	: 0;

if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
	WPSEO_Sitemaps_Cache::clear();
}

if ( function_exists( 'estrato_aeo_ping_sitemaps_cron' ) ) {
	estrato_aeo_ping_sitemaps_cron();
}

$cat_sitemap = 0;
$response    = wp_remote_get( home_url( '/category-sitemap.xml' ), array( 'timeout' => 15 ) );
if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
	$cat_sitemap = 1;
}

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'         => $portal,
			'editorias'      => count( $editorias ),
			'indexnow_cats'  => $cat_indexed,
			'indexnow_posts' => $posts_indexed,
			'category_sitemap' => $cat_sitemap,
		),
		JSON_UNESCAPED_UNICODE
	)
);
