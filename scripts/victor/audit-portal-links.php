<?php
/**
 * Audita links do header G1, menu principal e footer FT.
 *
 * Uso: ESTRATO_PORTAL=estrato-finance wp eval-file scripts/victor/audit-portal-links.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
$urls      = array();

if ( function_exists( 'estrato_nav_network_catalog' ) ) {
	foreach ( estrato_nav_network_catalog() as $node ) {
		if ( $node['id'] === ( getenv( 'ESTRATO_PORTAL' ) ?: '' ) ) {
			continue;
		}
		$urls[] = array( 'source' => 'rede', 'label' => $node['name'], 'url' => $node['url'] );
	}
}

$menu = wp_get_nav_menu_object( 'Estrato Principal' );
if ( $menu ) {
	$items = wp_get_nav_menu_items( $menu->term_id );
	if ( $items ) {
		foreach ( $items as $item ) {
			if ( empty( $item->url ) ) {
				continue;
			}
			$urls[] = array(
				'source' => 'menu',
				'label'  => $item->title,
				'url'    => $item->url,
			);
		}
	}
}

if ( function_exists( 'estrato_ft_group_businesses_all' ) ) {
	foreach ( estrato_ft_group_businesses_all() as $biz ) {
		if ( ! empty( $biz['text_only'] ) || empty( $biz['url'] ) ) {
			continue;
		}
		$urls[] = array(
			'source' => 'footer-grupo',
			'label'  => $biz['name'],
			'url'    => $biz['url'],
		);
	}
}

$footer_sections = function_exists( 'estrato_ft_footer_accordion_sections' )
	? estrato_ft_footer_accordion_sections()
	: array();
foreach ( $footer_sections as $section ) {
	foreach ( $section['links'] as $link ) {
		if ( empty( $link['url'] ) ) {
			continue;
		}
		$urls[] = array(
			'source' => 'footer-' . $section['id'],
			'label'  => $link['label'],
			'url'    => $link['url'],
		);
	}
}

$seen   = array();
$broken = array();
$ok     = 0;

foreach ( $urls as $row ) {
	$url = (string) $row['url'];
	if ( isset( $seen[ $url ] ) ) {
		continue;
	}
	$seen[ $url ] = true;

	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	$code = 0;

	if ( $host && $host !== $home_host ) {
		$response = wp_remote_head(
			$url,
			array(
				'timeout'     => 12,
				'redirection' => 3,
				'sslverify'   => false,
			)
		);
		if ( is_wp_error( $response ) ) {
			$broken[] = array_merge( $row, array( 'status' => 'error', 'detail' => $response->get_error_message() ) );
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 400 ) {
			++$ok;
			continue;
		}
		$broken[] = array_merge( $row, array( 'status' => (string) $code, 'detail' => '' ) );
		continue;
	}

	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$path = trim( $path, '/' );
	if ( '' === $path ) {
		++$ok;
		continue;
	}

	if ( preg_match( '/\.(xml|txt)$/i', $path ) ) {
		++$ok;
		continue;
	}

	if ( 0 === strpos( $path, 'category/' ) ) {
		$slug = substr( $path, strlen( 'category/' ) );
		$term = get_term_by( 'slug', $slug, 'category' );
		if ( ( ! $term || is_wp_error( $term ) ) && false !== strpos( $slug, '/' ) ) {
			$parts = array_filter( explode( '/', $slug ) );
			$last  = (string) end( $parts );
			$term  = get_term_by( 'slug', $last, 'category' );
			if ( ( ! $term || is_wp_error( $term ) ) && count( $parts ) >= 2 ) {
				$joined = sanitize_title( implode( '-', $parts ) );
				$term   = get_term_by( 'slug', $joined, 'category' );
			}
		}
		if ( $term && ! is_wp_error( $term ) ) {
			++$ok;
		} else {
			$broken[] = array_merge( $row, array( 'status' => '404', 'detail' => 'category missing' ) );
		}
		continue;
	}

	if ( 0 === strpos( $path, 'tudo-sobre/' ) ) {
		$page = get_page_by_path( $path );
		if ( $page && 'publish' === $page->post_status ) {
			++$ok;
		} else {
			$broken[] = array_merge( $row, array( 'status' => '404', 'detail' => 'page missing' ) );
		}
		continue;
	}

	$page = get_page_by_path( $path );
	if ( $page && 'publish' === $page->post_status ) {
		++$ok;
		continue;
	}

	$post = get_page_by_path( $path, OBJECT, 'post' );
	if ( $post && 'publish' === $post->post_status ) {
		++$ok;
		continue;
	}

	$broken[] = array_merge( $row, array( 'status' => '404', 'detail' => 'internal missing' ) );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: 'estrato-finance';
echo $portal . ' links_ok=' . $ok . ' links_broken=' . count( $broken ) . PHP_EOL;
foreach ( $broken as $b ) {
	echo 'BROKEN [' . $b['source'] . '] ' . $b['label'] . ' => ' . $b['url'] . ' (' . $b['status'] . ( $b['detail'] ? ': ' . $b['detail'] : '' ) . ')' . PHP_EOL;
}
