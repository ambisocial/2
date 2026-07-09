<?php
/**
 * Plugin Name: Estrato Publisher Bridge
 * Description: Recebe artigos do pipeline Victor (scout/curator/writer/publisher) via REST API.
 * Version: 1.0.0
 * Author: Cursor Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_BRIDGE_VERSION', '1.1.0' );
define( 'ESTRATO_BRIDGE_SECRET_OPTION', 'estrato_bridge_secret' );

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
		update_post_meta( $post_id, '_estrato_source_url', esc_url_raw( $params['source_url'] ) );
	}
	if ( ! empty( $params['source_name'] ) ) {
		update_post_meta( $post_id, '_estrato_source_name', sanitize_text_field( $params['source_name'] ) );
	}

	$image_url = '';
	if ( ! empty( $params['image_url'] ) ) {
		$image_url = esc_url_raw( $params['image_url'] );
	}
	if ( $image_url ) {
		estrato_bridge_set_featured_image_from_url( $post_id, $image_url );
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
 * @return int|false Attachment ID ou false.
 */
function estrato_bridge_set_featured_image_from_url( $post_id, $image_url ) {
	$post_id   = (int) $post_id;
	$image_url = esc_url_raw( $image_url );

	if ( ! $post_id || ! $image_url ) {
		return false;
	}

	if ( has_post_thumbnail( $post_id ) ) {
		return (int) get_post_thumbnail_id( $post_id );
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $image_url, 45 );
	if ( is_wp_error( $tmp ) ) {
		return false;
	}

	$path = wp_parse_url( $image_url, PHP_URL_PATH );
	$name = $path ? basename( $path ) : 'estrato-featured.jpg';
	if ( ! preg_match( '/\.(jpe?g|png|gif|webp)$/i', $name ) ) {
		$name = 'estrato-featured.jpg';
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

	set_post_thumbnail( $post_id, $attachment_id );
	return (int) $attachment_id;
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
 * Preenche imagens destacadas em posts sem thumbnail.
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

	$fallbacks = array(
		'mercados'           => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800&q=80&fit=crop',
		'negocios'           => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&q=80&fit=crop',
		'economia'           => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&q=80&fit=crop',
		'financas-pessoais'  => 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800&q=80&fit=crop',
		'default'            => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&q=80&fit=crop',
	);

	foreach ( $posts as $post ) {
		$stats['processed']++;
		$image_url = estrato_bridge_extract_image_from_html( $post->post_content );

		if ( ! $image_url ) {
			$cats      = wp_get_post_categories( $post->ID, array( 'fields' => 'slugs' ) );
			$cat_slug  = ! empty( $cats[0] ) ? $cats[0] : 'default';
			$image_url = isset( $fallbacks[ $cat_slug ] ) ? $fallbacks[ $cat_slug ] : $fallbacks['default'];
		}

		$result = estrato_bridge_set_featured_image_from_url( $post->ID, $image_url );
		if ( $result ) {
			$stats['set']++;
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
