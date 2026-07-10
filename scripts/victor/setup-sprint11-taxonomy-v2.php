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

$taxonomy = estrato_rss_load_finance_taxonomy();
$version  = (int) ( $taxonomy['schema_version'] ?? 0 );
WP_CLI::log( "Schema taxonomia: v$version" );

$sync = estrato_rss_sync_portal_taxonomy();
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

$order = estrato_rss_get_finance_menu_order();
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
