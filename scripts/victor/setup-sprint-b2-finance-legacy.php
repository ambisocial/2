<?php
/**
 * Sprint B2 — Merge categorias legado (estrato.cc).
 *
 * Uso: wp eval-file setup-sprint-b2-finance-legacy.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$merge_file = dirname( __FILE__ ) . '/merge-legacy-categories.php';
if ( ! is_readable( $merge_file ) ) {
	WP_CLI::error( 'merge-legacy-categories.php ausente' );
}

require $merge_file;

$legacy = function_exists( 'estrato_regression_legacy_category_posts' )
	? estrato_regression_legacy_category_posts()
	: -1;

WP_CLI::success( wp_json_encode( array( 'legacy_posts' => $legacy ), JSON_UNESCAPED_UNICODE ) );
