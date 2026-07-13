<?php
/**
 * SEO de categorias — canonical e Open Graph por editoria (Sprint 1 / B1–B2).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return WP_Term|null
 */
function estrato_seo_current_category_term() {
	if ( ! is_category() ) {
		return null;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || 'category' !== $term->taxonomy ) {
		return null;
	}
	return $term;
}

/**
 * @param WP_Term $term
 * @return string
 */
function estrato_seo_category_canonical_url( $term ) {
	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		return home_url( '/' );
	}
	return user_trailingslashit( $link );
}

/**
 * @param WP_Term $term
 * @return string
 */
function estrato_seo_category_og_title( $term ) {
	$brand = get_term_meta( $term->term_id, '_estrato_brand_name', true );
	$name  = $brand ? (string) $brand : $term->name;
	$site  = get_bloginfo( 'name' );
	return sprintf( '%s — %s', $name, $site );
}

/**
 * @param WP_Term $term
 * @return string
 */
function estrato_seo_category_og_description( $term ) {
	$desc = term_description( $term->term_id, 'category' );
	$desc = wp_strip_all_tags( (string) $desc );
	if ( strlen( $desc ) >= 40 ) {
		return wp_trim_words( $desc, 35, '…' );
	}
	$brand = get_term_meta( $term->term_id, '_estrato_brand_name', true );
	$label = $brand ? (string) $brand : $term->name;
	return sprintf(
		'Últimas notícias e análises de %s no %s. Cobertura editorial com contexto para leitores no Brasil.',
		$label,
		get_bloginfo( 'name' )
	);
}

/**
 * @param string $canonical
 * @return string
 */
function estrato_seo_filter_category_canonical( $canonical ) {
	$term = estrato_seo_current_category_term();
	if ( ! $term ) {
		return $canonical;
	}
	return estrato_seo_category_canonical_url( $term );
}
add_filter( 'wpseo_canonical', 'estrato_seo_filter_category_canonical', 20 );
add_filter( 'rank_math/frontend/canonical', 'estrato_seo_filter_category_canonical', 20 );

/**
 * @param string $title
 * @return string
 */
function estrato_seo_filter_category_og_title( $title ) {
	$term = estrato_seo_current_category_term();
	if ( ! $term ) {
		return $title;
	}
	return estrato_seo_category_og_title( $term );
}
add_filter( 'wpseo_opengraph_title', 'estrato_seo_filter_category_og_title', 20 );

/**
 * @param string $desc
 * @return string
 */
function estrato_seo_filter_category_og_desc( $desc ) {
	$term = estrato_seo_current_category_term();
	if ( ! $term ) {
		return $desc;
	}
	return estrato_seo_category_og_description( $term );
}
add_filter( 'wpseo_opengraph_desc', 'estrato_seo_filter_category_og_desc', 20 );

/**
 * Fallback quando Yoast não está ativo.
 */
function estrato_seo_category_head_fallback() {
	if ( ! estrato_seo_current_category_term() || defined( 'WPSEO_VERSION' ) ) {
		return;
	}
	$term = estrato_seo_current_category_term();
	if ( ! $term ) {
		return;
	}
	printf(
		'<link rel="canonical" href="%s" />' . "\n",
		esc_url( estrato_seo_category_canonical_url( $term ) )
	);
	printf(
		'<meta property="og:title" content="%s" />' . "\n",
		esc_attr( estrato_seo_category_og_title( $term ) )
	);
	printf(
		'<meta property="og:description" content="%s" />' . "\n",
		esc_attr( estrato_seo_category_og_description( $term ) )
	);
	printf(
		'<meta property="og:url" content="%s" />' . "\n",
		esc_url( estrato_seo_category_canonical_url( $term ) )
	);
}
add_action( 'wp_head', 'estrato_seo_category_head_fallback', 3 );
