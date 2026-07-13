<?php
/**
 * Sprint 8 genérico — layout PressGrid por portal (qualquer preset com taxonomia v2).
 *
 * Uso:
 *   ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint8-portal-layout.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: 'estrato-finance';
$portal = preg_replace( '/[^a-z0-9\-]/', '', strtolower( $portal ) );

$preset_map = array(
	'estrato-finance'   => 'brasil-financeiro',
	'estrato-mind'      => 'brasil-mind',
	'estrato-lifestyle' => 'brasil-lifestyle',
	'estrato-science'   => 'brasil-science',
	'estrato-sustain'   => 'brasil-sustain',
	'estrato-culture'   => 'brasil-culture',
);
$sync_preset = $preset_map[ $portal ] ?? get_option( 'estrato_rss_preset', 'brasil-financeiro' );

if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$sync = estrato_rss_sync_portal_taxonomy( $sync_preset );
	WP_CLI::log( 'Taxonomia sincronizada: ' . wp_json_encode( $sync ) );
}

/**
 * Carrega editorias do preset ativo.
 *
 * @return array<int, string>
 */
function estrato_s8_portal_editorias() {
	$preset = get_option( 'estrato_rss_preset', '' );
	$map    = array(
		'brasil-financeiro' => array( 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' ),
		'brasil-mind'       => array( 'aprendizado-cognicao', 'filosofia-autoconhecimento', 'financas-comportamentais' ),
		'brasil-lifestyle'  => array( 'sabores-paixao', 'movimento-ar-livre', 'hobbies-colecao' ),
		'brasil-science'    => array( 'neuro-biologia', 'bio-fabricacao', 'ia-seguranca' ),
		'brasil-sustain'    => array( 'agro-sustentavel', 'economia-alternativa', 'vida-nomade' ),
		'brasil-culture'    => array( 'jogos-imaginacao', 'narrativas-som' ),
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
 * @param array<int, string> $slugs
 * @return array<string, int>
 */
function estrato_s8_term_ids( $slugs ) {
	$ids = array();
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$ids[ $slug ] = (int) $term->term_id;
		}
	}
	return $ids;
}

$editorias = estrato_s8_portal_editorias();
$term_ids  = estrato_s8_term_ids( $editorias );

if ( empty( $term_ids ) ) {
	WP_CLI::warning( 'Nenhuma editoria encontrada — layout não configurado.' );
	return;
}

$layouts   = array( 'hero-grid', 'grid-4', 'grid-3', 'grid-2' );
$sections  = array();
$first     = true;

foreach ( $editorias as $i => $slug ) {
	if ( ! isset( $term_ids[ $slug ] ) ) {
		continue;
	}
	if ( $first ) {
		$sections[] = array(
			'id'          => 'hero',
			'label'       => ucfirst( str_replace( '-', ' ', $slug ) ),
			'enabled'     => true,
			'layout'      => 'hero-grid',
			'category'    => $term_ids[ $slug ],
			'post_count'  => 3,
			'custom_html' => '',
		);
		$first = false;
		continue;
	}
	$layout = $layouts[ ( $i % 3 ) + 1 ] ?? 'grid-3';
	$sections[] = array(
		'id'          => "editoria-{$slug}",
		'label'       => ucfirst( str_replace( '-', ' ', $slug ) ),
		'enabled'     => true,
		'layout'      => $layout,
		'category'    => $term_ids[ $slug ],
		'post_count'  => 4,
		'custom_html' => '',
	);
}

$sections[] = array(
	'id'          => 'latest',
	'label'       => 'Últimas',
	'enabled'     => true,
	'layout'      => 'grid-3',
	'category'    => 0,
	'post_count'  => 6,
	'custom_html' => '',
);

update_option( 'pressgrid_layout_sections', $sections, false );
WP_CLI::success( count( $sections ) . ' seções PressGrid configuradas para ' . $portal );

// Colunas: atribuir menu secondary se existir.
$col_menu = wp_get_nav_menu_object( 'Estrato Colunas' );
if ( $col_menu ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['secondary'] ) ) {
		$locations['secondary'] = (int) $col_menu->term_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		WP_CLI::log( 'Menu Estrato Colunas → secondary' );
	}
}
