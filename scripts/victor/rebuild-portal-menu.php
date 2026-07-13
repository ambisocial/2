<?php
/**
 * Reconstrói menu Estrato Principal (mega menu com subcategorias).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$preset = get_option( ESTRATO_RSS_OPTION_PRESET, 'brasil-financeiro' );
$preset = is_string( $preset ) ? sanitize_key( $preset ) : 'brasil-financeiro';

if ( function_exists( 'estrato_nav_rebuild_principal_menu' ) ) {
	$result = estrato_nav_rebuild_principal_menu();
	if ( function_exists( 'estrato_rss_rebuild_column_menu' ) ) {
		estrato_rss_rebuild_column_menu();
	}
	WP_CLI::success( sprintf(
		'Mega menu preset=%s pais=%d filhos=%d colunas=%d',
		$preset,
		(int) ( $result['parents'] ?? 0 ),
		(int) ( $result['children'] ?? 0 ),
		(int) ( $result['columns'] ?? 0 )
	) );
	exit( 0 );
}

$taxonomy = estrato_rss_load_taxonomy_by_preset( $preset );
$order    = $taxonomy['menu_order'] ?? $taxonomy['index_order'] ?? array();

if ( empty( $order ) && function_exists( 'estrato_rss_get_menu_order' ) ) {
	$order = estrato_rss_get_menu_order();
}

$parents = array();
foreach ( $order as $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( $term && ! is_wp_error( $term ) ) {
		$parents[ $slug ] = (int) $term->term_id;
	}
}

if ( empty( $parents ) ) {
	WP_CLI::error( "Sem categorias para menu preset={$preset}" );
}

$menu_name = 'Estrato Principal';
$existing  = wp_get_nav_menu_object( $menu_name );
if ( $existing ) {
	wp_delete_nav_menu( $existing->term_id );
}

$menu_id = wp_create_nav_menu( $menu_name );
if ( is_wp_error( $menu_id ) ) {
	WP_CLI::error( $menu_id->get_error_message() );
}

wp_update_nav_menu_item(
	$menu_id,
	0,
	array(
		'menu-item-title'  => 'Início',
		'menu-item-url'    => home_url( '/' ),
		'menu-item-status' => 'publish',
	)
);

foreach ( $order as $slug ) {
	if ( empty( $parents[ $slug ] ) ) {
		continue;
	}
	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-status'    => 'publish',
			'menu-item-type'      => 'taxonomy',
			'menu-item-object-id' => $parents[ $slug ],
			'menu-item-object'    => 'category',
		)
	);
}

$locations                = get_theme_mod( 'nav_menu_locations', array() );
$locations['primary']     = $menu_id;
$locations['top-menu']    = $menu_id;
$locations['footer-menu'] = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

$count = count( wp_get_nav_menu_items( $menu_id ) ?: array() );
WP_CLI::success( "Menu rebuilt preset={$preset} items={$count}" );
