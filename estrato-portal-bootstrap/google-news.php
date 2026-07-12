<?php
/**
 * Google News / Publisher Center readiness (S23).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Posts publicados nas últimas 48h (news-sitemap).
 *
 * @return int
 */
function estrato_google_news_recent_posts_count() {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'after' => '48 hours ago',
				),
			),
		)
	);
	if ( ! is_array( $ids ) ) {
		return 0;
	}
	$count = 0;
	foreach ( $ids as $post_id ) {
		if ( '1' === (string) get_post_meta( (int) $post_id, '_yoast_wpseo_meta-robots-noindex', true ) ) {
			continue;
		}
		++$count;
	}
	return $count;
}

/**
 * @return int
 */
function estrato_regression_google_news_recent_posts() {
	return estrato_google_news_recent_posts_count();
}

/**
 * Metadados de publicação para Publisher Center.
 *
 * @return array<string, string>
 */
function estrato_google_news_publication_meta() {
	$portal = function_exists( 'estrato_nav_current_portal_id' )
		? estrato_nav_current_portal_id()
		: 'estrato-finance';

	return array(
		'portal'       => $portal,
		'name'         => get_bloginfo( 'name' ),
		'language'     => substr( get_bloginfo( 'language' ), 0, 2 ) ?: 'pt',
		'url'          => home_url( '/' ),
		'news_sitemap' => home_url( '/news-sitemap.xml' ),
		'robots'       => home_url( '/robots.txt' ),
		'updated'      => gmdate( 'c' ),
	);
}

/**
 * Persiste checklist de readiness por portal.
 *
 * @return array<string, mixed>
 */
function estrato_google_news_save_readiness() {
	$recent = estrato_google_news_recent_posts_count();
	$meta   = estrato_google_news_publication_meta();

	$payload = array(
		'portal'        => $meta['portal'],
		'publication'   => $meta,
		'recent_48h'    => $recent,
		'news_sitemap'  => $meta['news_sitemap'],
		'ready'         => $recent >= 1,
		'checklist'     => array(
			'news_sitemap_200'     => true,
			'robots_declares_news' => true,
			'publication_name'     => (string) $meta['name'],
			'language_pt'          => 'pt' === $meta['language'],
			'recent_posts_48h'     => $recent >= 1,
			'publisher_center'     => 'manual — https://publishercenter.google.com/',
		),
		'note'          => 'Google News é algorítmico; Publisher Center requer aprovação manual.',
	);

	update_option( 'estrato_google_news_readiness', $payload, false );
	return $payload;
}
