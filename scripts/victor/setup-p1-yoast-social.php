<?php
/**
 * P1 — OG default 1200×630 + Yoast social sameAs.
 *
 * wp eval-file setup-p1-yoast-social.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$logo_path = WP_CONTENT_DIR . '/uploads/estrato-og-default.jpg';
if ( ! is_readable( $logo_path ) ) {
	$logo_path = dirname( __DIR__, 2 ) . '/estrato-portal-bootstrap/assets/estrato-logo.png';
}

$attach_id = (int) get_option( 'estrato_og_default_attachment_id', 0 );
if ( ! $attach_id || ! get_post( $attach_id ) ) {
	if ( is_readable( $logo_path ) ) {
		$tmp = wp_tempnam( 'estrato-og' );
		if ( $tmp && copy( $logo_path, $tmp ) ) {
			$file_array = array(
				'name'     => 'estrato-og-default.jpg',
				'tmp_name' => $tmp,
			);
			$attach_id  = media_handle_sideload( $file_array, 0, 'Estrato OG default' );
			if ( is_wp_error( $attach_id ) ) {
				$attach_id = 0;
			} else {
				update_option( 'estrato_og_default_attachment_id', (int) $attach_id, false );
			}
		}
	}
}

$social = get_option( 'wpseo_social', array() );
if ( ! is_array( $social ) ) {
	$social = array();
}
$social['og_default_image_id'] = $attach_id ? (string) $attach_id : ( $social['og_default_image_id'] ?? '' );
$social['linkedin_url']        = $social['linkedin_url'] ?? 'https://www.linkedin.com/company/estrato-media/';
$social['twitter_site']        = $social['twitter_site'] ?? '@estrato_cc';
$social['instagram_url']       = $social['instagram_url'] ?? 'https://www.instagram.com/estrato.cc/';
update_option( 'wpseo_social', $social, false );

WP_CLI::success(
	wp_json_encode(
		array(
			'og_attachment' => $attach_id,
			'social'        => array(
				'linkedin'  => $social['linkedin_url'],
				'twitter'   => $social['twitter_site'],
				'instagram' => $social['instagram_url'],
			),
		),
		JSON_UNESCAPED_UNICODE
	)
);
