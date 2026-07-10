<?php
/**
 * Bootstrap direto para /news-sitemap.xml (nginx fastcgi).
 */
define( 'WP_USE_THEMES', false );
require __DIR__ . '/wp-load.php';

$seo_file = __DIR__ . '/wp-content/plugins/estrato-portal-bootstrap/seo-robots.php';
if ( is_readable( $seo_file ) ) {
	require_once $seo_file;
}

if ( function_exists( 'estrato_seo_output_news_sitemap' ) ) {
	estrato_seo_output_news_sitemap();
}

status_header( 404 );
header( 'Content-Type: text/plain; charset=UTF-8' );
echo 'news-sitemap unavailable';
