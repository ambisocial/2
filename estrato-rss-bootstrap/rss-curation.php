<?php
/**
 * Curadoria RSS — deduplicação, validação e saúde dos feeds (Sprint 9).
 *
 * @package EstratoRssBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_RSS_FEED_HEALTH_OPTION', 'estrato_rss_feed_health' );

/**
 * @param string $url
 * @return string
 */
function estrato_rss_normalize_feed_url( $url ) {
	$url = trim( strtolower( (string) $url ) );
	$url = preg_replace( '#/$#', '', $url );
	return $url;
}

/**
 * Remove URLs duplicadas entre categorias (primeira ocorrência na menu_order vence).
 * Inclui feeds de subcategorias tier ≤ 1 (antes só root — satélites ficavam com 2–3 feeds).
 *
 * @param array<string, mixed> $taxonomy
 * @return array{config:array<string,array>,duplicates:int,removed:int}
 */
function estrato_rss_dedupe_feed_matrix( $taxonomy ) {
	$order    = $taxonomy['menu_order'] ?? array();
	$seen     = array();
	$config   = array();
	$dupes    = 0;
	$removed  = 0;
	$cats     = $taxonomy['categories'] ?? array();

	foreach ( $order as $slug ) {
		if ( empty( $cats[ $slug ] ) || ! is_array( $cats[ $slug ] ) ) {
			continue;
		}
		$cat    = $cats[ $slug ];
		$feeds  = array();
		$raw    = $cat['feeds'] ?? array();
		// Subcategorias: anexar feeds tier ≤ 1 ao root da editoria.
		if ( ! empty( $cat['subcategories'] ) && is_array( $cat['subcategories'] ) ) {
			foreach ( $cat['subcategories'] as $child ) {
				if ( ! is_array( $child ) || empty( $child['feeds'] ) || ! is_array( $child['feeds'] ) ) {
					continue;
				}
				foreach ( $child['feeds'] as $sub_feed ) {
					$tier = isset( $sub_feed['tier'] ) ? (int) $sub_feed['tier'] : 2;
					if ( $tier > 1 ) {
						continue;
					}
					$raw[] = $sub_feed;
				}
			}
		}
		foreach ( $raw as $feed ) {
			if ( empty( $feed['url'] ) ) {
				continue;
			}
			$key = estrato_rss_normalize_feed_url( $feed['url'] );
			if ( isset( $seen[ $key ] ) ) {
				++$dupes;
				++$removed;
				continue;
			}
			$seen[ $key ] = $slug;
			$feeds[]      = array(
				'title' => (string) ( $feed['title'] ?? '' ),
				'url'   => (string) $feed['url'],
				'tier'  => isset( $feed['tier'] ) ? (int) $feed['tier'] : 2,
			);
		}
		$config[ $slug ] = array(
			'name'        => (string) ( $cat['name'] ?? $slug ),
			'description' => (string) ( $cat['description'] ?? '' ),
			'feeds'       => $feeds,
		);
	}

	return array(
		'config'      => $config,
		'duplicates'  => $dupes,
		'removed'     => $removed,
	);
}

/**
 * @param string $url
 * @return array{ok:bool,error:string,items:int}
 */
function estrato_rss_validate_feed_url( $url ) {
	if ( ! function_exists( 'fetch_feed' ) ) {
		require_once ABSPATH . WPINC . '/feed.php';
	}

	$feed = fetch_feed( $url );
	if ( is_wp_error( $feed ) ) {
		return array(
			'ok'    => false,
			'error' => $feed->get_error_message(),
			'items' => 0,
		);
	}

	$items = $feed->get_items( 0, 1 );
	return array(
		'ok'    => ! empty( $items ),
		'error' => empty( $items ) ? 'feed vazio' : '',
		'items' => is_array( $items ) ? count( $items ) : 0,
	);
}

/**
 * Valida matriz, remove feeds quebrados e persiste config ativa.
 *
 * @param bool $skip_validation
 * @return array<string, mixed>
 */
function estrato_rss_apply_curation( $skip_validation = false ) {
	$taxonomy = estrato_rss_load_active_taxonomy();
	$deduped  = estrato_rss_dedupe_feed_matrix( $taxonomy );
	$config   = $deduped['config'];
	$health   = array();
	$broken   = 0;
	$healthy  = 0;
	$active   = array();

	foreach ( $config as $slug => $data ) {
		$active_feeds = array();
		foreach ( $data['feeds'] as $feed ) {
			$url = $feed['url'];
			if ( ! $skip_validation ) {
				$result = estrato_rss_validate_feed_url( $url );
				$health[ $url ] = array_merge(
					$result,
					array(
						'title'    => $feed['title'],
						'category' => $slug,
						'tier'     => $feed['tier'] ?? 2,
						'checked'  => gmdate( 'c' ),
					)
				);
				if ( ! $result['ok'] ) {
					++$broken;
					continue;
				}
				++$healthy;
			}
			$active_feeds[] = array(
				'title' => $feed['title'],
				'url'   => $url,
			);
		}
		$active[ $slug ] = array(
			'name'        => $data['name'],
			'description' => $data['description'],
			'feeds'       => $active_feeds,
		);
	}

	update_option( ESTRATO_RSS_OPTION_FEEDS, $active, false );
	update_option( ESTRATO_RSS_FEED_HEALTH_OPTION, $health, false );

	return array(
		'ok'         => true,
		'duplicates' => $deduped['duplicates'],
		'removed'    => $deduped['removed'],
		'healthy'    => $healthy,
		'broken'     => $broken,
		'categories' => count( $active ),
	);
}

/**
 * @return int URLs duplicadas na config ativa.
 */
function estrato_rss_count_duplicate_feed_urls() {
	$config = estrato_rss_get_config();
	$seen   = array();
	$dupes  = 0;
	foreach ( $config as $data ) {
		foreach ( $data['feeds'] as $feed ) {
			$key = estrato_rss_normalize_feed_url( $feed['url'] );
			if ( isset( $seen[ $key ] ) ) {
				++$dupes;
			}
			$seen[ $key ] = true;
		}
	}
	return $dupes;
}

/**
 * @return float Ratio 0–1 de feeds saudáveis na última validação.
 */
function estrato_rss_feed_health_ratio() {
	$health = get_option( ESTRATO_RSS_FEED_HEALTH_OPTION, array() );
	if ( ! is_array( $health ) || empty( $health ) ) {
		return -1;
	}
	$total = count( $health );
	$ok    = 0;
	foreach ( $health as $row ) {
		if ( ! empty( $row['ok'] ) ) {
			++$ok;
		}
	}
	return $total > 0 ? $ok / $total : 0;
}

/**
 * IndexNow ping por categoria (S9.7).
 *
 * @param array<int, string> $slugs
 * @return int URLs enviadas.
 */
function estrato_rss_index_categories_indexnow( $slugs ) {
	if ( ! function_exists( 'estrato_aeo_get_indexnow_key' ) ) {
		return 0;
	}

	$key  = estrato_aeo_get_indexnow_key();
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! $key || ! $host ) {
		return 0;
	}

	$urls = array();
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				$urls[] = $link;
			}
		}
	}

	if ( empty( $urls ) ) {
		return 0;
	}

	wp_remote_post(
		'https://api.indexnow.org/indexnow',
		array(
			'timeout' => 15,
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => wp_json_encode(
				array(
					'host'        => $host,
					'key'         => $key,
					'keyLocation' => home_url( '/estrato-indexnow-key.txt' ),
					'urlList'     => array_values( $urls ),
				)
			),
		)
	);

	update_option( 'estrato_rss_last_category_index', gmdate( 'c' ), false );
	return count( $urls );
}
