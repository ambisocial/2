<?php
/**
 * Sprint D3 — cross-links contextuais em hubs /tudo-sobre/.
 *
 * Injeta bloco estrato-network-crosslinks nas hubs mapeadas em estrato_nav_crosslink_map().
 *
 * Uso: ESTRATO_PORTAL=estrato-finance wp eval-file setup-sprint-d3-portal-crosslinks.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'estrato_nav_render_crosslinks_block' ) ) {
	WP_CLI::error( 'estrato_nav_render_crosslinks_block ausente (nav-visual.php)' );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: estrato_nav_current_portal_id();
$map    = estrato_nav_crosslink_map();
$hubs   = $map[ $portal ] ?? array();

if ( ! $hubs ) {
	WP_CLI::success(
		wp_json_encode(
			array(
				'portal'  => $portal,
				'updated' => 0,
				'note'    => 'sem hubs mapeados',
			),
			JSON_UNESCAPED_UNICODE
		)
	);
	exit( 0 );
}

$updated = 0;
$missing = 0;

foreach ( array_keys( $hubs ) as $hub_slug ) {
	$page = get_page_by_path( 'tudo-sobre/' . $hub_slug, OBJECT, 'page' );
	if ( ! $page ) {
		++$missing;
		WP_CLI::warning( "Hub ausente: tudo-sobre/{$hub_slug}" );
		continue;
	}

	$block = estrato_nav_render_crosslinks_block( $hub_slug );
	if ( '' === $block ) {
		continue;
	}

	if ( false !== strpos( $page->post_content, 'estrato-network-crosslinks' ) ) {
		WP_CLI::log( "Hub {$hub_slug} já tem cross-links" );
		continue;
	}

	$new_content = $page->post_content . "\n<!-- wp:html -->" . $block . '<!-- /wp:html -->';
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => $new_content,
		)
	);
	++$updated;
	WP_CLI::log( "Cross-links em tudo-sobre/{$hub_slug} → #{$page->ID}" );
}

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'  => $portal,
			'hubs'    => count( $hubs ),
			'updated' => $updated,
			'missing' => $missing,
		),
		JSON_UNESCAPED_UNICODE
	)
);
