<?php
/**
 * Plugin Name: Estrato Portal Bootstrap
 * Description: Provisiona tema (PressGrid ou Newspack), plugins e integração com pipeline/RSS por portal.
 * Version: 1.18.8
 * Author: Cursor Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/pressgrid-pt-br.php';
require_once __DIR__ . '/seo-robots.php';
require_once __DIR__ . '/seo-schema.php';
require_once __DIR__ . '/eeat.php';
require_once __DIR__ . '/author-personas.php';
require_once __DIR__ . '/syndication.php';
require_once __DIR__ . '/content-quality.php';
require_once __DIR__ . '/nav-visual.php';
require_once __DIR__ . '/nav-mega-menu.php';
require_once __DIR__ . '/nav-header-g1.php';
require_once __DIR__ . '/nav-footer-ft.php';
require_once __DIR__ . '/seo-aeo.php';
require_once __DIR__ . '/gate-safeguards.php';
require_once __DIR__ . '/taxonomy.php';
require_once __DIR__ . '/portal-taxonomy.php';
require_once __DIR__ . '/google-news.php';
require_once __DIR__ . '/seo-category.php';
require_once __DIR__ . '/pipeline-sanitize.php';
require_once __DIR__ . '/pipeline-categorization.php';
require_once __DIR__ . '/design-system.php';
require_once __DIR__ . '/home-layout.php';
require_once __DIR__ . '/single-article.php';
require_once __DIR__ . '/ticker-br.php';
require_once __DIR__ . '/retention.php';
require_once __DIR__ . '/ops.php';

define( 'ESTRATO_PORTAL_VERSION', '1.18.8' );
define( 'ESTRATO_PORTAL_CONFIG_OPTION', 'estrato_portal_config' );

register_activation_hook( __FILE__, 'estrato_portal_activate' );
add_action( 'admin_notices', 'estrato_portal_admin_notice' );

/**
 * Config padrão (sobrescrita por update_option ou YAML no deploy).
 *
 * @return array<string, mixed>
 */
function estrato_portal_default_config() {
	return array(
		'id'       => 'estrato-finance',
		'title'    => 'Estrato',
		'tagline'  => '',
		'language' => 'pt_BR',
		'theme'    => array(
			'family'        => 'pressgrid',
			'slug'          => 'pressgrid',
			'source'        => 'https://github.com/stantchev/PressGrid-WordPress-Theme/releases/download/v2.5.0/pressgrid-v2.5.0.zip',
			'plugin_source' => '',
			'child_source'  => '',
		),
		'content'  => array(
			'mode'              => 'rss_and_pipeline',
			'rss_preset'        => 'brasil-financeiro',
			'pipeline_enabled'  => true,
		),
		'branding' => array(
			'primary_color'   => '#000000',
			'accent_color'    => '#9AFF33',
			'secondary_color' => '#1a1a1a',
			'logo_file'       => 'estrato-logo.png',
			'breaking_label'  => 'Mercados',
		),
	);
}

/**
 * @return array<string, mixed>
 */
function estrato_portal_get_config() {
	$config = get_option( ESTRATO_PORTAL_CONFIG_OPTION, array() );
	if ( ! is_array( $config ) ) {
		$config = array();
	}
	return array_replace_recursive( estrato_portal_default_config(), $config );
}

/**
 * @param string $url
 * @return string|WP_Error
 */
function estrato_portal_download( $url ) {
	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$tmp = download_url( $url, 300 );
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	return $tmp;
}

/**
 * @param string $zip_path
 * @param string $type theme|plugin
 * @return true|WP_Error
 */
function estrato_portal_install_zip( $zip_path, $type = 'theme' ) {
	if ( ! class_exists( 'Theme_Upgrader' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}
	require_once ABSPATH . 'wp-admin/includes/theme.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$skin     = new Automatic_Upgrader_Skin();
	$upgrader = 'theme' === $type ? new Theme_Upgrader( $skin ) : new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $zip_path, array( 'overwrite_package' => true ) );

	if ( is_wp_error( $result ) ) {
		return $result;
	}
	return true;
}

/**
 * PressGrid zip extrai como PressGrid-WordPress-Theme-x.x.x/pressgrid/
 *
 * @param string $family
 * @return string
 */
function estrato_portal_resolve_theme_slug( $family ) {
	if ( 'newspack' === $family ) {
		$config = estrato_portal_get_config();
		return ! empty( $config['theme']['slug'] ) ? $config['theme']['slug'] : 'newspack-scott';
	}
	return 'pressgrid';
}

function estrato_portal_activate() {
	$config = estrato_portal_get_config();
	$log    = array();

	if ( ! empty( $config['title'] ) ) {
		update_option( 'blogname', sanitize_text_field( $config['title'] ) );
	}
	if ( ! empty( $config['tagline'] ) ) {
		update_option( 'blogdescription', sanitize_text_field( $config['tagline'] ) );
	}

	$theme_cfg = $config['theme'];
	$family    = $theme_cfg['family'] ?? 'pressgrid';

	if ( 'newspack' === $family && ! empty( $theme_cfg['plugin_source'] ) ) {
		$zip = estrato_portal_download( $theme_cfg['plugin_source'] );
		if ( ! is_wp_error( $zip ) ) {
			$install = estrato_portal_install_zip( $zip, 'plugin' );
			$log[]   = is_wp_error( $install ) ? $install->get_error_message() : 'newspack-plugin ok';
			@unlink( $zip );
		}
	}

	$theme_zip_url = ! empty( $theme_cfg['child_source'] ) ? $theme_cfg['child_source'] : ( $theme_cfg['source'] ?? '' );
	if ( $theme_zip_url ) {
		$zip = estrato_portal_download( $theme_zip_url );
		if ( ! is_wp_error( $zip ) ) {
			$install = estrato_portal_install_zip( $zip, 'theme' );
			$log[]   = is_wp_error( $install ) ? $install->get_error_message() : 'theme zip ok';
			@unlink( $zip );
		} else {
			$log[] = 'theme download: ' . $zip->get_error_message();
		}
	}

	$slug = estrato_portal_resolve_theme_slug( $family );
	switch_theme( $slug );
	$log[] = 'theme active: ' . $slug;

	update_option( 'WPLANG', $config['language'] ?? 'pt_BR' );

	$content_mode = $config['content']['mode'] ?? 'rss_and_pipeline';
	if ( function_exists( 'estrato_rss_apply_content_mode' ) ) {
		estrato_rss_apply_content_mode( $content_mode );
		$log[] = 'rss mode: ' . $content_mode;
	} elseif ( function_exists( 'estrato_rss_activate' ) ) {
		estrato_rss_activate();
		$log[] = 'rss bootstrap ok';
	}

	if ( ! empty( $config['timezone'] ) ) {
		update_option( 'timezone_string', sanitize_text_field( $config['timezone'] ) );
	}

	$branding_log = estrato_portal_apply_branding( $config );
	if ( $branding_log ) {
		$log[] = $branding_log;
	}

	set_transient(
		'estrato_portal_activation_log',
		array(
			'config' => $config,
			'log'    => $log,
		),
		DAY_IN_SECONDS
	);
}

function estrato_portal_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$log = get_transient( 'estrato_portal_activation_log' );
	if ( ! $log ) {
		return;
	}
	echo '<div class="notice notice-success is-dismissible"><p><strong>Estrato Portal:</strong> ';
	echo esc_html( implode( ' | ', $log['log'] ) );
	echo '</p></div>';
	delete_transient( 'estrato_portal_activation_log' );
}

/**
 * Injeta config YAML convertido em JSON no deploy (chamado via mu-plugin ou wp-cli).
 *
 * @param array<string, mixed> $config
 */
function estrato_portal_apply_config( $config ) {
	update_option( ESTRATO_PORTAL_CONFIG_OPTION, $config, false );
	estrato_portal_apply_branding( $config );
	if ( function_exists( 'estrato_rss_apply_preset' ) ) {
		$preset = $config['content']['rss_preset'] ?? 'brasil-financeiro';
		estrato_rss_apply_preset( $preset );
	}
}

/**
 * @param string $hex
 * @return string
 */
function estrato_portal_sanitize_hex_color( $hex ) {
	$hex = sanitize_hex_color( $hex );
	return $hex ? $hex : '#000000';
}

/**
 * Importa o logotipo do plugin para a biblioteca de mídia.
 *
 * @param string $filename Basename em estrato-portal-bootstrap/assets/.
 * @return int Attachment ID ou 0.
 */
function estrato_portal_import_logo( $filename ) {
	$filename = sanitize_file_name( $filename );
	if ( '' === $filename ) {
		return 0;
	}

	$path = plugin_dir_path( __FILE__ ) . 'assets/' . $filename;
	if ( ! is_readable( $path ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_estrato_brand_logo',
			'meta_value'     => $filename,
		)
	);
	if ( ! empty( $existing[0] ) ) {
		return (int) $existing[0];
	}

	$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $contents ) {
		return 0;
	}

	$upload = wp_upload_bits( $filename, null, $contents );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype = wp_check_filetype( $filename, null );
	$attach_id = wp_insert_attachment(
		array(
			'post_title'     => 'Estrato Logo',
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_mime_type' => $filetype['type'],
		),
		$upload['file']
	);
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return 0;
	}

	$meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	if ( ! is_wp_error( $meta ) && $meta ) {
		wp_update_attachment_metadata( $attach_id, $meta );
	}
	update_post_meta( $attach_id, '_estrato_brand_logo', $filename );

	return (int) $attach_id;
}

/**
 * Aplica cores PressGrid, logotipo e breaking news a partir do YAML/config.
 *
 * @param array<string, mixed> $config
 * @return string Log resumido.
 */
function estrato_portal_apply_branding( $config ) {
	$branding = isset( $config['branding'] ) && is_array( $config['branding'] )
		? $config['branding']
		: estrato_portal_default_config()['branding'];

	$primary   = estrato_portal_sanitize_hex_color( $branding['primary_color'] ?? '#000000' );
	$accent    = estrato_portal_sanitize_hex_color( $branding['accent_color'] ?? '#9AFF33' );
	$secondary = estrato_portal_sanitize_hex_color( $branding['secondary_color'] ?? '#1a1a1a' );

	set_theme_mod( 'pressgrid_primary_color', $primary );
	set_theme_mod( 'pressgrid_accent_color', $accent );
	set_theme_mod( 'pressgrid_secondary_color', $secondary );
	set_theme_mod( 'pressgrid_link_hover_color', $accent );

	$logo_file = ! empty( $branding['logo_file'] ) ? (string) $branding['logo_file'] : 'estrato-logo.png';
	$logo_id   = estrato_portal_import_logo( $logo_file );
	if ( $logo_id ) {
		set_theme_mod( 'custom_logo', $logo_id );
	}

	$breaking_slug = sanitize_title( $branding['breaking_category'] ?? 'mercados' );
	$breaking_term = get_term_by( 'slug', $breaking_slug, 'category' );
	if ( $breaking_term && ! is_wp_error( $breaking_term ) ) {
		set_theme_mod( 'pressgrid_breaking_news_category', (int) $breaking_term->term_id );
	}

	return $logo_id ? 'branding ok (logo #' . $logo_id . ')' : 'branding ok (sem logo)';
}
