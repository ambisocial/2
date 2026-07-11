<?php
/**
 * Sprint 11 — Taxonomia v2 (subcategorias, colunas, branding, keywords).
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	WP_CLI::error( 'estrato-rss-bootstrap não carregado.' );
}

$taxonomy = function_exists( 'estrato_rss_load_active_taxonomy' )
	? estrato_rss_load_active_taxonomy()
	: ( function_exists( 'estrato_rss_load_finance_taxonomy' ) ? estrato_rss_load_finance_taxonomy() : array() );
$version  = (int) ( $taxonomy['schema_version'] ?? 0 );
WP_CLI::log( "Schema taxonomia: v$version portal=" . ( $taxonomy['portal_id'] ?? 'estrato-finance' ) );

$preset = function_exists( 'estrato_rss_get_active_preset' ) ? estrato_rss_get_active_preset() : 'brasil-financeiro';
$sync   = estrato_rss_sync_portal_taxonomy( $preset );
if ( empty( $sync['ok'] ) ) {
	WP_CLI::warning( 'Sync taxonomia: ' . ( $sync['reason'] ?? 'falhou' ) );
} else {
	WP_CLI::log( sprintf(
		'Sync OK — categorias=%d subcategorias=%d colunas=%d nós RSS=%d',
		(int) ( $sync['categories'] ?? 0 ),
		(int) ( $sync['subcategories'] ?? 0 ),
		(int) ( $sync['columns'] ?? 0 ),
		(int) ( $sync['import_nodes'] ?? 0 )
	) );
}

$order_fn = 'estrato_rss_get_finance_menu_order';
if ( 'brasil-mind' === $preset && function_exists( 'estrato_rss_get_mind_menu_order' ) ) {
	$order_fn = 'estrato_rss_get_mind_menu_order';
} elseif ( 'brasil-lifestyle' === $preset && function_exists( 'estrato_rss_get_lifestyle_menu_order' ) ) {
	$order_fn = 'estrato_rss_get_lifestyle_menu_order';
} elseif ( 'brasil-science' === $preset && function_exists( 'estrato_rss_get_science_menu_order' ) ) {
	$order_fn = 'estrato_rss_get_science_menu_order';
} elseif ( 'brasil-sustain' === $preset && function_exists( 'estrato_rss_get_sustain_menu_order' ) ) {
	$order_fn = 'estrato_rss_get_sustain_menu_order';
} elseif ( 'brasil-culture' === $preset && function_exists( 'estrato_rss_get_culture_menu_order' ) ) {
	$order_fn = 'estrato_rss_get_culture_menu_order';
}
$order = $order_fn();
foreach ( $order as $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term ) {
		WP_CLI::log( "  [editoria] $slug — ausente" );
		continue;
	}
	$brand = get_term_meta( (int) $term->term_id, '_estrato_brand_name', true );
	$color = get_term_meta( (int) $term->term_id, '_estrato_primary_color', true );
	WP_CLI::log( "  [editoria] $slug — brand=$brand cor=$color" );
	$children = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => (int) $term->term_id,
			'hide_empty' => false,
		)
	);
	if ( ! is_wp_error( $children ) ) {
		foreach ( $children as $child ) {
			$type = get_term_meta( (int) $child->term_id, '_estrato_term_type', true );
			WP_CLI::log( "    [$type] {$child->slug} — {$child->name}" );
		}
	}
}

$cols = function_exists( 'estrato_taxonomy_get_column_terms' ) ? estrato_taxonomy_get_column_terms() : array();
WP_CLI::log( 'Colunas ativas: ' . count( $cols ) );
foreach ( $cols as $col ) {
	WP_CLI::log( "  [column] {$col['slug']} — {$col['name']}" );
}

WP_CLI::success( 'Taxonomia v2 aplicada.' );
