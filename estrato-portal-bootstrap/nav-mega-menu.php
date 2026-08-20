<?php
/**
 * Mega menu institucional — categorias, subcategorias e colunas em destaque.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomia ativa para construção do menu.
 *
 * @return array<string, mixed>
 */
function estrato_nav_menu_taxonomy() {
	if ( function_exists( 'estrato_rss_load_active_taxonomy' ) ) {
		$taxonomy = estrato_rss_load_active_taxonomy();
		if ( ! empty( $taxonomy ) ) {
			return $taxonomy;
		}
	}
	$preset = function_exists( 'estrato_rss_get_active_preset' )
		? estrato_rss_get_active_preset()
		: 'brasil-financeiro';
	if ( function_exists( 'estrato_rss_load_taxonomy_by_preset' ) ) {
		return estrato_rss_load_taxonomy_by_preset( $preset );
	}
	return array();
}

/**
 * Ordem de editorias no menu principal.
 *
 * @param array<string, mixed> $taxonomy
 * @return array<int, string>
 */
function estrato_nav_menu_editoria_order( $taxonomy ) {
	if ( ! empty( $taxonomy['menu_order'] ) && is_array( $taxonomy['menu_order'] ) ) {
		return array_values( array_map( 'sanitize_key', $taxonomy['menu_order'] ) );
	}
	if ( function_exists( 'estrato_rss_get_menu_order' ) ) {
		return estrato_rss_get_menu_order();
	}
	return array();
}

/**
 * Colunas show_in_nav agrupadas por editoria pai.
 *
 * @param array<string, mixed> $taxonomy
 * @return array<string, array<int, array{term_id:int,slug:string,name:string}>>
 */
function estrato_nav_columns_by_editoria( $taxonomy ) {
	$map = array();
	if ( empty( $taxonomy['columns'] ) || ! is_array( $taxonomy['columns'] ) ) {
		return $map;
	}
	foreach ( $taxonomy['columns'] as $col_slug => $col ) {
		if ( ! is_array( $col ) || empty( $col['category'] ) ) {
			continue;
		}
		$branding = is_array( $col['branding'] ?? null ) ? $col['branding'] : array();
		if ( empty( $col['show_in_nav'] ) && empty( $branding['show_in_nav'] ) ) {
			continue;
		}
		$parent_slug = sanitize_key( $col['category'] );
		$full_slug   = sanitize_title( $parent_slug . '-' . $col_slug );
		$term        = get_term_by( 'slug', $full_slug, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$name = (string) ( $branding['brand_name'] ?? $col['brand_name'] ?? $col['name'] ?? $term->name );
		if ( ! isset( $map[ $parent_slug ] ) ) {
			$map[ $parent_slug ] = array();
		}
		$map[ $parent_slug ][] = array(
			'term_id' => (int) $term->term_id,
			'slug'    => $full_slug,
			'name'    => $name,
		);
	}
	return $map;
}

/**
 * Subcategorias ordenadas (term_id) de uma editoria.
 *
 * @param string               $parent_slug
 * @param int                  $parent_id
 * @param array<string, mixed> $taxonomy
 * @return array<int, int>
 */
function estrato_nav_subcategory_term_ids( $parent_slug, $parent_id, $taxonomy ) {
	$ids  = array();
	$node = $taxonomy['categories'][ $parent_slug ] ?? array();
	$subs = is_array( $node['subcategories'] ?? null ) ? $node['subcategories'] : array();
	foreach ( array_keys( $subs ) as $sub_slug ) {
		$full_slug = sanitize_title( $parent_slug . '-' . sanitize_key( $sub_slug ) );
		$term      = get_term_by( 'slug', $full_slug, 'category' );
		if ( $term && ! is_wp_error( $term ) && (int) $term->parent === $parent_id ) {
			$ids[] = (int) $term->term_id;
		}
	}
	if ( $ids ) {
		return $ids;
	}
	$children = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => $parent_id,
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $children ) || ! $children ) {
		return array();
	}
	foreach ( $children as $child ) {
		$type = get_term_meta( (int) $child->term_id, '_estrato_term_type', true );
		$column_type = defined( 'ESTRATO_TERM_TYPE_COLUMN' ) ? ESTRATO_TERM_TYPE_COLUMN : 'column';
		if ( $column_type === $type ) {
			continue;
		}
		$ids[] = (int) $child->term_id;
	}
	return $ids;
}

/**
 * Reconstrói menu principal com subcategorias (mega menu) e colunas em destaque.
 *
 * @param array<string, int>|null $categories Mapa slug => term_id (opcional).
 * @return array{menu_id:int,parents:int,children:int,columns:int}
 */
function estrato_nav_rebuild_principal_menu( $categories = null ) {
	$taxonomy = estrato_nav_menu_taxonomy();
	$order    = estrato_nav_menu_editoria_order( $taxonomy );
	$col_map  = estrato_nav_columns_by_editoria( $taxonomy );

	if ( null === $categories ) {
		$categories = array();
		foreach ( $order as $slug ) {
			$term = get_term_by( 'slug', $slug, 'category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$categories[ $slug ] = (int) $term->term_id;
			}
		}
	}

	$menu_name = 'Estrato Principal';
	$existing  = wp_get_nav_menu_object( $menu_name );
	if ( $existing ) {
		wp_delete_nav_menu( $existing->term_id );
	}

	$menu_id = wp_create_nav_menu( $menu_name );
	if ( is_wp_error( $menu_id ) ) {
		return array(
			'menu_id'  => 0,
			'parents'  => 0,
			'children' => 0,
			'columns'  => 0,
		);
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

	$parents  = 0;
	$children = 0;
	$columns  = 0;

	foreach ( $order as $slug ) {
		if ( empty( $categories[ $slug ] ) ) {
			continue;
		}
		$parent_id = (int) $categories[ $slug ];
		++$parents;

		$parent_item = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'taxonomy',
				'menu-item-object-id' => $parent_id,
				'menu-item-object'    => 'category',
			)
		);

		if ( is_wp_error( $parent_item ) ) {
			continue;
		}

		$sub_ids = estrato_nav_subcategory_term_ids( $slug, $parent_id, $taxonomy );
		foreach ( $sub_ids as $sub_term_id ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-status'    => 'publish',
					'menu-item-type'      => 'taxonomy',
					'menu-item-object-id' => $sub_term_id,
					'menu-item-object'    => 'category',
					'menu-item-parent-id' => (int) $parent_item,
				)
			);
			++$children;
		}

		if ( ! empty( $col_map[ $slug ] ) ) {
			foreach ( $col_map[ $slug ] as $col ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $col['name'],
						'menu-item-status'    => 'publish',
						'menu-item-type'      => 'taxonomy',
						'menu-item-object-id' => $col['term_id'],
						'menu-item-object'    => 'category',
						'menu-item-parent-id' => (int) $parent_item,
						'menu-item-classes'   => 'estrato-nav-column-item',
					)
				);
				++$columns;
				++$children;
			}
		}
	}

	$locations                = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary']     = $menu_id;
	$locations['top-menu']    = $menu_id;
	$locations['footer-menu'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	return array(
		'menu_id'  => (int) $menu_id,
		'parents'  => $parents,
		'children' => $children,
		'columns'  => $columns,
	);
}

/**
 * Classe CSS no menu principal.
 *
 * @param array<string, mixed> $args
 * @return array<string, mixed>
 */
function estrato_nav_mega_menu_args( $args ) {
	$loc   = $args['theme_location'] ?? '';
	$class = (string) ( $args['menu_class'] ?? 'menu' );
	/*
	 * Trilho horizontal (estrato-g1-rail): NÃO herdar estrato-g1-menu / mega-nav.
	 * Essas classes forçam flex-direction:column no mobile e transformam o
	 * trilho em lista vertical com “botões apagados” (só aparecem no scroll).
	 */
	if ( false !== strpos( $class, 'estrato-g1-rail' ) ) {
		$args['menu_class'] = 'estrato-g1-rail';
		return $args;
	}
	if ( in_array( $loc, array( 'primary', 'top-menu', 'footer-menu' ), true ) ) {
		foreach ( array( 'estrato-mega-nav', 'estrato-g1-menu' ) as $need ) {
			if ( false === strpos( $class, $need ) ) {
				$class .= ' ' . $need;
			}
		}
		$args['menu_class'] = trim( $class );
	}
	return $args;
}
add_filter( 'wp_nav_menu_args', 'estrato_nav_mega_menu_args', 20 );

/**
 * Marca itens pai para layout mega.
 *
 * @param array<int, string> $classes
 * @param WP_Post            $item
 * @return array<int, string>
 */
function estrato_nav_mega_menu_item_classes( $classes, $item ) {
	if ( in_array( 'menu-item-has-children', $classes, true ) && 0 === (int) $item->menu_item_parent ) {
		$classes[] = 'estrato-mega-parent';
	}
	if ( in_array( 'estrato-nav-column-item', $classes, true ) ) {
		$classes[] = 'estrato-mega-column';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'estrato_nav_mega_menu_item_classes', 20, 2 );

/**
 * CSS do mega menu institucional.
 */
function estrato_nav_mega_menu_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$css = /* Base = mobile: submenu em fluxo. */
		'.estrato-mega-nav>li.estrato-mega-parent{position:relative}'
		. '.estrato-mega-nav>li.estrato-mega-parent>.sub-menu{background:transparent;border:0;border-top:1px solid #e2e8f0;border-radius:0;box-shadow:none;display:none;left:auto;list-style:none;margin:0;min-width:0;padding:.75rem 0 .75rem 1rem;position:relative;right:auto;top:auto;z-index:1;grid-template-columns:1fr;gap:0}'
		. '.estrato-mega-nav>li.estrato-mega-parent.estrato-mega-open>.sub-menu{display:grid}'
		. '.estrato-mega-nav .sub-menu .menu-item{margin:0;padding:0}'
		. '.estrato-mega-nav .sub-menu a{color:#1e293b;display:block;font-size:.875rem;font-weight:500;line-height:1.35;padding:.45rem 0;text-decoration:none;min-height:44px}'
		. '.estrato-mega-nav .sub-menu a:hover{color:#0f766e}'
		. '.estrato-mega-nav .estrato-mega-column>a{font-weight:700;color:#0d1b2a;border-left:3px solid #9aff33;padding-left:.5rem}'
		. '.estrato-mega-headlines{display:none!important}'
		. '@media(min-width:960px){'
		. '.estrato-mega-nav>li.estrato-mega-parent{position:static}'
		. '.estrato-mega-nav>li.estrato-mega-parent>.sub-menu{background:#fff;border:1px solid #e2e8f0;border-radius:0 0 6px 6px;box-shadow:0 12px 32px rgba(15,23,42,.12);left:0;min-width:220px;padding:1rem 1.25rem;position:absolute;right:0;top:100%;z-index:9999;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.35rem 1.5rem}'
		. '.estrato-mega-nav>li.estrato-mega-parent:hover>.sub-menu,.estrato-mega-nav>li.estrato-mega-parent:focus-within>.sub-menu,.estrato-mega-nav>li.estrato-mega-parent.estrato-mega-open>.sub-menu{display:grid}'
		. '.estrato-mega-nav .sub-menu a{min-height:0;padding:.35rem 0}'
		. '}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-mega-menu-css', $css, 'main' );
	} else {
		echo '<style id="estrato-mega-menu-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'estrato_nav_mega_menu_styles', 20 );

/**
 * Toggle mobile legado removido — chevron + teclado ficam em nav-header-g1.php.
 */
function estrato_nav_mega_menu_scripts() {
	/* no-op: handlers consolidados no header G1 */
}
add_action( 'wp_footer', 'estrato_nav_mega_menu_scripts', 30 );

/**
 * Manchetes no mega menu desativadas — menu só com editorias/subeditorias.
 *
 * @param string   $item_output
 * @param WP_Post  $item
 * @param int      $depth
 * @param stdClass $args
 * @return string
 */
function estrato_nav_mega_menu_headlines( $item_output, $item, $depth, $args ) {
	unset( $item, $depth, $args );
	return $item_output;
}
// Mantém a função (compat) mas não registra o filter — evita prévias de notícia no nav.

