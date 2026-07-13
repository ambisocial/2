<?php
/**
 * V3 (auditoria visual 2026-07-13) — Guard hardening no RSS pipeline:
 * antes de qualquer `wp_insert_post`, o host da URL do feed precisa constar
 * em `estrato_rss_import_matrix` do site atual. Impede regressão do bug B2
 * (contaminação cruzada). Compatível com os dois paths de import:
 *   - `estrato_rss_run_import_matrix()` (schema v2, satélites)
 *   - `estrato_rss_run_import()` legado (`foreach $config`).
 *
 * @package EstratoRssBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna hosts únicos permitidos pela matriz do site atual, cacheado por
 * 5 minutos.
 *
 * @return array<string, bool>
 */
function estrato_rss_guard_matrix_hosts() {
	$cached = wp_cache_get( 'estrato_rss_guard_matrix_hosts', 'estrato_rss' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$hosts  = array();
	$matrix = get_option( 'estrato_rss_import_matrix', array() );
	if ( is_array( $matrix ) ) {
		foreach ( $matrix as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$node = $entry['node'] ?? null;
			if ( is_string( $node ) ) {
				$decoded = json_decode( $node, true );
				if ( is_array( $decoded ) ) {
					$node = $decoded;
				}
			}
			$feeds = ( is_array( $node ) && ! empty( $node['feeds'] ) ) ? $node['feeds'] : array();
			if ( empty( $feeds ) && ! empty( $entry['feeds'] ) && is_array( $entry['feeds'] ) ) {
				$feeds = $entry['feeds'];
			}
			foreach ( (array) $feeds as $feed ) {
				$url = is_string( $feed ) ? $feed : ( isset( $feed['url'] ) ? (string) $feed['url'] : '' );
				if ( ! $url ) {
					continue;
				}
				$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
				if ( $host ) {
					$hosts[ $host ] = true;
				}
			}
		}
	}
	wp_cache_set( 'estrato_rss_guard_matrix_hosts', $hosts, 'estrato_rss', 300 );
	return $hosts;
}

/**
 * @param string $url
 * @return bool
 */
function estrato_rss_guard_host_allowed( $url ) {
	$hosts = estrato_rss_guard_matrix_hosts();
	if ( empty( $hosts ) ) {
		return true;
	}
	$host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
	if ( ! $host ) {
		return true;
	}
	return isset( $hosts[ $host ] );
}

/**
 * Filter final antes de wp_insert_post no pipeline RSS. Se o feed URL
 * (2º parâmetro) não estiver na matriz, `false` sinaliza abortar o insert.
 *
 * @param string $feed_url
 * @return bool
 */
function estrato_rss_guard_allow_insert( $feed_url ) {
	if ( estrato_rss_guard_host_allowed( $feed_url ) ) {
		return true;
	}
	if ( function_exists( 'error_log' ) ) {
		$host = wp_parse_url( (string) $feed_url, PHP_URL_HOST );
		error_log( '[estrato-rss][guard] SKIP off-matrix host=' . $host . ' at ' . home_url() );
	}
	do_action( 'estrato_rss_guard_off_matrix_skipped', $feed_url );
	return false;
}

/**
 * Invalida o cache quando a matriz for atualizada via WP-CLI/admin.
 *
 * @param mixed $option
 */
function estrato_rss_guard_flush_cache_on_matrix_change( $option = null ) {
	wp_cache_delete( 'estrato_rss_guard_matrix_hosts', 'estrato_rss' );
}
add_action( 'update_option_estrato_rss_import_matrix', 'estrato_rss_guard_flush_cache_on_matrix_change', 10, 0 );
add_action( 'add_option_estrato_rss_import_matrix', 'estrato_rss_guard_flush_cache_on_matrix_change', 10, 0 );
