<?php
/**
 * Repara OG default órfão (wpseo_social + estrato_og_default_attachment_id)
 * e aplica thumbnail editorial em posts publicados sem imagem destacada.
 *
 * Uso: wp eval-file scripts/victor/repair-og-default-and-thumbs.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$report = array(
	'portal'           => getenv( 'ESTRATO_PORTAL' ) ?: home_url( '/' ),
	'og_attachment_id' => 0,
	'og_source'        => '',
	'thumbs_set'       => 0,
	'thumbs_fail'      => 0,
	'no_thumb_left'    => 0,
);

/**
 * @param int $id Attachment ID.
 * @return bool
 */
$attachment_ok = static function ( $id ) {
	$id = (int) $id;
	if ( $id <= 0 ) {
		return false;
	}
	$post = get_post( $id );
	return $post && 'attachment' === $post->post_type && 0 === strpos( (string) $post->post_mime_type, 'image/' );
};

$portal = preg_replace( '/[^a-z0-9\-]/', '', strtolower( (string) ( getenv( 'ESTRATO_PORTAL' ) ?: '' ) ) );
$slug   = $portal ? str_replace( 'estrato-', '', $portal ) : 'finance';
if ( 'finance' === $slug || '' === $slug ) {
	$file_hint = 'estrato-finance-og-1200x630';
} else {
	$file_hint = 'estrato-' . $slug . '-og-1200x630';
}

$og_id = (int) get_option( 'estrato_og_default_attachment_id', 0 );
if ( ! $attachment_ok( $og_id ) ) {
	$og_id = 0;
}

if ( ! $og_id ) {
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	foreach ( $found as $att ) {
		$url = (string) wp_get_attachment_url( $att->ID );
		if ( false !== strpos( $url, 'og-1200x630' ) && $attachment_ok( (int) $att->ID ) ) {
			$og_id                 = (int) $att->ID;
			$report['og_source'] = 'existing_attachment';
			break;
		}
	}
}

if ( ! $og_id ) {
	$uploads = wp_upload_dir();
	$basedir = trailingslashit( $uploads['basedir'] ) . '2026/07/';
	$candidates = glob( $basedir . $file_hint . '*.png' );
	if ( ! $candidates ) {
		$candidates = glob( $basedir . '*og-1200x630*.png' );
	}
	$file = '';
	if ( $candidates ) {
		foreach ( $candidates as $path ) {
			if ( ! preg_match( '/-\d+x\d+\.png$/i', $path ) ) {
				$file = $path;
				break;
			}
		}
		if ( ! $file ) {
			$file = $candidates[0];
		}
	}
	if ( $file && is_readable( $file ) ) {
		$tmp = wp_tempnam( basename( $file ) );
		if ( $tmp && copy( $file, $tmp ) ) {
			$file_array = array(
				'name'     => sanitize_file_name( basename( $file ) ),
				'tmp_name' => $tmp,
			);
			$imported = media_handle_sideload( $file_array, 0, 'Estrato OG 1200x630' );
			if ( ! is_wp_error( $imported ) && $attachment_ok( (int) $imported ) ) {
				$og_id                 = (int) $imported;
				$report['og_source'] = 'sideload_disk';
			}
		}
	}
}

if ( ! $og_id ) {
	$logo = (int) get_theme_mod( 'custom_logo' );
	if ( $attachment_ok( $logo ) ) {
		$og_id                 = $logo;
		$report['og_source'] = 'custom_logo';
	}
}

if ( $og_id ) {
	update_option( 'estrato_og_default_attachment_id', $og_id, false );
	$social = get_option( 'wpseo_social', array() );
	if ( ! is_array( $social ) ) {
		$social = array();
	}
	$url = (string) wp_get_attachment_url( $og_id );
	$social['og_default_image_id'] = (string) $og_id;
	if ( $url ) {
		$social['og_default_image']        = $url;
		$social['og_frontpage_image']      = $url;
		$social['og_frontpage_image_id']   = (string) $og_id;
	}
	update_option( 'wpseo_social', $social, false );
	$report['og_attachment_id'] = $og_id;
	if ( ! $report['og_source'] ) {
		$report['og_source'] = 'kept_valid';
	}
}

global $wpdb;
$ids = $wpdb->get_col(
	"
	SELECT p.ID
	FROM {$wpdb->posts} p
	LEFT JOIN {$wpdb->postmeta} m
	  ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id'
	WHERE p.post_type = 'post'
	  AND p.post_status = 'publish'
	  AND (m.meta_id IS NULL OR m.meta_value = '' OR m.meta_value = '0')
	"
);

foreach ( (array) $ids as $post_id ) {
	$post_id = (int) $post_id;
	clean_post_cache( $post_id );
	if ( has_post_thumbnail( $post_id ) ) {
		continue;
	}
	$ok = false;
	if ( function_exists( 'estrato_bridge_set_editorial_thumbnail' ) ) {
		$ok = estrato_bridge_set_editorial_thumbnail( $post_id );
	}
	if ( ! $ok && $og_id ) {
		$ok = (bool) set_post_thumbnail( $post_id, $og_id );
	}
	if ( $ok && has_post_thumbnail( $post_id ) ) {
		++$report['thumbs_set'];
	} else {
		++$report['thumbs_fail'];
	}
}

$left = $wpdb->get_var(
	"
	SELECT COUNT(*)
	FROM {$wpdb->posts} p
	LEFT JOIN {$wpdb->postmeta} m
	  ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id'
	WHERE p.post_type = 'post'
	  AND p.post_status = 'publish'
	  AND (m.meta_id IS NULL OR m.meta_value = '' OR m.meta_value = '0')
	"
);
$report['no_thumb_left'] = (int) $left;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::success( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
} else {
	echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
