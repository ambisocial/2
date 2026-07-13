<?php
/**
 * Operação contínua — crons semanal/mensal (Sprint 10).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_OPS_WEEKLY_HOOK', 'estrato_ops_weekly_report_event' );
define( 'ESTRATO_OPS_MONTHLY_HOOK', 'estrato_ops_monthly_maintenance_event' );

add_filter(
	'cron_schedules',
	function ( $schedules ) {
		if ( ! isset( $schedules['estrato_weekly'] ) ) {
			$schedules['estrato_weekly'] = array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display'  => 'Estrato semanal',
			);
		}
		if ( ! isset( $schedules['estrato_monthly'] ) ) {
			$schedules['estrato_monthly'] = array(
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => 'Estrato mensal',
			);
		}
		return $schedules;
	}
);

/**
 * Agenda eventos de operação.
 */
function estrato_ops_schedule_events() {
	if ( ! wp_next_scheduled( ESTRATO_OPS_WEEKLY_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'estrato_weekly', ESTRATO_OPS_WEEKLY_HOOK );
	}
	if ( ! wp_next_scheduled( ESTRATO_OPS_MONTHLY_HOOK ) ) {
		wp_schedule_event( time() + 2 * HOUR_IN_SECONDS, 'estrato_monthly', ESTRATO_OPS_MONTHLY_HOOK );
	}
}
add_action( 'init', 'estrato_ops_schedule_events', 30 );

/**
 * Relatório semanal.
 */
function estrato_ops_run_weekly_report() {
	$file = dirname( __DIR__ ) . '/scripts/victor/report-gsc-weekly.php';
	$repo = '/var/www/estrato/repo/scripts/victor/report-gsc-weekly.php';
	$path = is_readable( $repo ) ? $repo : $file;
	if ( is_readable( $path ) ) {
		require $path;
	}
}
add_action( ESTRATO_OPS_WEEKLY_HOOK, 'estrato_ops_run_weekly_report' );

/**
 * Manutenção mensal.
 */
function estrato_ops_run_monthly_maintenance() {
	$file = dirname( __DIR__ ) . '/scripts/victor/ops-monthly-maintenance.php';
	$repo = '/var/www/estrato/repo/scripts/victor/ops-monthly-maintenance.php';
	$path = is_readable( $repo ) ? $repo : $file;
	if ( is_readable( $path ) ) {
		require $path;
	}
}
add_action( ESTRATO_OPS_MONTHLY_HOOK, 'estrato_ops_run_monthly_maintenance' );

/**
 * @return int Dias desde último relatório semanal (-1 se nunca).
 */
function estrato_regression_ops_weekly_report_age_days() {
	$last = get_option( 'estrato_ops_last_weekly_report', '' );
	if ( ! $last ) {
		return -1;
	}
	$ts = strtotime( $last );
	if ( ! $ts ) {
		return -1;
	}
	return (int) floor( ( time() - $ts ) / DAY_IN_SECONDS );
}

/**
 * @return int Dias desde última manutenção mensal (-1 se nunca).
 */
function estrato_regression_ops_monthly_age_days() {
	$last = get_option( 'estrato_ops_last_monthly_maintenance', '' );
	if ( ! $last ) {
		return -1;
	}
	$ts = strtotime( $last );
	if ( ! $ts ) {
		return -1;
	}
	return (int) floor( ( time() - $ts ) / DAY_IN_SECONDS );
}

/**
 * @return bool
 */
function estrato_regression_ops_crons_scheduled() {
	return (bool) wp_next_scheduled( ESTRATO_OPS_WEEKLY_HOOK )
		&& (bool) wp_next_scheduled( ESTRATO_OPS_MONTHLY_HOOK );
}
