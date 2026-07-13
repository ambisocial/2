<?php
/**
 * Archives — chips de subcategoria + breadcrumb (P1 auditoria).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param WP_Term $term
 * @return array<int, WP_Term>
 */
function estrato_archive_subcategory_chips( $term ) {
	if ( ! $term || is_wp_error( $term ) ) {
		return array();
	}
	$children = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => (int) $term->term_id,
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	return is_wp_error( $children ) ? array() : $children;
}

/**
 * @param int $term_id
 * @return array<int, array{label:string,url:string}>
 */
function estrato_archive_breadcrumb_trail( $term_id = 0 ) {
	$trail = array(
		array(
			'label' => 'Início',
			'url'   => home_url( '/' ),
		),
	);

	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$deepest = $cats[0];
			foreach ( $cats as $cat ) {
				if ( $cat->parent > $deepest->parent ) {
					$deepest = $cat;
				}
			}
			$term_id = (int) $deepest->term_id;
		}
	}

	if ( ! $term_id && is_category() ) {
		$term = get_queried_object();
		$term_id = $term ? (int) $term->term_id : 0;
	}

	if ( ! $term_id ) {
		return $trail;
	}

	$ancestors = array_reverse( get_ancestors( $term_id, 'category' ) );
	foreach ( $ancestors as $ancestor_id ) {
		$ancestor = get_term( (int) $ancestor_id, 'category' );
		if ( $ancestor && ! is_wp_error( $ancestor ) ) {
			$trail[] = array(
				'label' => $ancestor->name,
				'url'   => get_category_link( $ancestor ),
			);
		}
	}

	$term = get_term( $term_id, 'category' );
	if ( $term && ! is_wp_error( $term ) ) {
		$trail[] = array(
			'label' => $term->name,
			'url'   => get_category_link( $term ),
		);
	}

	if ( is_singular( 'post' ) ) {
		$trail[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	}

	return $trail;
}

/**
 * @param array<int, array{label:string,url:string}> $trail
 * @return string
 */
function estrato_archive_render_breadcrumb( $trail ) {
	if ( count( $trail ) < 2 ) {
		return '';
	}
	$html = '<nav class="estrato-breadcrumb" aria-label="Breadcrumb"><ol>';
	$last = count( $trail ) - 1;
	foreach ( $trail as $i => $crumb ) {
		$html .= '<li>';
		if ( $i === $last || '' === $crumb['url'] ) {
			$html .= '<span aria-current="page">' . esc_html( $crumb['label'] ) . '</span>';
		} else {
			$html .= '<a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['label'] ) . '</a>';
		}
		$html .= '</li>';
	}
	$html .= '</ol></nav>';
	return $html;
}

/**
 * Chips + breadcrumb no topo de archives de categoria.
 */
function estrato_archive_render_header_block( $query ) {
	if ( ! $query instanceof WP_Query || ! $query->is_main_query() || ! is_category() || is_admin() || is_feed() ) {
		return;
	}
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}

	$color = function_exists( 'estrato_ds_editoria_color' )
		? estrato_ds_editoria_color( $term->slug )
		: '#191919';

	echo '<div class="estrato-archive-header" style="--estrato-cat-color:' . esc_attr( $color ) . '">';
	echo estrato_archive_render_breadcrumb( estrato_archive_breadcrumb_trail( (int) $term->term_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<h1 class="estrato-display estrato-archive-title">' . esc_html( $term->name ) . '</h1>';
	if ( $term->description ) {
		echo '<p class="estrato-archive-desc">' . esc_html( wp_strip_all_tags( $term->description ) ) . '</p>';
	}
	$chips = estrato_archive_subcategory_chips( $term );
	if ( $chips ) {
		echo '<div class="estrato-archive-chips" role="navigation" aria-label="Subcategorias">';
		echo '<a class="estrato-archive-chip estrato-archive-chip--active" href="' . esc_url( get_category_link( $term ) ) . '">Todas</a>';
		foreach ( $chips as $child ) {
			echo '<a class="estrato-archive-chip" href="' . esc_url( get_category_link( $child ) ) . '">' . esc_html( $child->name ) . '</a>';
		}
		echo '</div>';
	}
	echo '</div>';
}
add_action( 'loop_start', 'estrato_archive_render_header_block', 5 );

/**
 * CSS archives.
 */
function estrato_archive_styles() {
	if ( is_admin() || is_feed() || ( ! is_category() && ! is_singular( 'post' ) ) ) {
		return;
	}
	?>
	<style id="estrato-archive-css">
	.estrato-breadcrumb{font-size:13px;color:var(--estrato-muted);margin:0 0 1rem}
	.estrato-breadcrumb ol{list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:.35rem}
	.estrato-breadcrumb li+li::before{content:"›";margin-right:.35rem;color:var(--estrato-line)}
	.estrato-breadcrumb a{color:inherit;text-decoration:none}
	.estrato-breadcrumb a:hover{text-decoration:underline}
	.estrato-archive-header{max-width:1200px;margin:0 auto 1.5rem;padding:0 1rem;border-top:4px solid var(--estrato-cat-color)}
	.estrato-archive-title{margin:.5rem 0}
	.estrato-archive-desc{color:var(--estrato-muted);max-width:72ch;line-height:1.5}
	.estrato-archive-chips{display:flex;flex-wrap:wrap;gap:.5rem;margin:1rem 0}
	.estrato-archive-chip{display:inline-block;padding:.35rem .85rem;border:1px solid var(--estrato-line);border-radius:999px;font-size:13px;text-decoration:none;color:inherit}
	.estrato-archive-chip--active,.estrato-archive-chip:hover{background:var(--estrato-cat-color);color:#fff;border-color:var(--estrato-cat-color)}
	</style>
	<?php
}
add_action( 'wp_head', 'estrato_archive_styles', 28 );
