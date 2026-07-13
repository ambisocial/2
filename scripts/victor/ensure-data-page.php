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
	WP_CLI::success( 'skip: not finance portal' );
	return;
}

$page = get_page_by_path( 'data' );
if ( $page && 'publish' === $page->post_status ) {
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
	WP_CLI::error( $id->get_error_message() );
}

WP_CLI::success( 'data page created id=' . $id );
