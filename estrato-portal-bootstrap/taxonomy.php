<?php
/**
 * Helpers de regressão para taxonomia financeira (Sprint 8).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string, int>
 */
function estrato_regression_finance_category_counts() {
	$slugs = function_exists( 'estrato_rss_get_finance_menu_order' )
		? estrato_rss_get_finance_menu_order()
		: array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );
	$out   = array();
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		$out[ $slug ] = ( $term && ! is_wp_error( $term ) ) ? (int) $term->count : -1;
	}
	return $out;
}

/**
 * @return int Posts publicados ainda em categorias legado.
 */
function estrato_regression_legacy_category_posts() {
	$legacy = array( 'politica', 'tecnologia', 'brasil' );
	$total  = 0;
	foreach ( $legacy as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$total += count(
			get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'category'       => (int) $term->term_id,
					'fields'         => 'ids',
				)
			)
		);
	}
	return $total;
}

/**
 * @return int Seções PressGrid com category > 0 para editorias financeiras.
 */
function estrato_regression_pressgrid_finance_sections() {
	$sections = get_option( 'pressgrid_layout_sections', array() );
	if ( ! is_array( $sections ) ) {
		return 0;
	}
	$finance_ids = array();
	$slugs       = function_exists( 'estrato_rss_get_finance_menu_order' )
		? estrato_rss_get_finance_menu_order()
		: array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term ) {
			$finance_ids[] = (int) $term->term_id;
		}
	}
	$matched = 0;
	foreach ( $sections as $section ) {
		if ( empty( $section['enabled'] ) || empty( $section['category'] ) ) {
			continue;
		}
		if ( in_array( (int) $section['category'], $finance_ids, true ) ) {
			++$matched;
		}
	}
	return $matched;
}
