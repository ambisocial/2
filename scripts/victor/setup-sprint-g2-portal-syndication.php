<?php
/**
 * Sprint G2 — syndication outbound por portal (S24).
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-g2-portal-syndication.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: ( function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '' );
$script = function_exists( 'estrato_syndicate_resolve_script_path' )
	? estrato_syndicate_resolve_script_path()
	: '';

if ( ! $script || ! is_readable( $script ) ) {
	WP_CLI::error( 'syndicate-outbound.py ausente' );
}

$log_dir = '/var/log/estrato';
if ( ! is_dir( $log_dir ) ) {
	wp_mkdir_p( $log_dir );
}
$log_file = $log_dir . '/syndicate-' . sanitize_key( $portal ) . '.log';
if ( ! is_file( $log_file ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch
	touch( $log_file );
	@chmod( $log_file, 0664 );
}

update_option( 'estrato_syndication_enabled', true, false );

$domain = home_url();
$wp_path = ABSPATH;
$recent  = 'estrato-finance' === $portal ? 5 : 3;

$cmd = sprintf(
	'python3 %s --recent %d --wp-path %s --domain %s >> %s 2>&1',
	escapeshellarg( $script ),
	$recent,
	escapeshellarg( untrailingslashit( $wp_path ) ),
	escapeshellarg( $domain ),
	escapeshellarg( $log_file )
);

// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
exec( $cmd, $output, $code );

$lines = 0;
if ( is_readable( $log_file ) ) {
	$lines = count( file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) );
}

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'    => $portal,
			'backfill'  => $recent,
			'exit_code' => (int) $code,
			'log_lines' => $lines,
			'log_file'  => $log_file,
		),
		JSON_UNESCAPED_UNICODE
	)
);
