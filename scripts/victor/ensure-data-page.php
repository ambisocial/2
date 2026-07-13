<?php
/**
 * Garante página institucional /data/ no portal finance (footer Grupo Estrato).
 *
 * wp eval-file scripts/victor/ensure-data-page.php --path=/var/www/estrato.cc
 *
 * @package EstratoVictorScripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: '';
if ( 'estrato-finance' !== $portal ) {
	if ( defined( 'ESTRATO_OPS_EMBED' ) ) {
		$GLOBALS['estrato_ops_data_page'] = 'skip:not_finance';
		return;
	}
	WP_CLI::success( 'skip: not finance portal' );
	return;
}

$page = get_page_by_path( 'data' );
if ( $page && 'publish' === $page->post_status ) {
	if ( defined( 'ESTRATO_OPS_EMBED' ) ) {
		$GLOBALS['estrato_ops_data_page'] = 'exists:' . $page->ID;
		return;
	}
	WP_CLI::success( 'data page exists id=' . $page->ID );
	return;
}

$id = wp_insert_post(
	array(
		'post_title'   => 'Data Estrato',
		'post_name'    => 'data',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '<p>Data Estrato reúne dados, séries e inteligência editorial para a rede Estrato. Em breve, painéis e APIs abertas para jornalismo de dados.</p>',
	)
);

if ( is_wp_error( $id ) ) {
	if ( defined( 'ESTRATO_OPS_EMBED' ) ) {
		$GLOBALS['estrato_ops_data_page'] = 'error:' . $id->get_error_message();
		return;
	}
	WP_CLI::error( $id->get_error_message() );
}

if ( defined( 'ESTRATO_OPS_EMBED' ) ) {
	$GLOBALS['estrato_ops_data_page'] = 'created:' . $id;
	return;
}

WP_CLI::success( 'data page created id=' . $id );
