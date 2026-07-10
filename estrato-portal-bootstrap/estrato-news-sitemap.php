<?php
/**
 * Bootstrap direto para /news-sitemap.xml (nginx fastcgi).
 */
define( 'WP_USE_THEMES', false );
require __DIR__ . '/wp-load.php';

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
$language  = substr( get_bloginfo( 'language' ), 0, 2 ) ?: 'pt';

status_header( 200 );
header( 'Content-Type: application/xml; charset=UTF-8', true );
header( 'X-Robots-Tag: noindex, follow', true );

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

foreach ( $posts as $post ) {
	if ( '1' === (string) get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex', true ) ) {
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
