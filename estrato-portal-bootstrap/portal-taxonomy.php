<?php
/**
 * Branding por editoria/coluna — cores e identidade ao entrar na seção (schema v2).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return int
 */
function estrato_portal_get_archive_term_id() {
	if ( is_category() ) {
		$term = get_queried_object();
		return ( $term && isset( $term->term_id ) ) ? (int) $term->term_id : 0;
	}
	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( ! $cats ) {
			return 0;
		}
		foreach ( $cats as $cat ) {
			$type = get_term_meta( (int) $cat->term_id, '_estrato_term_type', true );
			if ( 'column' === $type ) {
				return (int) $cat->term_id;
			}
		}
		foreach ( $cats as $cat ) {
			$type = get_term_meta( (int) $cat->term_id, '_estrato_term_type', true );
			if ( 'subcategory' === $type ) {
				return (int) $cat->term_id;
			}
		}
		return (int) $cats[0]->term_id;
	}
	return 0;
}

/**
 * Sobe na hierarquia até achar termo com cores (coluna > subcategoria > editoria).
 *
 * @param int $term_id
 * @return array<string, mixed>
 */
function estrato_portal_resolve_branding_for_term( $term_id ) {
	$visited = 0;
	while ( $term_id > 0 && $visited < 6 ) {
		++$visited;
		if ( function_exists( 'estrato_taxonomy_get_term_branding' ) ) {
			$branding = estrato_taxonomy_get_term_branding( $term_id );
		} else {
			$branding = array(
				'primary_color'   => (string) get_term_meta( $term_id, '_estrato_primary_color', true ),
				'accent_color'    => (string) get_term_meta( $term_id, '_estrato_accent_color', true ),
				'secondary_color' => (string) get_term_meta( $term_id, '_estrato_secondary_color', true ),
				'brand_name'      => (string) get_term_meta( $term_id, '_estrato_brand_name', true ),
				'tagline'         => (string) get_term_meta( $term_id, '_estrato_tagline', true ),
				'header_variant'  => (string) get_term_meta( $term_id, '_estrato_header_variant', true ),
				'term_type'       => (string) get_term_meta( $term_id, '_estrato_term_type', true ),
			);
		}
		if ( ! empty( $branding['primary_color'] ) && ! empty( $branding['accent_color'] ) ) {
			$term = get_term( $term_id, 'category' );
			$branding['term_id']   = $term_id;
			$branding['term_slug'] = ( $term && ! is_wp_error( $term ) ) ? $term->slug : '';
			return $branding;
		}
		$term = get_term( $term_id, 'category' );
		if ( ! $term || is_wp_error( $term ) || empty( $term->parent ) ) {
			break;
		}
		$term_id = (int) $term->parent;
	}
	return array();
}

/**
 * @param array<string, string> $classes
 * @return array<string, string>
 */
function estrato_portal_body_class_branding( $classes ) {
	$term_id = estrato_portal_get_archive_term_id();
	if ( ! $term_id ) {
		return $classes;
	}
	$branding = estrato_portal_resolve_branding_for_term( $term_id );
	if ( empty( $branding['term_slug'] ) ) {
		return $classes;
	}
	$type = $branding['term_type'] ?? 'editoria';
	$classes[] = 'estrato-section-active';
	$classes[] = 'estrato-section-' . sanitize_html_class( $type );
	$classes[] = 'estrato-section-' . sanitize_html_class( $branding['term_slug'] );
	if ( ! empty( $branding['header_variant'] ) ) {
		$classes[] = 'estrato-header-' . sanitize_html_class( $branding['header_variant'] );
	}
	return $classes;
}
add_filter( 'body_class', 'estrato_portal_body_class_branding' );

/**
 * CSS variables + header tint (estilo G1 por seção).
 */
function estrato_portal_section_branding_styles() {
	$term_id = estrato_portal_get_archive_term_id();
	if ( ! $term_id ) {
		return;
	}
	$branding = estrato_portal_resolve_branding_for_term( $term_id );
	if ( empty( $branding['primary_color'] ) ) {
		return;
	}
	$primary   = esc_attr( $branding['primary_color'] );
	$accent    = esc_attr( $branding['accent_color'] ?: '#9AFF33' );
	$secondary = esc_attr( $branding['secondary_color'] ?: '#1a1a1a' );
	$brand     = esc_html( $branding['brand_name'] ?? '' );
	$tagline   = esc_html( $branding['tagline'] ?? '' );

	$css = ':root{--estrato-primary:' . $primary . ';--estrato-accent:' . $accent . ';--estrato-secondary:' . $secondary . '}'
		. '.estrato-section-active .estrato-g1-header__principal,.estrato-section-active .estrato-g1-header__editoria'
		. '{background-color:var(--estrato-primary)!important;color:#fff!important}'
		. '.estrato-section-active .estrato-g1-header__logo-img{filter:brightness(0) invert(1)}'
		. '.estrato-section-active .site-header,.estrato-section-active header.site-header,.estrato-section-active #masthead'
		. '{background-color:var(--estrato-primary)!important;border-bottom:3px solid var(--estrato-accent)}'
		. '.estrato-section-active .main-nav a:hover,.estrato-section-active a{color:inherit}'
		. '.estrato-section-active .breaking-news,.estrato-section-active .ticker-label{background:var(--estrato-accent)!important;color:var(--estrato-primary)!important}'
		. '.estrato-section-active .category-title,.estrato-section-active .archive-title{color:var(--estrato-primary)}'
		. '.estrato-section-active .estrato-section-kicker{display:block;font-size:.85rem;letter-spacing:.08em;text-transform:uppercase;color:var(--estrato-accent);margin-bottom:.35rem}';
	if ( function_exists( 'estrato_perf_style_add' ) ) {
		estrato_perf_style_add( 'estrato-section-branding', $css, 'main' );
	} else {
		echo '<style id="estrato-section-branding">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	if ( $brand && ( is_category() || is_singular( 'post' ) ) ) {
		echo '<script>document.addEventListener("DOMContentLoaded",function(){'
			. 'var t=document.querySelector(".archive-title,.page-title,.entry-header h1");'
			. 'if(t&&!t.querySelector(".estrato-section-kicker")){'
			. 'var k=document.createElement("span");k.className="estrato-section-kicker";'
			. 'k.textContent=' . wp_json_encode( $brand . ( $tagline ? ' — ' . $tagline : '' ) ) . ';'
			. 't.insertBefore(k,t.firstChild);}});</script>';
	}
}
add_action( 'wp_head', 'estrato_portal_section_branding_styles', 20 );

/**
 * Faixa de colunas na home.
 *
 * @return string
 */
function estrato_shortcode_home_columns() {
	if ( ! function_exists( 'estrato_taxonomy_get_column_terms' ) ) {
		return '';
	}
	$columns = estrato_taxonomy_get_column_terms();
	$columns = array_filter(
		$columns,
		function ( $col ) {
			return ! empty( $col['branding']['show_in_home_strip'] );
		}
	);
	if ( ! $columns ) {
		return '';
	}
	$html = '<nav class="estrato-columns-strip" aria-label="Colunas Estrato"><ul>';
	foreach ( $columns as $col ) {
		$term = get_term( $col['term_id'], 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$primary = esc_attr( $col['branding']['primary_color'] ?? '#000' );
		$accent  = esc_attr( $col['branding']['accent_color'] ?? '#9AFF33' );
		$html   .= '<li><a href="' . esc_url( get_term_link( $term ) ) . '" style="--col-primary:' . $primary . ';--col-accent:' . $accent . '">'
			. esc_html( $col['name'] ) . '</a></li>';
	}
	$html .= '</ul></nav>';
	return $html;
}
add_shortcode( 'estrato_home_columns', 'estrato_shortcode_home_columns' );

/**
 * CSS da strip de colunas (evita &lt;style&gt; removido por wp_kses_post no PressGrid).
 */
function estrato_enqueue_columns_strip_css() {
	if ( ! is_front_page() ) {
		return;
	}
	wp_register_style( 'estrato-columns-strip', false, array(), '1.0.0' );
	wp_enqueue_style( 'estrato-columns-strip' );
	wp_add_inline_style(
		'estrato-columns-strip',
		'.estrato-columns-strip ul{display:flex;flex-wrap:wrap;gap:.75rem;list-style:none;margin:1rem 0;padding:0}'
		. '.estrato-columns-strip a{display:inline-block;padding:.45rem .9rem;border-radius:4px;font-weight:600;text-decoration:none;background:var(--col-primary);color:var(--col-accent)}'
	);
}
add_action( 'wp_enqueue_scripts', 'estrato_enqueue_columns_strip_css', 20 );

/**
 * @return int
 */
function estrato_regression_column_terms_count() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'meta_query' => array(
				array(
					'key'   => '_estrato_term_type',
					'value' => 'column',
				),
			),
			'fields'     => 'ids',
		)
	);
	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return -1;
	}
	return count( $terms );
}

/**
 * @return int Editorias com meta de branding.
 */
function estrato_regression_branded_editorias_count() {
	$slugs = function_exists( 'estrato_rss_get_finance_menu_order' )
		? estrato_rss_get_finance_menu_order()
		: array();
	$count = 0;
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$color = get_term_meta( (int) $term->term_id, '_estrato_primary_color', true );
		if ( $color ) {
			++$count;
		}
	}
	return $count;
}
