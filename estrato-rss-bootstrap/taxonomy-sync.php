<?php
/**
 * Sincronização YAML/PHP → preset RSS brasil-financeiro (Sprint 8+ / schema v2).
 *
 * @package EstratoRssBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/taxonomy-helpers.php';

define( 'ESTRATO_RSS_IMPORT_MATRIX_OPTION', 'estrato_rss_import_matrix' );

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
 * Converte taxonomia do portal em mapa de preset RSS (editorias — sem subcategorias/colunas).
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
 * Sincroniza preset ativo, categorias, subcategorias, colunas e matriz RSS.
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

	$categories = estrato_rss_create_categories_from_map( $preset, $taxonomy );
	$subcats    = estrato_rss_create_subcategories( $taxonomy, $categories );
	$columns    = estrato_rss_create_columns( $taxonomy, $categories );
	estrato_rss_noindex_legacy_terms( $taxonomy );

	$matrix = estrato_taxonomy_build_import_matrix( $taxonomy );
	update_option( ESTRATO_RSS_IMPORT_MATRIX_OPTION, $matrix, false );

	if ( function_exists( 'estrato_rss_apply_curation' ) ) {
		$curation = estrato_rss_apply_curation( false );
	} else {
		$curation = array();
	}

	estrato_rss_run_after_init(
		function () use ( $categories ) {
			estrato_rss_rebuild_menus( $categories );
			estrato_rss_rebuild_column_menu();
		}
	);

	return array(
		'ok'            => true,
		'schema_version' => (int) ( $taxonomy['schema_version'] ?? 1 ),
		'categories'    => count( $categories ),
		'subcategories' => $subcats,
		'columns'       => $columns,
		'import_nodes'  => count( $matrix ),
		'curation'      => $curation,
	);
}

/**
 * @param array<string, array{name:string, description:string, feeds:array}> $map
 * @param array<string, mixed>                                              $taxonomy
 * @return array<string, int>
 */
function estrato_rss_create_categories_from_map( $map, $taxonomy = array() ) {
	$created = array();
	foreach ( $map as $slug => $data ) {
		$cat_node = $taxonomy['categories'][ $slug ] ?? array();
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
		} else {
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
		if ( ! empty( $created[ $slug ] ) && $taxonomy ) {
			$node = is_array( $cat_node ) ? $cat_node : array( 'name' => $data['name'] );
			if ( empty( $node['branding']['brand_name'] ) ) {
				$node['branding']['brand_name'] = $data['name'];
			}
			estrato_taxonomy_persist_term_meta( $created[ $slug ], ESTRATO_TERM_TYPE_EDITORIA, $node, $taxonomy );
		}
	}
	return $created;
}

/**
 * @param array<string, mixed> $taxonomy
 * @param array<string, int>   $parents
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
				$term_id = (int) $existing->term_id;
				++$count;
			} else {
				$result = wp_insert_term(
					(string) ( $sub['name'] ?? $sub_slug ),
					'category',
					array(
						'slug'        => $full_slug,
						'description' => (string) ( $sub['description'] ?? '' ),
						'parent'      => $parent_id,
					)
				);
				if ( is_wp_error( $result ) ) {
					continue;
				}
				$term_id = (int) $result['term_id'];
				++$count;
			}
			estrato_taxonomy_persist_term_meta( $term_id, ESTRATO_TERM_TYPE_SUBCATEGORY, $sub, $taxonomy );
		}
	}
	return $count;
}

/**
 * @param array<string, mixed> $taxonomy
 * @param array<string, int>   $parents Editoria slug => term_id.
 * @return int
 */
function estrato_rss_create_columns( $taxonomy, $parents ) {
	$count   = 0;
	$columns = $taxonomy['columns'] ?? array();
	if ( empty( $columns ) || ! is_array( $columns ) ) {
		return 0;
	}
	foreach ( $columns as $col_slug => $col ) {
		if ( ! is_array( $col ) || empty( $col['category'] ) ) {
			continue;
		}
		$parent_slug = sanitize_key( $col['category'] );
		if ( empty( $parents[ $parent_slug ] ) ) {
			continue;
		}
		$parent_id = (int) $parents[ $parent_slug ];
		if ( ! empty( $col['subcategory'] ) ) {
			$sub_full = sanitize_title( $parent_slug . '-' . sanitize_key( $col['subcategory'] ) );
			$sub_term = get_term_by( 'slug', $sub_full, 'category' );
			if ( $sub_term && ! is_wp_error( $sub_term ) ) {
				$parent_id = (int) $sub_term->term_id;
			}
		}
		$full_slug = sanitize_title( $parent_slug . '-' . $col_slug );
		$name      = (string) ( $col['name'] ?? $col['brand_name'] ?? $col_slug );
		$existing  = get_term_by( 'slug', $full_slug, 'category' );
		if ( $existing ) {
			wp_update_term(
				(int) $existing->term_id,
				'category',
				array(
					'name'        => $name,
					'description' => (string) ( $col['description'] ?? '' ),
					'parent'      => $parent_id,
				)
			);
			$term_id = (int) $existing->term_id;
			++$count;
		} else {
			$result = wp_insert_term(
				$name,
				'category',
				array(
					'slug'        => $full_slug,
					'description' => (string) ( $col['description'] ?? '' ),
					'parent'      => $parent_id,
				)
			);
			if ( is_wp_error( $result ) ) {
				continue;
			}
			$term_id = (int) $result['term_id'];
			++$count;
		}
		if ( empty( $col['branding']['brand_name'] ) && ! empty( $col['brand_name'] ) ) {
			$col['branding']['brand_name'] = $col['brand_name'];
		}
		estrato_taxonomy_persist_term_meta( $term_id, ESTRATO_TERM_TYPE_COLUMN, $col, $taxonomy );
	}
	return $count;
}

/**
 * Menu secundário com colunas de nicho (show_in_nav).
 */
function estrato_rss_rebuild_column_menu() {
	$columns = estrato_taxonomy_get_column_terms();
	if ( ! $columns ) {
		return;
	}
	$nav_cols = array_filter(
		$columns,
		function ( $col ) {
			return ! empty( $col['branding']['show_in_nav'] );
		}
	);
	if ( ! $nav_cols ) {
		return;
	}

	$menu_name = 'Estrato Colunas';
	$existing  = wp_get_nav_menu_object( $menu_name );
	if ( $existing ) {
		wp_delete_nav_menu( $existing->term_id );
	}
	$menu_id = wp_create_nav_menu( $menu_name );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	foreach ( $nav_cols as $col ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $col['name'],
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'taxonomy',
				'menu-item-object-id' => $col['term_id'],
				'menu-item-object'    => 'category',
			)
		);
	}
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! isset( $locations['secondary'] ) ) {
		$locations['secondary'] = $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
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

/**
 * Resolve term_id para um nó da matriz de importação.
 *
 * @param array<string, mixed> $node
 * @return int
 */
function estrato_rss_resolve_matrix_term_id( $node ) {
	$slug = sanitize_title( (string) ( $node['slug'] ?? '' ) );
	if ( ! $slug ) {
		return 0;
	}
	$term = get_term_by( 'slug', $slug, 'category' );
	return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
}
