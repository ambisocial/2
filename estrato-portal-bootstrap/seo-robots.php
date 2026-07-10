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
 * Regras adicionais após o bloco Yoast.
 *
 * @param string $output
 * @param bool   $public
 * @return string
 */
function estrato_seo_filter_robots_txt( $output, $public ) {
	if ( ! $public ) {
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
add_filter( 'robots_txt', 'estrato_seo_filter_robots_txt', 20, 2 );

/**
 * Registra endpoint news-sitemap.xml.
 */
function estrato_seo_register_news_sitemap_rewrite() {
	add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?estrato_news_sitemap=1', 'top' );
}
add_action( 'init', 'estrato_seo_register_news_sitemap_rewrite' );

/**
 * @param array<string, mixed> $vars
 * @return array<string, mixed>
 */
function estrato_seo_news_sitemap_query_var( $vars ) {
	$vars[] = 'estrato_news_sitemap';
	return $vars;
}
add_filter( 'query_vars', 'estrato_seo_news_sitemap_query_var' );

/**
 * Renderiza Google News sitemap (últimas 48h).
 */
function estrato_seo_render_news_sitemap() {
	if ( ! get_query_var( 'estrato_news_sitemap' ) ) {
		return;
	}

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
add_action( 'template_redirect', 'estrato_seo_render_news_sitemap' );

/**
 * @param int $post_id
 * @return bool
 */
function estrato_seo_post_is_noindex( $post_id ) {
	$noindex = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
	return '1' === (string) $noindex;
}

/**
 * Flush rewrite rules quando o plugin é ativado/atualizado.
 */
function estrato_seo_maybe_flush_rewrites() {
	$version = get_option( 'estrato_seo_rewrite_version', '' );
	if ( ESTRATO_PORTAL_VERSION !== $version ) {
		estrato_seo_register_news_sitemap_rewrite();
		flush_rewrite_rules( false );
		update_option( 'estrato_seo_rewrite_version', ESTRATO_PORTAL_VERSION, false );
	}
}
add_action( 'init', 'estrato_seo_maybe_flush_rewrites', 99 );
