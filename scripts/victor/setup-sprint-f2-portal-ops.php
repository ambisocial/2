<?php
/**
 * Sprint F2 — ops contínua + relatório GSC por portal (S21).
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-f2-portal-ops.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: ( function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '' );

if ( function_exists( 'estrato_ops_schedule_events' ) ) {
	estrato_ops_schedule_events();
}

if ( function_exists( 'estrato_aeo_schedule_cron' ) ) {
	estrato_aeo_schedule_cron();
}

$weekly = dirname( __FILE__ ) . '/report-gsc-weekly.php';
if ( is_readable( $weekly ) ) {
	require $weekly;
}

$monthly = dirname( __FILE__ ) . '/ops-monthly-maintenance.php';
if ( is_readable( $monthly ) ) {
	require $monthly;
}

$w_age = function_exists( 'estrato_regression_ops_weekly_report_age_days' )
	? estrato_regression_ops_weekly_report_age_days()
	: -1;
$m_age = function_exists( 'estrato_regression_ops_monthly_age_days' )
	? estrato_regression_ops_monthly_age_days()
	: -1;
$crons = function_exists( 'estrato_regression_ops_crons_scheduled' )
	&& estrato_regression_ops_crons_scheduled();

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'        => $portal,
			'crons'         => $crons,
			'weekly_age_d'  => $w_age,
			'monthly_age_d' => $m_age,
		),
		JSON_UNESCAPED_UNICODE
	)
);
