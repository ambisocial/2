<?php
/**
 * Reconstrói menu Estrato Principal a partir do preset ativo.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$preset = estrato_rss_get_active_preset();
$taxonomy = estrato_rss_load_taxonomy_by_preset( $preset );
$map      = estrato_rss_taxonomy_to_preset( $taxonomy );
$parents  = array();
foreach ( array_keys( $map ) as $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( $term && ! is_wp_error( $term ) ) {
		$parents[ $slug ] = (int) $term->term_id;
	}
}
if ( empty( $parents ) ) {
	WP_CLI::error( 'Sem categorias parent para menu' );
}
estrato_rss_rebuild_menus( $parents );
$menu = wp_get_nav_menu_object( 'Estrato Principal' );
$count = $menu ? count( wp_get_nav_menu_items( $menu->term_id ) ?: array() ) : 0;
WP_CLI::success( "Menu rebuilt preset={$preset} items={$count}" );
