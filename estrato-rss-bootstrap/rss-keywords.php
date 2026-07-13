<?php
/**
 * Filtro de palavras-chave no import RSS (schema v2).
 *
 * @package EstratoRssBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string               $title
 * @param string               $body
 * @param array<string, mixed> $keywords
 * @return bool
 */
function estrato_rss_item_matches_keywords( $title, $body, $keywords ) {
	if ( empty( $keywords ) || ! is_array( $keywords ) ) {
		return true;
	}
	$include = $keywords['include'] ?? array();
	$exclude = $keywords['exclude'] ?? array();
	if ( empty( $include ) && empty( $exclude ) ) {
		return true;
	}

	$match_in = $keywords['match_in'] ?? array( 'title', 'excerpt', 'content' );
	if ( ! is_array( $match_in ) ) {
		$match_in = array( 'title', 'excerpt', 'content' );
	}

	$haystack = '';
	if ( in_array( 'title', $match_in, true ) ) {
		$haystack .= ' ' . $title;
	}
	if ( in_array( 'excerpt', $match_in, true ) || in_array( 'content', $match_in, true ) ) {
		$haystack .= ' ' . wp_strip_all_tags( $body );
	}
	$haystack = function_exists( 'mb_strtolower' ) ? mb_strtolower( $haystack ) : strtolower( $haystack );

	foreach ( $exclude as $word ) {
		$word = trim( (string) $word );
		if ( '' === $word ) {
			continue;
		}
		$needle = function_exists( 'mb_strtolower' ) ? mb_strtolower( $word ) : strtolower( $word );
		if ( false !== strpos( $haystack, $needle ) ) {
			return false;
		}
	}

	if ( empty( $include ) ) {
		return true;
	}

	foreach ( $include as $word ) {
		$word = trim( (string) $word );
		if ( '' === $word ) {
			continue;
		}
		$needle = function_exists( 'mb_strtolower' ) ? mb_strtolower( $word ) : strtolower( $word );
		if ( false !== strpos( $haystack, $needle ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Tenta classificar post em subcategoria pelo título/conteúdo.
 *
 * @param int                  $parent_term_id
 * @param string               $title
 * @param string               $body
 * @param array<string, mixed> $taxonomy
 * @param string               $parent_slug
 * @return int 0 se nenhuma subcategoria casar.
 */
function estrato_rss_match_subcategory_term( $parent_term_id, $title, $body, $taxonomy, $parent_slug ) {
	$cats = $taxonomy['categories'][ $parent_slug ]['subcategories'] ?? array();
	if ( empty( $cats ) || ! is_array( $cats ) ) {
		return 0;
	}
	foreach ( $cats as $sub_slug => $sub ) {
		if ( ! is_array( $sub ) ) {
			continue;
		}
		$keywords = function_exists( 'estrato_taxonomy_resolve_keywords' )
			? estrato_taxonomy_resolve_keywords( $taxonomy, $sub )
			: ( $sub['keywords'] ?? array() );
		if ( ! estrato_rss_item_matches_keywords( $title, $body, $keywords ) ) {
			continue;
		}
		$include = $keywords['include'] ?? array();
		if ( empty( $include ) ) {
			continue;
		}
		$full_slug = sanitize_title( $parent_slug . '-' . $sub_slug );
		$term      = get_term_by( 'slug', $full_slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}
	}
	return 0;
}

/**
 * Import RSS usando matriz v2 (editorias, subcategorias, colunas + keywords).
 *
 * @param bool                   $first_run
 * @param array<int, array>      $matrix
 * @param array<string, mixed>   $taxonomy
 * @return array{imported:int, skipped:int, skipped_no_image:int, errors:int, deleted:int}
 */
function estrato_rss_run_import_matrix( $first_run, $matrix, $taxonomy ) {
	if ( ! function_exists( 'fetch_feed' ) ) {
		require_once ABSPATH . WPINC . '/feed.php';
	}

	$settings = estrato_rss_get_settings();
	$per_feed = $first_run ? (int) $settings['items_first_run'] : (int) $settings['items_per_feed'];
	$per_feed = max( 1, min( 20, $per_feed ) );
	$max_run  = max( 1, min( 300, (int) $settings['max_per_run'] ) );
	$imported = estrato_rss_get_imported_guids();
	$stats    = array(
		'imported'         => 0,
		'skipped'          => 0,
		'skipped_no_image' => 0,
		'errors'           => 0,
		'deleted'          => 0,
	);

	estrato_rss_create_categories();

	foreach ( $matrix as $node ) {
		if ( $stats['imported'] >= $max_run || empty( $node['feeds'] ) || ! is_array( $node['feeds'] ) ) {
			continue;
		}
		$term_id = function_exists( 'estrato_rss_resolve_matrix_term_id' )
			? estrato_rss_resolve_matrix_term_id( $node )
			: 0;
		if ( ! $term_id ) {
			continue;
		}
		$node_type   = (string) ( $node['type'] ?? '' );
		$parent_slug = '';
		if ( ESTRATO_TERM_TYPE_EDITORIA === $node_type ) {
			$parent_slug = sanitize_key( (string) ( $node['slug'] ?? '' ) );
		}

		foreach ( $node['feeds'] as $feed_info ) {
			if ( $stats['imported'] >= $max_run || empty( $feed_info['url'] ) ) {
				break;
			}
			$feed = fetch_feed( $feed_info['url'] );
			if ( is_wp_error( $feed ) ) {
				$stats['errors']++;
				continue;
			}
			$keywords = $feed_info['keywords'] ?? ( $node['keywords'] ?? array() );
			$items    = $feed->get_items( 0, $per_feed );
			foreach ( $items as $item ) {
				if ( $stats['imported'] >= $max_run ) {
					break;
				}
				$guid = $item->get_id() ? $item->get_id() : $item->get_permalink();
				if ( ! $guid || isset( $imported[ $guid ] ) ) {
					$stats['skipped']++;
					continue;
				}
				$title = wp_strip_all_tags( $item->get_title() );
				if ( '' === $title ) {
					$stats['skipped']++;
					continue;
				}
				if ( function_exists( 'estrato_pipeline_gate_title_language' ) ) {
					$gate = estrato_pipeline_gate_title_language( $title );
					if ( ! $gate['ok'] ) {
						$stats['skipped']++;
						estrato_rss_mark_guid_imported( $guid );
						continue;
					}
					$title = $gate['title'];
				}
				$content = $item->get_content();
				$excerpt = $item->get_description();
				$body    = $content ? $content : $excerpt;
				$body    = wp_kses_post( $body );
				if ( function_exists( 'estrato_pipeline_sanitize_content_html' ) ) {
					$body = estrato_pipeline_sanitize_content_html( $body );
				}
				if ( ! estrato_rss_item_matches_keywords( $title, $body, $keywords ) ) {
					$stats['skipped']++;
					continue;
				}

				$cat_ids = array( $term_id );
				$sub_id  = 0;
				if ( ESTRATO_TERM_TYPE_EDITORIA === $node_type && $parent_slug ) {
					$sub_id = estrato_rss_match_subcategory_term( $term_id, $title, $body, $taxonomy, $parent_slug );
					if ( $sub_id > 0 && ! in_array( $sub_id, $cat_ids, true ) ) {
						$cat_ids[] = $sub_id;
					}
				}

				$author_id = 1;
				if ( $sub_id > 0 && function_exists( 'estrato_eeat_resolve_author_id_for_terms' ) ) {
					$sub_term = get_term( $sub_id, 'category' );
					if ( $sub_term && ! is_wp_error( $sub_term ) ) {
						$author_id = estrato_eeat_resolve_author_id_for_terms( $sub_term->slug, '' );
					}
				} elseif ( function_exists( 'estrato_eeat_resolve_author_id' ) && $parent_slug ) {
					$author_id = estrato_eeat_resolve_author_id( $parent_slug );
				} elseif ( ESTRATO_TERM_TYPE_SUBCATEGORY === $node_type && function_exists( 'estrato_eeat_resolve_author_id_for_terms' ) ) {
					$node_slug = sanitize_key( (string) ( $node['slug'] ?? '' ) );
					$author_id = estrato_eeat_resolve_author_id_for_terms( $node_slug, '' );
				}

				$link = esc_url( $item->get_permalink() );
				if ( function_exists( 'estrato_bridge_clean_source_url' ) ) {
					$link = estrato_bridge_clean_source_url( $link );
				}
				$source = esc_html( $feed_info['title'] ?? '' );
				$footer = sprintf(
					'<p><em>Fonte: <a href="%1$s" target="_blank" rel="nofollow noopener">%2$s</a></em></p>',
					$link,
					$source
				);

				$image_url = estrato_rss_resolve_item_image_url( $item, $body, $link );
				if ( ! $image_url ) {
					$stats['skipped']++;
					$stats['skipped_no_image']++;
					estrato_rss_mark_guid_imported( $guid );
					continue;
				}

				$post_id = wp_insert_post(
					array(
						'post_title'    => $title,
						'post_content'  => $body . $footer,
						'post_status'   => 'publish',
						'post_author'   => $author_id,
						'post_category' => $cat_ids,
						'post_date'     => $item->get_date( 'Y-m-d H:i:s' ) ? $item->get_date( 'Y-m-d H:i:s' ) : current_time( 'mysql' ),
					),
					true
				);
				if ( is_wp_error( $post_id ) ) {
					$stats['errors']++;
					continue;
				}
				update_post_meta( $post_id, '_estrato_rss_guid', $guid );
				update_post_meta( $post_id, '_estrato_rss_source', $feed_info['title'] ?? '' );
				update_post_meta( $post_id, '_estrato_rss_source_url', $feed_info['url'] );
				update_post_meta( $post_id, '_estrato_source_url', $link );
				$attached = false;
				if ( function_exists( 'estrato_bridge_set_featured_image_from_url' ) ) {
					$attached = (bool) estrato_bridge_set_featured_image_from_url( $post_id, $image_url, true );
				}
				if ( ! $attached ) {
					wp_delete_post( $post_id, true );
					$stats['skipped']++;
					$stats['skipped_no_image']++;
					estrato_rss_mark_guid_imported( $guid );
					continue;
				}
				update_post_meta( $post_id, '_estrato_original_image_url', esc_url_raw( $image_url ) );
				if ( function_exists( 'estrato_pipeline_apply_category' ) ) {
					estrato_pipeline_apply_category( $post_id, $title, $body );
				}
				if ( function_exists( 'estrato_content_on_publish' ) ) {
					estrato_content_on_publish( $post_id );
				}
				$post_after = get_post( $post_id );
				if ( $post_after && 'publish' !== $post_after->post_status ) {
					$stats['skipped']++;
					--$stats['imported'];
					estrato_rss_mark_guid_imported( $guid );
					continue;
				}
				estrato_rss_mark_guid_imported( $guid );
				$imported[ $guid ] = true;
				$stats['imported']++;
			}
		}
	}

	set_transient( 'estrato_rss_last_stats', $stats, DAY_IN_SECONDS );
	return $stats;
}
