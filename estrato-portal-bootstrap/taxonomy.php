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

/**
 * Editorias do preset ativo (option, não portal_get_config).
 *
 * @return array<int, string>
 */
function estrato_regression_portal_editoria_slugs() {
	$preset = get_option( 'estrato_rss_preset', '' );
	$preset = is_string( $preset ) ? sanitize_key( $preset ) : '';
	$map    = array(
		'brasil-financeiro' => array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' ),
		'brasil-mind'       => array( 'aprendizado-cognicao', 'filosofia-autoconhecimento', 'financas-comportamentais' ),
		'brasil-lifestyle'  => array( 'sabores-paixao', 'movimento-ar-livre', 'hobbies-colecao' ),
		'brasil-science'    => array( 'neuro-biologia', 'bio-fabricacao', 'ia-seguranca' ),
		'brasil-agro'       => array( 'producao-safras', 'mercado-agro', 'agroecologia' ),
		'brasil-esg'        => array( 'clima-ambiente', 'transicao-energia', 'impacto-negocios' ),
		'brasil-viagem'     => array( 'destinos', 'rotas-dicas', 'nomadismo' ),
		'brasil-culture'    => array( 'jogos-imaginacao', 'narrativas-som', 'celebridades' ),
		'brasil-politica'   => array( 'poder', 'brasil', 'eleicoes' ),
		'brasil-esporte'    => array( 'futebol', 'olimpiadas', 'mais-esportes' ),
		'brasil-saude'      => array( 'medicina', 'prevencao', 'bem-estar' ),
		'brasil-educacao'   => array( 'educacao-base', 'carreira', 'empreendedorismo' ),
		'brasil-tech'       => array( 'tecnologia', 'inovacao', 'gadgets' ),
		'brasil-carros'     => array( 'automoveis', 'eletricos', 'mobilidade' ),
		'brasil-sustain'    => array( 'producao-safras', 'mercado-agro', 'agroecologia' ),
	);
	if ( isset( $map[ $preset ] ) ) {
		return $map[ $preset ];
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => 0,
			'hide_empty' => false,
			'meta_query' => array(
				array(
					'key'     => '_estrato_layer',
					'value'   => 'editoria',
					'compare' => '=',
				),
			),
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}
	return wp_list_pluck( $terms, 'slug' );
}

/**
 * @return int Seções PressGrid amarradas às editorias do portal ativo.
 */
function estrato_regression_pressgrid_portal_sections() {
	$sections = get_option( 'pressgrid_layout_sections', array() );
	if ( ! is_array( $sections ) ) {
		return 0;
	}
	$editoria_ids = array();
	foreach ( estrato_regression_portal_editoria_slugs() as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$editoria_ids[] = (int) $term->term_id;
		}
	}
	if ( empty( $editoria_ids ) ) {
		return 0;
	}
	$matched = 0;
	foreach ( $sections as $section ) {
		if ( empty( $section['enabled'] ) || empty( $section['category'] ) ) {
			continue;
		}
		if ( in_array( (int) $section['category'], $editoria_ids, true ) ) {
			++$matched;
		}
	}
	return $matched;
}
