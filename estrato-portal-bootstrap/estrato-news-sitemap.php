<?php
/**
 * Bootstrap direto para /news-sitemap.xml (fallback quando rewrite WP falha).
 * Nginx: location = /news-sitemap.xml → este arquivo via fastcgi.
 */
define( 'WP_USE_THEMES', false );
require __DIR__ . '/wp-load.php';

if ( function_exists( 'estrato_seo_output_news_sitemap' ) ) {
	estrato_seo_output_news_sitemap();
}

status_header( 404 );
header( 'Content-Type: text/plain; charset=UTF-8' );
echo 'news-sitemap unavailable';
