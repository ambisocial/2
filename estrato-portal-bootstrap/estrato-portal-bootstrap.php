<?php
/**
 * Plugin Name: Estrato Portal Bootstrap
 * Description: Provisiona tema (PressGrid ou Newspack), plugins e integração com pipeline/RSS por portal.
 * Version: 1.0.0
 * Author: Cursor Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_PORTAL_VERSION', '1.0.0' );
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
			'primary_color'  => '#fe4c1c',
			'breaking_label' => 'Mercados',
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
}
