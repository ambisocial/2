<?php
/**
 * Sincronização YAML/PHP → preset RSS brasil-financeiro (Sprint 8).
 *
 * @package EstratoRssBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string
 */
function estrato_rss_taxonomy_file_path() {
	$candidates = array(
		'/var/www/estrato/repo/portals/estrato-finance-taxonomy.php',
		dirname( __DIR__ ) . '/portals/estrato-finance-taxonomy.php',
	);
	foreach ( $candidates as $path ) {
		if ( is_readable( $path ) ) {
			return $path;
		}
	}
	return '';
}

/**
 * @return array<string, mixed>
 */
function estrato_rss_load_finance_taxonomy() {
	$path = estrato_rss_taxonomy_file_path();
	if ( ! $path ) {
		return array();
	}
	$data = include $path;
	return is_array( $data ) ? $data : array();
}

/**
 * Converte taxonomia do portal em mapa de preset RSS (sem subcategorias).
 *
 * @param array<string, mixed> $taxonomy
 * @return array<string, array{name:string, description:string, feeds:array<int, array{title:string,url:string}>}>
 */
function estrato_rss_taxonomy_to_preset( $taxonomy ) {
	$out = array();
	if ( empty( $taxonomy['categories'] ) || ! is_array( $taxonomy['categories'] ) ) {
		return $out;
	}
	foreach ( $taxonomy['categories'] as $slug => $cat ) {
		if ( ! is_array( $cat ) ) {
			continue;
		}
		$out[ $slug ] = array(
			'name'        => (string) ( $cat['name'] ?? $slug ),
			'description' => (string) ( $cat['description'] ?? '' ),
			'feeds'       => isset( $cat['feeds'] ) && is_array( $cat['feeds'] ) ? $cat['feeds'] : array(),
		);
	}
	return $out;
}

/**
 * @return array<int, string>
 */
function estrato_rss_get_finance_menu_order() {
	$taxonomy = estrato_rss_load_finance_taxonomy();
	if ( ! empty( $taxonomy['menu_order'] ) && is_array( $taxonomy['menu_order'] ) ) {
		return array_values( array_map( 'sanitize_key', $taxonomy['menu_order'] ) );
	}
	return array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );
}

/**
 * Sincroniza preset ativo, categorias, subcategorias e menus.
 *
 * @return array<string, mixed>
 */
function estrato_rss_sync_portal_taxonomy() {
	$taxonomy = estrato_rss_load_finance_taxonomy();
	if ( empty( $taxonomy ) ) {
		return array( 'ok' => false, 'reason' => 'taxonomy_file_missing' );
	}

	$preset = estrato_rss_taxonomy_to_preset( $taxonomy );
	if ( empty( $preset ) ) {
		return array( 'ok' => false, 'reason' => 'empty_preset' );
	}

	update_option( ESTRATO_RSS_OPTION_PRESET, 'brasil-financeiro', false );
	update_option( ESTRATO_RSS_OPTION_FEEDS, $preset, false );

	$categories = estrato_rss_create_categories_from_map( $preset );
	$subcats    = estrato_rss_create_subcategories( $taxonomy, $categories );
	estrato_rss_noindex_legacy_terms( $taxonomy );

	estrato_rss_run_after_init(
		function () use ( $categories ) {
			estrato_rss_rebuild_menus( $categories );
		}
	);

	return array(
		'ok'            => true,
		'categories'    => count( $categories ),
		'subcategories' => $subcats,
	);
}

/**
 * @param array<string, array{name:string, description:string, feeds:array}> $map
 * @return array<string, int>
 */
function estrato_rss_create_categories_from_map( $map ) {
	$created = array();
	foreach ( $map as $slug => $data ) {
		$existing = get_term_by( 'slug', $slug, 'category' );
		if ( $existing ) {
			wp_update_term(
				(int) $existing->term_id,
				'category',
				array(
					'name'        => $data['name'],
					'description' => $data['description'],
				)
			);
			$created[ $slug ] = (int) $existing->term_id;
			continue;
		}
		$result = wp_insert_term(
			$data['name'],
			'category',
			array(
				'slug'        => $slug,
				'description' => $data['description'],
			)
		);
		if ( ! is_wp_error( $result ) ) {
			$created[ $slug ] = (int) $result['term_id'];
		}
	}
	return $created;
}

/**
 * @param array<string, mixed>        $taxonomy
 * @param array<string, int>          $parents
 * @return int
 */
function estrato_rss_create_subcategories( $taxonomy, $parents ) {
	$count = 0;
	if ( empty( $taxonomy['categories'] ) || ! is_array( $taxonomy['categories'] ) ) {
		return 0;
	}
	foreach ( $taxonomy['categories'] as $parent_slug => $cat ) {
		if ( empty( $cat['subcategories'] ) || ! is_array( $cat['subcategories'] ) ) {
			continue;
		}
		if ( empty( $parents[ $parent_slug ] ) ) {
			continue;
		}
		$parent_id = (int) $parents[ $parent_slug ];
		foreach ( $cat['subcategories'] as $sub_slug => $sub ) {
			if ( ! is_array( $sub ) ) {
				continue;
			}
			$full_slug = sanitize_title( $parent_slug . '-' . $sub_slug );
			$existing  = get_term_by( 'slug', $full_slug, 'category' );
			if ( $existing ) {
				wp_update_term(
					(int) $existing->term_id,
					'category',
					array(
						'name'        => (string) ( $sub['name'] ?? $sub_slug ),
						'description' => (string) ( $sub['description'] ?? '' ),
						'parent'      => $parent_id,
					)
				);
				++$count;
				continue;
			}
			$result = wp_insert_term(
				(string) ( $sub['name'] ?? $sub_slug ),
				'category',
				array(
					'slug'        => $full_slug,
					'description' => (string) ( $sub['description'] ?? '' ),
					'parent'      => $parent_id,
				)
			);
			if ( ! is_wp_error( $result ) ) {
				++$count;
			}
		}
	}
	return $count;
}

/**
 * @param array<string, mixed> $taxonomy
 */
function estrato_rss_noindex_legacy_terms( $taxonomy ) {
	$slugs = $taxonomy['legacy_noindex'] ?? array( 'politica', 'tecnologia', 'brasil', 'sem-categoria' );
	if ( ! is_array( $slugs ) ) {
		return;
	}
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', sanitize_key( $slug ), 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		update_term_meta( (int) $term->term_id, 'wpseo_noindex', 'noindex' );
	}
}
