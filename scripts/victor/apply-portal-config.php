<?php
/**
 * Lê portals/estrato-*.yaml e aplica config + branding no WordPress.
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file apply-portal-config.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: 'estrato-finance';
$portal = preg_replace( '/[^a-z0-9\-]/', '', strtolower( $portal ) );

$paths = array(
	"/var/www/estrato/repo/portals/{$portal}.yaml",
	dirname( __DIR__, 2 ) . "/portals/{$portal}.yaml",
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
	echo "YAML não encontrado para portal={$portal}\n";
	return;
}

/**
 * Parser YAML mínimo (sem extensão php-yaml).
 *
 * @param string $raw
 * @return array<string, mixed>
 */
function estrato_parse_portal_yaml_minimal( $raw ) {
	$config = array(
		'content'  => array(),
		'branding' => array(),
	);
	$section = '';
	foreach ( preg_split( '/\r?\n/', $raw ) as $line ) {
		if ( preg_match( '/^content:\s*$/', $line ) ) {
			$section = 'content';
			continue;
		}
		if ( preg_match( '/^branding:\s*$/', $line ) ) {
			$section = 'branding';
			continue;
		}
		if ( preg_match( '/^(\w+):\s*(.+)$/', $line, $m ) && ! preg_match( '/^\s/', $line ) ) {
			$config[ $m[1] ] = trim( $m[2], " \t\"'" );
			$section           = '';
			continue;
		}
		if ( preg_match( '/^\s{2}(\w+):\s*(.+)$/', $line, $m ) && $section ) {
			$config[ $section ][ $m[1] ] = trim( $m[2], " \t\"'" );
		}
	}
	return $config;
}

if ( function_exists( 'yaml_parse_file' ) ) {
	$config = yaml_parse_file( $yaml_path );
} else {
	$config = estrato_parse_portal_yaml_minimal( file_get_contents( $yaml_path ) ); // phpcs:ignore
}

if ( ! is_array( $config ) || empty( $config['content']['rss_preset'] ) ) {
	echo "YAML inválido ou rss_preset ausente ({$portal})\n";
	return;
}

echo "portal yaml: {$yaml_path}\n";

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

if ( function_exists( 'estrato_rss_sync_portal_taxonomy' ) ) {
	$preset = $config['content']['rss_preset'] ?? 'brasil-financeiro';
	$sync   = estrato_rss_sync_portal_taxonomy( $preset );
	echo 'taxonomy sync: ' . wp_json_encode( $sync ) . "\n";
}

if ( ! empty( $config['title'] ) ) {
	update_option( 'blogname', sanitize_text_field( $config['title'] ) );
}
if ( ! empty( $config['tagline'] ) ) {
	update_option( 'blogdescription', sanitize_text_field( $config['tagline'] ) );
}

echo "done\n";
