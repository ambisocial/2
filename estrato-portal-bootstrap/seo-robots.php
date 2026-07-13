<?php
/**
 * Robots.txt estendido + news-sitemap.xml (padrão BPMoney / Money Times).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Regras dentro do bloco Yoast + sitemap news.
 *
 * @param object $robots_txt_helper Yoast Robots_Txt_Helper.
 */
function estrato_seo_register_yoast_robots_rules( $robots_txt_helper ) {
	if ( ! is_object( $robots_txt_helper ) ) {
		return;
	}

	if ( method_exists( $robots_txt_helper, 'add_disallow' ) ) {
		$robots_txt_helper->add_disallow( '*', '/wp-admin/' );
		$robots_txt_helper->add_disallow( '*', '/?s=' );
		$robots_txt_helper->add_disallow( '*', '/wp-login.php' );
		$robots_txt_helper->add_disallow( '*', '/xmlrpc.php' );
	}

	if ( method_exists( $robots_txt_helper, 'add_allow' ) ) {
		$robots_txt_helper->add_allow( '*', '/wp-admin/admin-ajax.php' );
	}

	$domain = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( $domain && method_exists( $robots_txt_helper, 'add_sitemap' ) ) {
		$robots_txt_helper->add_sitemap( "https://{$domain}/news-sitemap.xml" );
	}
}
add_action( 'Yoast\WP\SEO\register_robots_rules', 'estrato_seo_register_yoast_robots_rules', 20 );

/**
 * Fallback: append após Yoast (prioridade acima do filter Yoast 99999).
 *
 * @param string $output
 * @param bool   $public
 * @return string
 */
function estrato_seo_filter_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}

	if ( false !== stripos( $output, 'wp-admin' ) && false !== stripos( $output, 'news-sitemap' ) ) {
		return $output;
	}

	$domain = wp_parse_url( home_url(), PHP_URL_HOST );
	$extra  = "\n# Estrato SEO (Sprint 1)\n";
	$extra .= "User-agent: *\n";
	$extra .= "Disallow: /wp-admin/\n";
	$extra .= "Allow: /wp-admin/admin-ajax.php\n";
	$extra .= "Disallow: /?s=\n";
	$extra .= "Disallow: /search/\n";
	$extra .= "Disallow: /wp-login.php\n";
	$extra .= "Disallow: /xmlrpc.php\n";

	if ( $domain && false === stripos( $output, 'news-sitemap' ) ) {
		$extra .= "\nSitemap: https://{$domain}/news-sitemap.xml\n";
	}

	return rtrim( $output ) . "\n" . $extra;
}
add_filter( 'robots_txt', 'estrato_seo_filter_robots_txt', 1000000, 2 );

/**
 * Registra /news-sitemap.xml (compatível com rewrite Yoast).
 */
function estrato_seo_register_news_sitemap_rewrite() {
	add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?estrato_news_sitemap=1', 'top' );
}
add_action( 'init', 'estrato_seo_register_news_sitemap_rewrite' );

/**
 * @param array<int, string> $vars
 * @return array<int, string>
 */
function estrato_seo_news_sitemap_query_var( $vars ) {
	$vars[] = 'estrato_news_sitemap';
	return $vars;
}
add_filter( 'query_vars', 'estrato_seo_news_sitemap_query_var' );

/**
 * Serve news-sitemap.xml via rewrite ou REQUEST_URI direto.
 */
function estrato_seo_maybe_render_news_sitemap() {
	if ( get_query_var( 'estrato_news_sitemap' ) ) {
		estrato_seo_output_news_sitemap();
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '';
	if ( '/news-sitemap.xml' === $uri ) {
		estrato_seo_output_news_sitemap();
	}
}
add_action( 'template_redirect', 'estrato_seo_maybe_render_news_sitemap', 0 );

/**
 * Renderiza Google News sitemap (últimas 48h).
 */
function estrato_seo_output_news_sitemap() {
	$posts = get_posts(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 1000,
			'date_query'             => array(
				array(
					'after' => '48 hours ago',
				),
			),
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$site_name = get_bloginfo( 'name' );
	$language  = substr( get_bloginfo( 'language' ), 0, 2 );
	if ( ! $language ) {
		$language = 'pt';
	}

	status_header( 200 );
	header( 'Content-Type: application/xml; charset=UTF-8', true );
	header( 'X-Robots-Tag: noindex, follow', true );

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

	foreach ( $posts as $post ) {
		if ( estrato_seo_post_is_noindex( $post->ID ) ) {
			continue;
		}

		$loc   = esc_url( get_permalink( $post ) );
		$title = esc_xml( wp_strip_all_tags( get_the_title( $post ) ) );
		$tz    = wp_timezone();
		$dt    = get_post_datetime( $post, 'date', $tz );
		$pub   = $dt ? $dt->format( 'Y-m-d\TH:i:sP' ) : gmdate( 'Y-m-d\TH:i:sP' );

		echo "  <url>\n";
		echo "    <loc>{$loc}</loc>\n";
		echo "    <news:news>\n";
		echo "      <news:publication>\n";
		echo "        <news:name>" . esc_xml( $site_name ) . "</news:name>\n";
		echo "        <news:language>{$language}</news:language>\n";
		echo "      </news:publication>\n";
		echo "      <news:publication_date>{$pub}</news:publication_date>\n";
		echo "      <news:title>{$title}</news:title>\n";
		echo "    </news:news>\n";
		echo "  </url>\n";
	}

	echo '</urlset>';
	exit;
}

/**
 * @param int $post_id
 * @return bool
 */
function estrato_seo_post_is_noindex( $post_id ) {
	$noindex = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
	return '1' === (string) $noindex;
}
