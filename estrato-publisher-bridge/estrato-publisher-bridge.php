<?php
/**
 * Plugin Name: Estrato Publisher Bridge
 * Description: Recebe artigos do pipeline Victor (scout/curator/writer/publisher) via REST API.
 * Version: 1.2.0
 * Author: Cursor Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_BRIDGE_VERSION', '1.2.0' );
define( 'ESTRATO_BRIDGE_SECRET_OPTION', 'estrato_bridge_secret' );
define( 'ESTRATO_BRIDGE_BACKFILL_HOOK', 'estrato_thumbnail_backfill_event' );
define( 'ESTRATO_BRIDGE_ORIGINAL_META', '_estrato_original_image_url' );

register_activation_hook( __FILE__, 'estrato_bridge_activate' );
register_deactivation_hook( __FILE__, 'estrato_bridge_deactivate' );
add_action( ESTRATO_BRIDGE_BACKFILL_HOOK, 'estrato_bridge_run_backfill_cron' );

function estrato_bridge_activate() {
	if ( ! wp_next_scheduled( ESTRATO_BRIDGE_BACKFILL_HOOK ) ) {
		wp_schedule_event( time() + 300, 'hourly', ESTRATO_BRIDGE_BACKFILL_HOOK );
	}
}

function estrato_bridge_deactivate() {
	$timestamp = wp_next_scheduled( ESTRATO_BRIDGE_BACKFILL_HOOK );
	while ( $timestamp ) {
		wp_unschedule_event( $timestamp, ESTRATO_BRIDGE_BACKFILL_HOOK );
		$timestamp = wp_next_scheduled( ESTRATO_BRIDGE_BACKFILL_HOOK );
	}
}

/**
 * Cron: garante thumbnails em posts importados sem imagem destacada.
 */
function estrato_bridge_run_backfill_cron() {
	$stats = estrato_bridge_backfill_featured_images( 20 );
	$stats = array_merge( $stats, estrato_bridge_refresh_stock_thumbnails( 15 ) );
	set_transient( 'estrato_bridge_last_backfill', $stats, HOUR_IN_SECONDS );
}

add_action( 'rest_api_init', 'estrato_bridge_register_routes' );

/**
 * @return string
 */
function estrato_bridge_get_secret() {
	$secret = get_option( ESTRATO_BRIDGE_SECRET_OPTION, '' );
	if ( ! $secret ) {
		$secret = wp_generate_password( 48, false, false );
		update_option( ESTRATO_BRIDGE_SECRET_OPTION, $secret, false );
	}
	return $secret;
}

function estrato_bridge_register_routes() {
	register_rest_route(
		'estrato/v1',
		'/publish',
		array(
			'methods'             => 'POST',
			'callback'            => 'estrato_bridge_publish_post',
			'permission_callback' => 'estrato_bridge_permission',
		)
	);

	register_rest_route(
		'estrato/v1',
		'/health',
		array(
			'methods'             => 'GET',
			'callback'            => function () {
				return array(
					'ok'      => true,
					'version' => ESTRATO_BRIDGE_VERSION,
					'site'    => home_url(),
				);
			},
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * @param WP_REST_Request $request
 */
function estrato_bridge_permission( $request ) {
	$secret = estrato_bridge_get_secret();
	$header = $request->get_header( 'x-estrato-secret' );
	if ( $header && hash_equals( $secret, $header ) ) {
		return true;
	}
	return current_user_can( 'edit_posts' );
}

/**
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function estrato_bridge_publish_post( $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		return new WP_Error( 'invalid_json', 'JSON inválido', array( 'status' => 400 ) );
	}

	$title   = isset( $params['title'] ) ? wp_strip_all_tags( $params['title'] ) : '';
	$content = isset( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';
	$guid    = isset( $params['external_id'] ) ? sanitize_text_field( $params['external_id'] ) : '';

	if ( '' === $title || '' === $content ) {
		return new WP_Error( 'missing_fields', 'title e content são obrigatórios', array( 'status' => 400 ) );
	}

	if ( ! $guid && ! empty( $params['source_url'] ) ) {
		$guid = esc_url_raw( $params['source_url'] );
	}

	$existing_id = 0;
	if ( $guid ) {
		$found = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_estrato_pipeline_id',
				'meta_value'     => $guid,
			)
		);
		if ( ! empty( $found[0] ) ) {
			$existing_id = (int) $found[0];
		}
	}

	$category_ids = array();
	if ( ! empty( $params['category'] ) ) {
		$slug = sanitize_title( $params['category'] );
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term ) {
			$category_ids[] = (int) $term->term_id;
		}
	}

	$post_data = array(
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => ! empty( $params['status'] ) ? sanitize_key( $params['status'] ) : 'publish',
		'post_author'  => 1,
	);

	if ( ! empty( $params['excerpt'] ) ) {
		$post_data['post_excerpt'] = wp_strip_all_tags( $params['excerpt'] );
	}

	if ( $existing_id ) {
		$post_data['ID'] = $existing_id;
		$post_id       = wp_update_post( $post_data, true );
		$action        = 'updated';
	} else {
		if ( $category_ids ) {
			$post_data['post_category'] = $category_ids;
		}
		$post_id = wp_insert_post( $post_data, true );
		$action  = 'created';
	}

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	if ( $guid ) {
		update_post_meta( $post_id, '_estrato_pipeline_id', $guid );
	}
	if ( ! empty( $params['source_url'] ) ) {
		update_post_meta( $post_id, '_estrato_source_url', estrato_bridge_clean_source_url( $params['source_url'] ) );
	}
	if ( ! empty( $params['source_name'] ) ) {
		update_post_meta( $post_id, '_estrato_source_name', sanitize_text_field( $params['source_name'] ) );
	}

	$image_url = estrato_bridge_resolve_original_image_url(
		$post_id,
		$content,
		! empty( $params['source_url'] ) ? $params['source_url'] : '',
		! empty( $params['image_url'] ) ? $params['image_url'] : ''
	);
	if ( $image_url ) {
		$thumb_id    = get_post_thumbnail_id( $post_id );
		$force_image = ! $thumb_id || estrato_bridge_attachment_is_stock( $thumb_id );
		estrato_bridge_set_featured_image_from_url( $post_id, $image_url, $force_image );
		update_post_meta( $post_id, ESTRATO_BRIDGE_ORIGINAL_META, esc_url_raw( $image_url ) );
	}

	return new WP_REST_Response(
		array(
			'ok'      => true,
			'action'  => $action,
			'post_id' => $post_id,
			'url'     => get_permalink( $post_id ),
		),
		200
	);
}

/**
 * Define imagem destacada a partir de URL externa (sideload).
 *
 * @param int    $post_id
 * @param string $image_url
 * @param bool   $force_replace Substitui thumbnail existente (ex.: trocar stock por original).
 * @return int|false Attachment ID ou false.
 */
function estrato_bridge_set_featured_image_from_url( $post_id, $image_url, $force_replace = false ) {
	$post_id   = (int) $post_id;
	$image_url = estrato_bridge_normalize_image_url( esc_url_raw( $image_url ) );

	if ( ! $post_id || ! $image_url || estrato_bridge_is_stock_image_url( $image_url ) ) {
		return false;
	}

	if ( has_post_thumbnail( $post_id ) && ! $force_replace ) {
		return (int) get_post_thumbnail_id( $post_id );
	}

	if ( $force_replace && has_post_thumbnail( $post_id ) ) {
		$old_id = (int) get_post_thumbnail_id( $post_id );
		if ( $old_id && estrato_bridge_attachment_is_stock( $old_id ) ) {
			wp_delete_attachment( $old_id, true );
		}
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $image_url, 60 );
	if ( is_wp_error( $tmp ) ) {
		return false;
	}

	$path = wp_parse_url( $image_url, PHP_URL_PATH );
	$name = $path ? basename( $path ) : 'estrato-original.jpg';
	if ( ! preg_match( '/\.(jpe?g|png|gif|webp)$/i', $name ) ) {
		$name = 'estrato-original.jpg';
	}

	$file_array = array(
		'name'     => sanitize_file_name( $name ),
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file_array, $post_id );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		return false;
	}

	update_post_meta( $attachment_id, ESTRATO_BRIDGE_ORIGINAL_META, esc_url_raw( $image_url ) );
	set_post_thumbnail( $post_id, $attachment_id );
	update_post_meta( $post_id, ESTRATO_BRIDGE_ORIGINAL_META, esc_url_raw( $image_url ) );
	return (int) $attachment_id;
}

/**
 * @param string $url
 */
function estrato_bridge_is_stock_image_url( $url ) {
	if ( ! $url ) {
		return true;
	}
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$host = strtolower( (string) $host );
	$stock_hosts = array(
		'images.unsplash.com',
		'plus.unsplash.com',
		'images.pexels.com',
		'image.pollinations.ai',
		'pollinations.ai',
		'secure.gravatar.com',
		'www.gravatar.com',
	);
	foreach ( $stock_hosts as $stock ) {
		if ( $host === $stock || str_ends_with( $host, '.' . $stock ) ) {
			return true;
		}
	}
	return (bool) preg_match( '/placeholder|dummy|default-image|no-image/i', $url );
}

/**
 * @param int $attachment_id
 */
function estrato_bridge_attachment_is_stock( $attachment_id ) {
	$orig = get_post_meta( $attachment_id, ESTRATO_BRIDGE_ORIGINAL_META, true );
	if ( $orig && ! estrato_bridge_is_stock_image_url( $orig ) ) {
		return false;
	}
	$url = wp_get_attachment_url( $attachment_id );
	if ( $url && estrato_bridge_is_stock_image_url( $url ) ) {
		return true;
	}
	$file = get_attached_file( $attachment_id );
	return $file && (bool) preg_match( '/estrato-featured/i', basename( $file ) );
}

/**
 * Tenta obter URL em resolução maior (CDNs de notícias).
 *
 * @param string $url
 */
function estrato_bridge_normalize_image_url( $url ) {
	if ( ! $url ) {
		return '';
	}
	// URL direta s3 Globo embutida em CDN.
	if ( preg_match( '#(https?://i\.s3\.glbimg\.com/v1/[^\s"\']+\.(?:jpe?g|png|webp))#i', $url, $m ) ) {
		return esc_url_raw( $m[1] );
	}
	// CDN Globo com resize — manter URL completa (já inclui 1200x0 etc).
	if ( preg_match( '#^https?://s\d+-[^/]+\.glbimg\.com/.+#i', $url ) ) {
		return esc_url_raw( $url );
	}
	$url = preg_replace( '/([?&])(w|h|width|height|resize|fit|crop)=[^&]+/i', '', $url );
	$url = rtrim( $url, '?&' );
	return esc_url_raw( $url );
}

/**
 * Extrai melhor imagem do HTML (maior área, ignora ícones/logos).
 *
 * @param string $html
 */
function estrato_bridge_extract_best_image_from_html( $html ) {
	if ( ! $html ) {
		return '';
	}
	$candidates = array();
	if ( preg_match_all( '/<img[^>]+>/i', $html, $tags ) ) {
		foreach ( $tags[0] as $tag ) {
			$src = '';
			if ( preg_match( '/\ssrc=["\']([^"\']+)["\']/i', $tag, $m ) ) {
				$src = $m[1];
			}
			$w = 0;
			$h = 0;
			if ( preg_match( '/\swidth=["\']?(\d+)/i', $tag, $m ) ) {
				$w = (int) $m[1];
			}
			if ( preg_match( '/\sheight=["\']?(\d+)/i', $tag, $m ) ) {
				$h = (int) $m[1];
			}
			if ( preg_match( '/\ssrcset=["\']([^"\']+)["\']/i', $tag, $m ) ) {
				$parts = preg_split( '/\s*,\s*/', $m[1] );
				$last  = trim( end( $parts ) );
				if ( preg_match( '/^(https?:\/\/\S+)/i', $last, $u ) ) {
					$src = $u[1];
				}
			}
			if ( ! $src || estrato_bridge_is_stock_image_url( $src ) ) {
				continue;
			}
			if ( preg_match( '/\b(icon|logo|avatar|sprite|emoji|1x1)\b/i', $tag ) ) {
				continue;
			}
			$score = max( $w * $h, 40000 );
			if ( preg_match( '/\.(jpe?g|webp)(\?|$)/i', $src ) ) {
				$score += 50000;
			}
			$candidates[ $src ] = $score;
		}
	}
	if ( empty( $candidates ) ) {
		return estrato_bridge_extract_image_from_html( $html );
	}
	arsort( $candidates );
	return estrato_bridge_normalize_image_url( (string) array_key_first( $candidates ) );
}

/**
 * @param string $page_url
 */
function estrato_bridge_fetch_og_image( $page_url ) {
	$page_url = esc_url_raw( $page_url );
	if ( ! $page_url ) {
		return '';
	}
	$response = wp_remote_get(
		$page_url,
		array(
			'timeout'    => 20,
			'user-agent' => 'Mozilla/5.0 (compatible; EstratoBot/1.2; +https://estrato.cc)',
			'headers'    => array( 'Accept' => 'text/html' ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return '';
	}
	$html = wp_remote_retrieve_body( $response );
	if ( ! $html ) {
		return '';
	}
	$patterns = array(
		'/property=["\']og:image(?::secure_url)?["\'][^>]+content=["\']([^"\']+)["\']/i',
		'/content=["\']([^"\']+)["\'][^>]+property=["\']og:image(?::secure_url)?["\']/i',
		'/name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i',
		'/content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image["\']/i',
	);
	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $html, $m ) ) {
			$url = estrato_bridge_normalize_image_url( $m[1] );
			if ( $url && ! estrato_bridge_is_stock_image_url( $url ) ) {
				return $url;
			}
		}
	}
	return '';
}

/**
 * @param string $url
 */
function estrato_bridge_clean_source_url( $url ) {
	$url = esc_url_raw( trim( (string) $url ) );
	if ( ! $url ) {
		return '';
	}
	if ( preg_match( '/\*+(https?:\/\/.+)$/i', $url, $m ) ) {
		$url = $m[1];
	}
	if ( preg_match( '/[?&]url=(https?[^&]+)/i', $url, $m ) ) {
		$url = urldecode( $m[1] );
	}
	return esc_url_raw( $url );
}

/**
 * @param WP_Post $post
 */
function estrato_bridge_get_source_url_from_post( $post ) {
	$source = get_post_meta( $post->ID, '_estrato_source_url', true );
	$source = estrato_bridge_clean_source_url( $source );
	if ( $source && ! preg_match( '/\/feed\/?$|rss\.xml|\.rss$/i', $source ) ) {
		return $source;
	}
	$link = get_post_meta( $post->ID, '_estrato_pipeline_id', true );
	$link = estrato_bridge_clean_source_url( $link );
	if ( $link && filter_var( $link, FILTER_VALIDATE_URL ) && ! preg_match( '/\/feed\/?$|rss/i', $link ) ) {
		return $link;
	}
	if ( preg_match( '/href=["\']([^"\']+)["\'][^>]*>[^<]*Fonte:/i', $post->post_content, $m ) ) {
		return estrato_bridge_clean_source_url( $m[1] );
	}
	if ( preg_match( '/Fonte:\s*<a[^>]+href=["\']([^"\']+)["\']/i', $post->post_content, $m ) ) {
		return estrato_bridge_clean_source_url( $m[1] );
	}
	return '';
}

/**
 * Resolve imagem original: explícita (não stock) → HTML → og:image da fonte.
 *
 * @param int    $post_id
 * @param string $content
 * @param string $source_url
 * @param string $explicit_url
 */
function estrato_bridge_resolve_original_image_url( $post_id, $content = '', $source_url = '', $explicit_url = '' ) {
	$stored = get_post_meta( $post_id, ESTRATO_BRIDGE_ORIGINAL_META, true );
	if ( $stored && ! estrato_bridge_is_stock_image_url( $stored ) ) {
		return estrato_bridge_normalize_image_url( $stored );
	}

	if ( $explicit_url && ! estrato_bridge_is_stock_image_url( $explicit_url ) ) {
		return estrato_bridge_normalize_image_url( $explicit_url );
	}

	$source_clean = estrato_bridge_clean_source_url( $source_url );
	if ( $source_clean && ! preg_match( '/\/feed\/?$|rss/i', $source_clean ) ) {
		$og = estrato_bridge_fetch_og_image( $source_clean );
		if ( $og ) {
			return $og;
		}
	}

	$from_html = estrato_bridge_extract_best_image_from_html( $content );
	if ( $from_html ) {
		return $from_html;
	}

	return '';
}

/**
 * Extrai primeira URL de imagem de HTML.
 *
 * @param string $html
 * @return string
 */
function estrato_bridge_extract_image_from_html( $html ) {
	if ( ! $html ) {
		return '';
	}
	if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches ) ) {
		return esc_url_raw( $matches[1] );
	}
	return '';
}

/**
 * Preenche imagens destacadas em posts sem thumbnail (somente originais).
 *
 * @param int $limit Máximo de posts por execução.
 * @return array{processed:int, set:int, failed:int}
 */
function estrato_bridge_backfill_featured_images( $limit = 50 ) {
	$limit = max( 1, min( 200, (int) $limit ) );
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	$stats = array(
		'processed' => 0,
		'set'       => 0,
		'failed'    => 0,
	);

	foreach ( $posts as $post ) {
		$stats['processed']++;
		$source  = estrato_bridge_get_source_url_from_post( $post );
		$stored  = get_post_meta( $post->ID, ESTRATO_BRIDGE_ORIGINAL_META, true );
		$image_url = estrato_bridge_resolve_original_image_url(
			$post->ID,
			$post->post_content,
			$source,
			$stored
		);

		if ( ! $image_url ) {
			$stats['failed']++;
			continue;
		}

		$result = estrato_bridge_set_featured_image_from_url( $post->ID, $image_url, true );
		if ( $result ) {
			$stats['set']++;
		} else {
			$stats['failed']++;
		}
	}

	return $stats;
}

/**
 * Substitui thumbnails genéricos (Unsplash/stock) por imagem original da matéria.
 *
 * @param int $limit
 * @return array{processed:int, refreshed:int, failed:int}
 */
function estrato_bridge_refresh_stock_thumbnails( $limit = 30 ) {
	$limit = max( 1, min( 100, (int) $limit ) );
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit * 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$stats = array(
		'processed' => 0,
		'refreshed' => 0,
		'failed'    => 0,
	);

	foreach ( $posts as $post ) {
		if ( $stats['processed'] >= $limit ) {
			break;
		}
		$thumb_id = get_post_thumbnail_id( $post->ID );
		if ( ! $thumb_id || ! estrato_bridge_attachment_is_stock( $thumb_id ) ) {
			continue;
		}
		$stats['processed']++;
		$source    = estrato_bridge_get_source_url_from_post( $post );
		$image_url = estrato_bridge_resolve_original_image_url(
			$post->ID,
			$post->post_content,
			$source,
			''
		);
		if ( ! $image_url ) {
			$stats['failed']++;
			continue;
		}
		$result = estrato_bridge_set_featured_image_from_url( $post->ID, $image_url, true );
		if ( $result ) {
			$stats['refreshed']++;
		} else {
			$stats['failed']++;
		}
	}

	return $stats;
}

add_action( 'admin_menu', function () {
	add_options_page(
		'Estrato Bridge',
		'Estrato Bridge',
		'manage_options',
		'estrato-bridge',
		function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$secret = estrato_bridge_get_secret();
			echo '<div class="wrap"><h1>Estrato Publisher Bridge</h1>';
			echo '<p>Endpoint: <code>' . esc_html( rest_url( 'estrato/v1/publish' ) ) . '</code></p>';
			echo '<p>Header: <code>X-Estrato-Secret: ' . esc_html( $secret ) . '</code></p>';
			echo '<p>Health: <code>' . esc_html( rest_url( 'estrato/v1/health' ) ) . '</code></p>';
			echo '</div>';
		}
	);
} );
