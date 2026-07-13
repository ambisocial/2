<?php
/**
 * Sprint A4 — PressGrid por editoria + matriz RSS (satélites).
 *
 * - Sync taxonomia com preset explícito (evita portal_get_config → finance)
 * - Reconfigura PressGrid layout por editorias do portal
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-a4-portal-pressgrid.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( '' === $portal ) {
	$host      = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$by_domain = array(
		'mente.estrato.cc'     => 'estrato-mind',
		'lifestyle.estrato.cc' => 'estrato-lifestyle',
		'science.estrato.cc'   => 'estrato-science',
		'sustain.estrato.cc'   => 'estrato-sustain',
		'culture.estrato.cc'   => 'estrato-culture',
	);
	$portal = $by_domain[ $host ] ?? '';
}

$presets = array(
	'estrato-mind'      => 'brasil-mind',
	'estrato-lifestyle' => 'brasil-lifestyle',
	'estrato-science'   => 'brasil-science',
	'estrato-sustain'   => 'brasil-sustain',
	'estrato-culture'   => 'brasil-culture',
);

$editoria_map = array(
	'brasil-mind'      => array( 'aprendizado-cognicao', 'filosofia-autoconhecimento', 'financas-comportamentais' ),
	'brasil-lifestyle' => array( 'sabores-paixao', 'movimento-ar-livre', 'hobbies-colecao' ),
	'brasil-science'   => array( 'neuro-biologia', 'bio-fabricacao', 'ia-seguranca' ),
	'brasil-sustain'   => array( 'agro-sustentavel', 'economia-alternativa', 'vida-nomade' ),
	'brasil-culture'   => array( 'jogos-imaginacao', 'narrativas-som' ),
);

$preset = $presets[ $portal ] ?? '';
if ( '' === $preset ) {
	WP_CLI::error( "Portal satélite inválido: {$portal}" );
}

$sync = array( 'skipped' => true );
if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$sync = estrato_rss_sync_portal_taxonomy( $preset );
	WP_CLI::log( 'Taxonomia sync: ' . wp_json_encode( $sync ) );
}

$editorias = $editoria_map[ $preset ] ?? array();
$term_ids  = array();
foreach ( $editorias as $slug ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( $term && ! is_wp_error( $term ) ) {
		$term_ids[ $slug ] = (int) $term->term_id;
	}
}

if ( empty( $term_ids ) ) {
	WP_CLI::error( 'Nenhuma editoria encontrada após sync' );
}

$layouts  = array( 'hero-grid', 'grid-4', 'grid-3', 'grid-2' );
$sections = array();
$first    = true;

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
	$layout     = $layouts[ ( $i % 3 ) + 1 ] ?? 'grid-3';
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

$matrix_n = count( get_option( 'estrato_rss_import_matrix', array() ) );
$pg_n     = function_exists( 'estrato_regression_pressgrid_portal_sections' )
	? estrato_regression_pressgrid_portal_sections()
	: 0;

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'           => $portal,
			'preset'           => $preset,
			'import_nodes'     => $matrix_n,
			'pressgrid_secs'   => count( $sections ),
			'pressgrid_editor' => $pg_n,
			'editorias'        => count( $term_ids ),
		),
		JSON_UNESCAPED_UNICODE
	)
);
