<?php
/**
 * Lê portals/estrato-finance.yaml e aplica config + branding no WordPress.
 * Uso: wp eval-file apply-portal-config.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$paths = array(
	'/var/www/estrato/repo/portals/estrato-finance.yaml',
	dirname( __DIR__, 2 ) . '/portals/estrato-finance.yaml',
);

$yaml_path = '';
foreach ( $paths as $path ) {
	if ( is_readable( $path ) ) {
		$yaml_path = $path;
		break;
	}
}

if ( ! $yaml_path ) {
	echo "YAML não encontrado\n";
	return;
}

if ( ! function_exists( 'yaml_parse_file' ) ) {
	$raw    = file_get_contents( $yaml_path ); // phpcs:ignore
	$config = array();
	foreach ( preg_split( '/\r?\n/', $raw ) as $line ) {
		if ( preg_match( '/^(\w+):\s*(.+)$/', trim( $line ), $m ) && false === strpos( $line, '  ' ) ) {
			$config[ $m[1] ] = trim( $m[2], " \t\"'" );
		}
	}
	$config = array(
		'id'       => 'estrato-finance',
		'title'    => 'Estrato',
		'tagline'  => 'Economia, mercados e finanças',
		'language' => 'pt_BR',
		'timezone' => 'America/Sao_Paulo',
		'content'  => array(
			'mode'       => 'pipeline_primary',
			'rss_preset' => 'brasil-financeiro',
		),
		'branding' => array(
			'primary_color'     => '#000000',
			'accent_color'      => '#9AFF33',
			'secondary_color'   => '#1a1a1a',
			'logo_file'         => 'estrato-logo.png',
			'breaking_category' => 'mercados',
		),
	);
} else {
	$config = yaml_parse_file( $yaml_path );
}

if ( ! is_array( $config ) ) {
	echo "YAML inválido\n";
	return;
}

if ( function_exists( 'estrato_portal_apply_config' ) ) {
	estrato_portal_apply_config( $config );
	echo "estrato_portal_apply_config ok\n";
} elseif ( function_exists( 'estrato_portal_apply_branding' ) ) {
	estrato_portal_apply_branding( $config );
	echo "estrato_portal_apply_branding ok\n";
}

if ( function_exists( 'estrato_rss_apply_content_mode' ) ) {
	$mode = $config['content']['mode'] ?? 'pipeline_primary';
	estrato_rss_apply_content_mode( $mode );
	echo "rss mode: $mode\n";
}

if ( function_exists( 'estrato_rss_apply_preset' ) ) {
	$preset = $config['content']['rss_preset'] ?? 'brasil-financeiro';
	estrato_rss_apply_preset( $preset );
	echo "rss preset: $preset\n";
}

if ( ! empty( $config['title'] ) ) {
	update_option( 'blogname', sanitize_text_field( $config['title'] ) );
}
if ( ! empty( $config['tagline'] ) ) {
	update_option( 'blogdescription', sanitize_text_field( $config['tagline'] ) );
}

echo "done\n";
