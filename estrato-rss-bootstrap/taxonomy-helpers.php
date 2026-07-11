<?php
/**
 * Helpers compartilhados da taxonomia v2 (categorias, subcategorias, colunas).
 *
 * @package EstratoRssBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_TERM_TYPE_EDITORIA', 'editoria' );
define( 'ESTRATO_TERM_TYPE_SUBCATEGORY', 'subcategory' );
define( 'ESTRATO_TERM_TYPE_COLUMN', 'column' );

/**
 * @param array<string, mixed> $base
 * @param array<string, mixed> $override
 * @return array<string, mixed>
 */
function estrato_taxonomy_merge_config( $base, $override ) {
	if ( ! is_array( $override ) ) {
		return $base;
	}
	foreach ( $override as $key => $value ) {
		if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
			$base[ $key ] = estrato_taxonomy_merge_config( $base[ $key ], $value );
		} else {
			$base[ $key ] = $value;
		}
	}
	return $base;
}

/**
 * @param array<string, mixed> $taxonomy
 * @param array<string, mixed> $node
 * @return array<string, mixed>
 */
function estrato_taxonomy_resolve_branding( $taxonomy, $node ) {
	$defaults = $taxonomy['defaults']['branding'] ?? array();
	$node_br  = is_array( $node['branding'] ?? null ) ? $node['branding'] : array();
	return estrato_taxonomy_merge_config( $defaults, $node_br );
}

/**
 * @param array<string, mixed> $taxonomy
 * @param array<string, mixed> $node
 * @return array<string, mixed>
 */
function estrato_taxonomy_resolve_keywords( $taxonomy, $node ) {
	$defaults = $taxonomy['defaults']['keywords'] ?? array(
		'include'  => array(),
		'exclude'  => array(),
		'match_in' => array( 'title', 'excerpt', 'content' ),
	);
	$node_kw  = is_array( $node['keywords'] ?? null ) ? $node['keywords'] : array();
	return estrato_taxonomy_merge_config( $defaults, $node_kw );
}

/**
 * Mescla keywords de feed sobre o nó pai.
 *
 * @param array<string, mixed> $parent_kw
 * @param array<string, mixed> $feed
 * @return array<string, mixed>
 */
function estrato_taxonomy_feed_keywords( $parent_kw, $feed ) {
	$feed_kw = is_array( $feed['keywords'] ?? null ) ? $feed['keywords'] : array();
	if ( empty( $feed_kw ) ) {
		return $parent_kw;
	}
	$merged = $parent_kw;
	if ( ! empty( $feed_kw['include'] ) ) {
		$merged['include'] = array_values( array_unique( array_merge( $parent_kw['include'] ?? array(), $feed_kw['include'] ) ) );
	}
	if ( ! empty( $feed_kw['exclude'] ) ) {
		$merged['exclude'] = array_values( array_unique( array_merge( $parent_kw['exclude'] ?? array(), $feed_kw['exclude'] ) ) );
	}
	if ( ! empty( $feed_kw['match_in'] ) ) {
		$merged['match_in'] = $feed_kw['match_in'];
	}
	return $merged;
}

/**
 * @param int                  $term_id
 * @param string               $term_type editoria|subcategory|column
 * @param array<string, mixed> $node
 * @param array<string, mixed> $taxonomy
 */
function estrato_taxonomy_persist_term_meta( $term_id, $term_type, $node, $taxonomy ) {
	$branding = estrato_taxonomy_resolve_branding( $taxonomy, $node );
	$keywords = estrato_taxonomy_resolve_keywords( $taxonomy, $node );

	update_term_meta( $term_id, '_estrato_term_type', sanitize_key( $term_type ) );
	update_term_meta( $term_id, '_estrato_brand_name', sanitize_text_field( (string) ( $branding['brand_name'] ?? ( $node['name'] ?? '' ) ) ) );
	update_term_meta( $term_id, '_estrato_tagline', sanitize_text_field( (string) ( $node['tagline'] ?? '' ) ) );
	update_term_meta( $term_id, '_estrato_primary_color', estrato_taxonomy_sanitize_hex( $branding['primary_color'] ?? '' ) );
	update_term_meta( $term_id, '_estrato_accent_color', estrato_taxonomy_sanitize_hex( $branding['accent_color'] ?? '' ) );
	update_term_meta( $term_id, '_estrato_secondary_color', estrato_taxonomy_sanitize_hex( $branding['secondary_color'] ?? '' ) );
	update_term_meta( $term_id, '_estrato_header_variant', sanitize_key( (string) ( $branding['header_variant'] ?? 'editoria' ) ) );
	update_term_meta( $term_id, '_estrato_logo_file', sanitize_file_name( (string) ( $branding['logo_file'] ?? '' ) ) );
	update_term_meta( $term_id, '_estrato_show_in_nav', ! empty( $node['show_in_nav'] ) ? '1' : '0' );
	update_term_meta( $term_id, '_estrato_show_in_home_strip', ! empty( $node['show_in_home_strip'] ) ? '1' : '0' );
	update_term_meta( $term_id, '_estrato_keywords', wp_json_encode( $keywords ) );
}

/**
 * @param string $color
 * @return string
 */
function estrato_taxonomy_sanitize_hex( $color ) {
	$color = trim( (string) $color );
	if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color ) ) {
		return $color;
	}
	return '';
}

/**
 * @param int $term_id
 * @return array<string, mixed>
 */
function estrato_taxonomy_get_term_branding( $term_id ) {
	return array(
		'term_type'       => (string) get_term_meta( $term_id, '_estrato_term_type', true ),
		'brand_name'      => (string) get_term_meta( $term_id, '_estrato_brand_name', true ),
		'tagline'         => (string) get_term_meta( $term_id, '_estrato_tagline', true ),
		'primary_color'   => (string) get_term_meta( $term_id, '_estrato_primary_color', true ),
		'accent_color'    => (string) get_term_meta( $term_id, '_estrato_accent_color', true ),
		'secondary_color' => (string) get_term_meta( $term_id, '_estrato_secondary_color', true ),
		'header_variant'  => (string) get_term_meta( $term_id, '_estrato_header_variant', true ),
		'logo_file'       => (string) get_term_meta( $term_id, '_estrato_logo_file', true ),
		'show_in_nav'     => '1' === get_term_meta( $term_id, '_estrato_show_in_nav', true ),
		'show_in_home_strip' => '1' === get_term_meta( $term_id, '_estrato_show_in_home_strip', true ),
	);
}

/**
 * Matriz de importação RSS: cada nó (editoria, subcategoria, coluna) com feeds e keywords.
 *
 * @param array<string, mixed> $taxonomy
 * @return array<int, array<string, mixed>>
 */
function estrato_taxonomy_build_import_matrix( $taxonomy ) {
	$matrix = array();
	$cats   = $taxonomy['categories'] ?? array();
	$order  = $taxonomy['menu_order'] ?? array_keys( $cats );

	foreach ( $order as $cat_slug ) {
		if ( empty( $cats[ $cat_slug ] ) || ! is_array( $cats[ $cat_slug ] ) ) {
			continue;
		}
		$cat = $cats[ $cat_slug ];
		if ( ! empty( $cat['feeds'] ) ) {
			$matrix[] = estrato_taxonomy_matrix_node(
				$cat_slug,
				$cat,
				$taxonomy,
				ESTRATO_TERM_TYPE_EDITORIA,
				0
			);
		}
		if ( ! empty( $cat['subcategories'] ) && is_array( $cat['subcategories'] ) ) {
			foreach ( $cat['subcategories'] as $sub_slug => $sub ) {
				if ( ! is_array( $sub ) ) {
					continue;
				}
				$full_slug = sanitize_title( $cat_slug . '-' . $sub_slug );
				$sub_kw    = estrato_taxonomy_resolve_keywords( $taxonomy, $sub );
				$cat_kw    = estrato_taxonomy_resolve_keywords( $taxonomy, $cat );
				$merged    = estrato_taxonomy_merge_config( $cat_kw, $sub_kw );
				if ( ! empty( $sub['feeds'] ) ) {
					$matrix[] = estrato_taxonomy_matrix_node(
						$full_slug,
						array_merge( $sub, array( 'keywords' => $merged ) ),
						$taxonomy,
						ESTRATO_TERM_TYPE_SUBCATEGORY,
						0
					);
				}
			}
		}
	}

	$columns = $taxonomy['columns'] ?? array();
	foreach ( $columns as $col_slug => $col ) {
		if ( ! is_array( $col ) || empty( $col['category'] ) ) {
			continue;
		}
		$parent = sanitize_key( $col['category'] );
		$full   = sanitize_title( $parent . '-' . $col_slug );
		$matrix[] = estrato_taxonomy_matrix_node(
			$full,
			$col,
			$taxonomy,
			ESTRATO_TERM_TYPE_COLUMN,
			0
		);
	}

	return $matrix;
}

/**
 * @param string               $slug
 * @param array<string, mixed> $node
 * @param array<string, mixed> $taxonomy
 * @param string               $type
 * @param int                  $parent_term_id
 * @return array<string, mixed>
 */
function estrato_taxonomy_matrix_node( $slug, $node, $taxonomy, $type, $parent_term_id ) {
	$keywords = estrato_taxonomy_resolve_keywords( $taxonomy, $node );
	$feeds    = array();
	foreach ( $node['feeds'] ?? array() as $feed ) {
		if ( empty( $feed['url'] ) ) {
			continue;
		}
		$feeds[] = array(
			'title'    => (string) ( $feed['title'] ?? '' ),
			'url'      => (string) $feed['url'],
			'tier'     => isset( $feed['tier'] ) ? (int) $feed['tier'] : 2,
			'keywords' => estrato_taxonomy_feed_keywords( $keywords, $feed ),
		);
	}
	return array(
		'slug'           => $slug,
		'name'           => (string) ( $node['name'] ?? $node['brand_name'] ?? $slug ),
		'description'    => (string) ( $node['description'] ?? '' ),
		'type'           => $type,
		'parent_term_id' => $parent_term_id,
		'keywords'       => $keywords,
		'feeds'          => $feeds,
		'branding'       => estrato_taxonomy_resolve_branding( $taxonomy, $node ),
		'node'           => $node,
	);
}

/**
 * @return array<int, string>
 */
function estrato_taxonomy_get_column_order() {
	$taxonomy = function_exists( 'estrato_rss_load_active_taxonomy' )
		? estrato_rss_load_active_taxonomy()
		: array();
	if ( ! empty( $taxonomy['column_order'] ) && is_array( $taxonomy['column_order'] ) ) {
		return array_values( array_map( 'sanitize_title', $taxonomy['column_order'] ) );
	}
	return array();
}

/**
 * @return array<int, array<string, mixed>>
 */
function estrato_taxonomy_get_column_terms() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'meta_query' => array(
				array(
					'key'   => '_estrato_term_type',
					'value' => ESTRATO_TERM_TYPE_COLUMN,
				),
			),
		)
	);
	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}
	$out   = array();
	$order = estrato_taxonomy_get_column_order();
	foreach ( $terms as $term ) {
		$branding = estrato_taxonomy_get_term_branding( (int) $term->term_id );
		$out[]    = array(
			'term_id' => (int) $term->term_id,
			'slug'    => $term->slug,
			'name'    => $branding['brand_name'] ?: $term->name,
			'branding' => $branding,
		);
	}
	if ( $order ) {
		usort(
			$out,
			function ( $a, $b ) use ( $order ) {
				$ia = array_search( $a['slug'], $order, true );
				$ib = array_search( $b['slug'], $order, true );
				$ia = false === $ia ? 999 : $ia;
				$ib = false === $ib ? 999 : $ib;
				return $ia <=> $ib;
			}
		);
	}
	return $out;
}
