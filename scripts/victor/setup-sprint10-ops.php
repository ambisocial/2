<?php
/**
 * Sprint 10 — Instala crons de operação e gera primeiro relatório.
 *
 * Uso: wp eval-file setup-sprint10-ops.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( function_exists( 'estrato_ops_schedule_events' ) ) {
	estrato_ops_schedule_events();
	WP_CLI::log( 'Crons semanal/mensal agendados' );
}

$weekly = dirname( __FILE__ ) . '/report-gsc-weekly.php';
if ( is_readable( $weekly ) ) {
	require $weekly;
	WP_CLI::log( 'Relatório semanal inicial gerado' );
}

$weekly_next = wp_next_scheduled( ESTRATO_OPS_WEEKLY_HOOK );
$monthly_next = wp_next_scheduled( ESTRATO_OPS_MONTHLY_HOOK );
WP_CLI::log( 'Próximo weekly: ' . ( $weekly_next ? gmdate( 'c', $weekly_next ) : 'n/a' ) );
WP_CLI::log( 'Próximo monthly: ' . ( $monthly_next ? gmdate( 'c', $monthly_next ) : 'n/a' ) );

WP_CLI::success( 'Sprint 10 operação contínua configurada.' );
