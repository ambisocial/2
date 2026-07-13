<?php
/**
 * Sprint A5 — Operação contínua por portal (satélites).
 *
 * - Agenda crons semanal/mensal
 * - Relatório semanal inicial
 * - Manutenção mensal (curadoria, enrich, thumbs)
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-a5-portal-ops.php
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
		'estrato.cc'           => 'estrato-finance',
		'mente.estrato.cc'     => 'estrato-mind',
		'lifestyle.estrato.cc' => 'estrato-lifestyle',
		'science.estrato.cc'   => 'estrato-science',
		'sustain.estrato.cc'   => 'estrato-sustain',
		'culture.estrato.cc'   => 'estrato-culture',
	);
	$portal = $by_domain[ $host ] ?? 'estrato-finance';
}

if ( function_exists( 'estrato_ops_schedule_events' ) ) {
	estrato_ops_schedule_events();
}

$weekly = dirname( __FILE__ ) . '/report-gsc-weekly.php';
if ( is_readable( $weekly ) ) {
	require $weekly;
}

$monthly = dirname( __FILE__ ) . '/ops-monthly-maintenance.php';
if ( is_readable( $monthly ) ) {
	require $monthly;
}

$m_age = function_exists( 'estrato_regression_ops_monthly_age_days' )
	? estrato_regression_ops_monthly_age_days()
	: -1;
$w_age = function_exists( 'estrato_regression_ops_weekly_report_age_days' )
	? estrato_regression_ops_weekly_report_age_days()
	: -1;
$crons = function_exists( 'estrato_regression_ops_crons_scheduled' )
	&& estrato_regression_ops_crons_scheduled();

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'       => $portal,
			'crons'        => $crons,
			'weekly_age_d' => $w_age,
			'monthly_age_d'=> $m_age,
		),
		JSON_UNESCAPED_UNICODE
	)
);
