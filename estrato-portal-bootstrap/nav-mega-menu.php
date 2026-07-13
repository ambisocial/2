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
	$loc = $args['theme_location'] ?? '';
	if ( in_array( $loc, array( 'primary', 'top-menu', 'footer-menu' ), true ) ) {
		$args['menu_class'] = trim( ( $args['menu_class'] ?? 'menu' ) . ' estrato-mega-nav estrato-g1-menu' );
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
 * Faixa de colunas em destaque (PressGrid não renderiza location secondary).
 */
function estrato_nav_columns_ribbon() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	if ( ! function_exists( 'estrato_taxonomy_get_column_terms' ) ) {
		return;
	}
	$cols = estrato_taxonomy_get_column_terms();
	$nav  = array_filter(
		$cols,
		function ( $col ) {
			return ! empty( $col['branding']['show_in_nav'] );
		}
	);
	if ( ! $nav ) {
		return;
	}
	echo '<nav class="estrato-columns-ribbon" aria-label="Colunas em destaque"><ul>';
	foreach ( $nav as $col ) {
		$link = get_category_link( $col['term_id'] );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$accent = ! empty( $col['branding']['accent_color'] ) ? $col['branding']['accent_color'] : '#9AFF33';
		echo '<li><a href="' . esc_url( $link ) . '" style="--estrato-col-accent:' . esc_attr( $accent ) . '">'
			. esc_html( $col['name'] ) . '</a></li>';
	}
	echo '</ul></nav>';
}
add_action( 'wp_body_open', 'estrato_nav_columns_ribbon', 8 );

/**
 * CSS do mega menu institucional.
 */
function estrato_nav_mega_menu_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<style id="estrato-mega-menu-css">
	.estrato-columns-ribbon{background:#0d1b2a;border-bottom:1px solid rgba(255,255,255,.08);font-size:.8125rem}
	.estrato-columns-ribbon ul{display:flex;flex-wrap:wrap;gap:.25rem 1.25rem;list-style:none;margin:0 auto;max-width:1200px;padding:.45rem 1rem}
	.estrato-columns-ribbon a{color:#e8eef5;font-weight:600;text-decoration:none;border-bottom:2px solid var(--estrato-col-accent,#9aff33);padding-bottom:1px}
	.estrato-columns-ribbon a:hover{color:#fff}
	.estrato-mega-nav>li.estrato-mega-parent{position:static}
	.estrato-mega-nav>li.estrato-mega-parent>.sub-menu{
		background:#fff;border:1px solid #e2e8f0;border-radius:0 0 6px 6px;box-shadow:0 12px 32px rgba(15,23,42,.12);
		display:none;left:0;list-style:none;margin:0;min-width:220px;padding:1rem 1.25rem;position:absolute;right:0;top:100%;z-index:9999;
		grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.35rem 1.5rem
	}
	.estrato-mega-nav>li.estrato-mega-parent:hover>.sub-menu,
	.estrato-mega-nav>li.estrato-mega-parent.estrato-mega-open>.sub-menu{display:grid}
	.estrato-mega-nav .sub-menu .menu-item{margin:0;padding:0}
	.estrato-mega-nav .sub-menu a{color:#1e293b;display:block;font-size:.875rem;font-weight:500;line-height:1.35;padding:.35rem 0;text-decoration:none}
	.estrato-mega-nav .sub-menu a:hover{color:#0f766e}
	.estrato-mega-nav .estrato-mega-column>a{font-weight:700;color:#0d1b2a;border-left:3px solid #9aff33;padding-left:.5rem}
	.estrato-mega-headlines{grid-column:1/-1;border-top:1px solid #e2e8f0;margin-top:.75rem;padding-top:.75rem;display:grid;gap:.35rem}
	.estrato-mega-headline{font-size:.8125rem;font-weight:600;color:#334155;text-decoration:none}
	.estrato-mega-headline:hover{color:#0b6e4f}
	@media (max-width:960px){
		.estrato-mega-nav>li.estrato-mega-parent{position:relative}
		.estrato-mega-nav>li.estrato-mega-parent>.sub-menu{
			position:relative;box-shadow:none;border:0;border-top:1px solid #e2e8f0;border-radius:0;
			grid-template-columns:1fr;padding:.75rem 0 .75rem 1rem
		}
	}
	</style>
	<?php
}
add_action( 'wp_head', 'estrato_nav_mega_menu_styles', 30 );

/**
 * Toggle mobile para submenus.
 */
function estrato_nav_mega_menu_scripts() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<script id="estrato-mega-menu-js">
	(function(){
		var nav=document.querySelector('.estrato-mega-nav');
		if(!nav||window.matchMedia('(min-width:961px)').matches)return;
		nav.addEventListener('click',function(e){
			var link=e.target.closest('a');
			if(!link)return;
			var li=link.parentElement;
			if(!li||!li.classList.contains('estrato-mega-parent'))return;
			if(li.querySelector('.sub-menu')){
				e.preventDefault();
				li.classList.toggle('estrato-mega-open');
			}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'estrato_nav_mega_menu_scripts', 30 );

/**
 * 2 manchetes recentes por editoria no mega menu (Sprint 5).
 *
 * @param string   $item_output
 * @param WP_Post  $item
 * @param int      $depth
 * @param stdClass $args
 * @return string
 */
function estrato_nav_mega_menu_headlines( $item_output, $item, $depth, $args ) {
	if ( 0 !== (int) $depth || 'taxonomy' !== $item->type || 'category' !== $item->object ) {
		return $item_output;
	}
	$term = get_term( (int) $item->object_id, 'category' );
	if ( ! $term || is_wp_error( $term ) || $term->parent ) {
		return $item_output;
	}
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 2,
			'cat'            => (int) $term->term_id,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	if ( ! $posts ) {
		return $item_output;
	}
	$extra = '<div class="estrato-mega-headlines">';
	foreach ( $posts as $post ) {
		$extra .= '<a class="estrato-mega-headline" href="' . esc_url( get_permalink( $post ) ) . '">'
			. esc_html( wp_trim_words( get_the_title( $post ), 10, '…' ) ) . '</a>';
	}
	$extra .= '</div>';
	return $item_output . $extra;
}
add_filter( 'walker_nav_menu_start_el', 'estrato_nav_mega_menu_headlines', 20, 4 );

