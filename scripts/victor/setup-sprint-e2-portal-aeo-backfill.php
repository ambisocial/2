<?php
/**
 * Sprint E2 — backfill AEO em 100% dos posts publicados.
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-e2-portal-aeo-backfill.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'estrato_content_enrich_post' ) ) {
	WP_CLI::error( 'estrato_content_enrich_post ausente' );
}

if ( ! defined( 'ESTRATO_ENRICHING' ) ) {
	define( 'ESTRATO_ENRICHING', true );
}

$updated = 0;
$checked = 0;
$skipped = 0;

foreach (
	get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	) as $post_id
) {
	++$checked;
	$content = get_post_field( 'post_content', $post_id );
	if ( false !== strpos( $content, ESTRATO_AEO_MARKER ) ) {
		++$skipped;
		continue;
	}

	$result = estrato_content_enrich_post( (int) $post_id );
	if ( ! empty( $result['updated'] ) ) {
		++$updated;
	}
}

$without = function_exists( 'estrato_regression_posts_without_aeo' )
	? estrato_regression_posts_without_aeo()
	: -1;

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'  => getenv( 'ESTRATO_PORTAL' ) ?: ( function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '' ),
			'checked' => $checked,
			'updated' => $updated,
			'skipped' => $skipped,
			'without' => $without,
		),
		JSON_UNESCAPED_UNICODE
	)
);
