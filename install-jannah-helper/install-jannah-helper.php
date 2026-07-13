<?php
/**
 * Plugin Name: Install Jannah Helper
 * Description: Baixa e instala o tema Jannah automaticamente ao ativar.
 * Version: 1.0.0
 * Author: Cursor Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

register_activation_hook( __FILE__, 'ijh_install_jannah_on_activation' );

function ijh_install_jannah_on_activation() {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! class_exists( 'Theme_Upgrader' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}
	require_once ABSPATH . 'wp-admin/includes/theme.php';

	$packages = array(
		'jannah'       => 'https://github.com/ambisocial/2/raw/cursor/extract-php-drive-zip-2c04/Jannah-wp-theme/jannah.zip',
		'jannah-child' => 'https://github.com/ambisocial/2/raw/cursor/extract-php-drive-zip-2c04/Jannah-wp-theme/jannah-child.zip',
	);

	$skin     = new Automatic_Upgrader_Skin();
	$upgrader = new Theme_Upgrader( $skin );

	foreach ( $packages as $slug => $url ) {
		$result = $upgrader->install( $url, array( 'overwrite_package' => true ) );
		if ( is_wp_error( $result ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html( 'Erro ao instalar ' . $slug . ': ' . $result->get_error_message() ) );
		}
	}

	switch_theme( 'jannah-child' );
	update_option( 'ijh_jannah_installed', time() );
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'switch_themes' ) ) {
		return;
	}
	if ( get_option( 'ijh_jannah_installed' ) && wp_get_theme()->get_stylesheet() === 'jannah-child' ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>Jannah instalado!</strong> Vá em <em>Jannah Theme Options</em> para importar a demo.</p></div>';
	}
} );
