<?php
/**
 * Plugin Name: Unzip Jannah Helper
 * Description: Extrai jannah.zip e jannah-child.zip já enviados para wp-content/themes/ e ativa o child theme.
 * Version: 1.0.0
 * Author: Cursor Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook( __FILE__, 'ujh_install_jannah_from_zips' );

function ujh_install_jannah_from_zips() {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! class_exists( 'Theme_Upgrader' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}
	require_once ABSPATH . 'wp-admin/includes/theme.php';

	$theme_root = get_theme_root();
	$packages   = array(
		'jannah'       => $theme_root . '/jannah.zip',
		'jannah-child' => $theme_root . '/jannah-child.zip',
	);

	$skin     = new Automatic_Upgrader_Skin();
	$upgrader = new Theme_Upgrader( $skin );

	foreach ( $packages as $slug => $zip_path ) {
		if ( ! file_exists( $zip_path ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html( 'Arquivo não encontrado: ' . $zip_path ) );
		}
		$result = $upgrader->install( $zip_path, array( 'overwrite_package' => true ) );
		if ( is_wp_error( $result ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html( 'Erro ao instalar ' . $slug . ': ' . $result->get_error_message() ) );
		}
	}

	switch_theme( 'jannah-child' );
	update_option( 'ujh_jannah_installed', time() );
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'switch_themes' ) ) {
		return;
	}
	if ( get_option( 'ujh_jannah_installed' ) && wp_get_theme()->get_stylesheet() === 'jannah-child' ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>Jannah instalado!</strong> Vá em <em>Jannah → Install Demos</em> e importe o demo <strong>SEO</strong>.</p></div>';
	}
} );
