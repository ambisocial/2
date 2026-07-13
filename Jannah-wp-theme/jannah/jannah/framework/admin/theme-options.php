<?php
/**
 * Theme Options
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly


/**
 * Save Theme Settings
 */
function tie_save_theme_options( $data, $refresh = 0 ){


	if( ! empty( $data['tie_options'] ) ) {

		//
		$new_settings = $data['tie_options'];

		// DB option field name
		$option_field = apply_filters( 'TieLabs/theme_options', '' );

		// Prepare the data
		do_action( 'TieLabs/Options/before_update', $new_settings );

		// Remove all empty keys
		$new_settings = TIELABS_ADMIN_HELPER::clean_settings( $new_settings );
		$new_settings = TIELABS_ADMIN_HELPER::array_filter( $new_settings );

		foreach ( $new_settings as $key => $value ) {
			if( ! is_array( $value ) ) {
				$new_settings[ $key ] = str_replace( 'tie-open-tag', '', $value );
			}
		}

		// Remove the Logo Text if it is the same as the site title
		if( ! empty( $new_settings['logo_text'] ) && $new_settings['logo_text'] == get_bloginfo() ){
			unset( $new_settings['logo_text'] );
		}

		// If the site uses SSL, make sure all site links are SSL
		if( TIELABS_HELPER::is_ssl() ){
			array_walk_recursive( $new_settings, array( 'TIELABS_HELPER', 'replace_ssl' ) );
		}

		// Save the settings
		update_option( $option_field, $new_settings );

		// WPML
		if( ! empty( $new_settings['breaking_custom'] ) && is_array( $new_settings['breaking_custom'] ) ) {
			$count = 0;

			foreach ( $new_settings['breaking_custom'] as $custom_text ){
				$count++;

				if( ! empty( $custom_text['text'] ) ) {
					do_action( 'wpml_register_single_string', TIELABS_THEME_SLUG, 'Breaking News Custom Text #'.$count, $custom_text['text'] );
				}

				if( ! empty( $custom_text['link'] ) ) {
					do_action( 'wpml_register_single_string', TIELABS_THEME_SLUG, 'Breaking News Custom Link #'.$count, $custom_text['link'] );
				}
			}
		}
	}

	// After updating the theme options action
	do_action( 'TieLabs/Options/updated' );

	// Reset the cached settings
	$GLOBALS['tie_options'] = false;

	// Refresh the page?
	$refresh = apply_filters( 'TieLabs/options_refresh', $refresh );

	if( ! empty( $refresh ) ){
		esc_html_e( $refresh );
		die();
	}
}


/**
 * Save Options
 */
add_action( 'wp_ajax_tie_theme_data_save', 'tie_save_theme_options_ajax' );
function tie_save_theme_options_ajax(){
	check_ajax_referer( 'tie-theme-data', 'tie-security' );

	if( current_user_can( 'manage_options' ) ){
		tie_save_theme_options( wp_unslash( $_POST ), 1 );
	}
}


/**
 * Add the Theme Options Page to the about page's tabs
 */
add_filter( 'TieLabs/about_tabs', 'tie_about_tabs_options', 99 );
function tie_about_tabs_options( $tabs ){

	$tabs['theme-options'] = array(
		'text' => esc_html__( 'Theme Options', TIELABS_TEXTDOMAIN ),
		'url'  => menu_page_url( 'tie-theme-options', false ),
	);

	return $tabs;
}


/**
 * Add Panel Page
 */
add_action( 'admin_menu', 'tie_admin_menus' );
function tie_admin_menus(){

	// Add the main theme settings page
	add_menu_page(
		$page_title = apply_filters( 'TieLabs/theme_name', 'TieLabs' ),
		$menu_title = 'tietheme',
		$capability = 'switch_themes',
		$menu_slug  = 'tie-theme-options',
		$function   = 'tie_show_theme_options',
		$icon_url   = tie_get_option( 'white_label_menu_icon', TIELABS_TEMPLATE_URL.'/framework/admin/assets/images/tie.png' ),
		$position   = 2
	);

	// Add Sub menus
	$theme_submenus = array(
		array(
			'page_title' => esc_html__( 'Theme Options', TIELABS_TEXTDOMAIN ),
			'menu_title' => esc_html__( 'Theme Options', TIELABS_TEXTDOMAIN ),
			'menu_slug'  => 'tie-theme-options',
			'function'   => 'tie_show_theme_options',
		),
	);

	$theme_submenus = apply_filters( 'TieLabs/panel_submenus', $theme_submenus );

	foreach ( $theme_submenus as $submenu ){
		add_submenu_page(
			$parent_slug = 'tie-theme-options',
			$page_title  = $submenu['page_title'],
			$menu_title  = $submenu['menu_title'],
			$capability  = 'switch_themes',
			$menu_slug   = $submenu['menu_slug'],
			$function    = $submenu['function']
		);
	}


	if( ! TIELABS_ADMIN_HELPER::is_theme_options_page() ) {
		return;
	}

	// Reset settings
	if( isset( $_REQUEST['reset-settings'] ) && check_admin_referer( 'reset-theme-settings', 'reset_nonce' ) ){

		$default_data = tie_default_theme_settings();
		tie_save_theme_options( $default_data );

		# Redirect to the theme options page
		wp_safe_redirect( add_query_arg( array( 'page' => 'tie-theme-options', 'reset' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	// Export Settings
	elseif( isset( $_REQUEST['export-settings'] ) && check_admin_referer( 'export-theme-settings', 'export_nonce' ) ){

		global $wpdb;

		$stored_options = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name = '%s'", apply_filters( 'TieLabs/theme_options', '' ) ));

		header( 'Cache-Control: public, must-revalidate' );
		header( 'Pragma: hack' );
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="'. TIELABS_THEME_SLUG .'-options-'.date("dMy").'.dat"');
		echo json_encode( unserialize( $stored_options[0]->option_value ) );
		die();
	}

	// Import the settings
	elseif( isset( $_FILES[ 'tie_import_file' ] ) && check_admin_referer( 'tie-theme-data', 'tie-security' ) ){
		if( $_FILES['tie_import_file']['error'] > 0 ){
			// error
		}
		else {
			$options = json_decode( file_get_contents( $_FILES['tie_import_file']['tmp_name'] ), true );

			if ( ! empty( $options ) && is_array( $options ) ) {
				update_option( apply_filters( 'TieLabs/theme_options', '' ), $options );
			}
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'tie-theme-options', 'import' => 'true' ), admin_url( 'admin.php' ) ) );
		exit;
	}

}


/**
 * Dark Mode
 */
add_Action( 'admin_head', 'tie_options_dark_skin' );
function tie_options_dark_skin(){
	if( TIELABS_ADMIN_HELPER::is_theme_options_page() ) {
		?>
		<script>
			if( 'undefined' != typeof localStorage ){
				var skin = localStorage.getItem('tie-backend-skin');
				if( skin == 'dark' ){
					var html = document.getElementsByTagName('html')[0].classList;
					html.add('tie-darkskin');
				}
			}
		</script>
		<?php
	}
}


/**
 * Get The Panel Options
 */
function tie_build_theme_option( $value ){
	$data = false;

	if( empty( $value['id'] ) ) {
		$value['id'] = ' ';
	}

	if( tie_get_option( $value['id'] ) ){
		$data = tie_get_option( $value['id'] );
	}

	tie_build_option( $value, 'tie_options['.$value["id"].']', $data );
}



/**
 * Save button
 */
add_action( 'TieLabs/save_button', 'tie_save_options_button' );
function tie_save_options_button(){
	
	$message = '';
	$class = array(
		'tie-save-button',
		'tie-primary-button',
		'button',
		'button-primary',
		'button-hero'
	);

	if( ! empty( $_SERVER['SERVER_NAME'] ) && ( strpos( $_SERVER['SERVER_NAME'], 'tielabs.com' ) !== false || strpos( $_SERVER['SERVER_NAME'], 'localhost' ) !== false ) ){

	}
	elseif( ! tie_get_token() ){
		$class[] = 'tie-disabled-button';
		$message = 'data-message="'. esc_html__( 'Verify your license to unlock this section.', TIELABS_TEXTDOMAIN ) .'"';
	}

	?>

	<div class="tie-panel-submit">
		<button name="save" class="<?php echo join( ' ', $class ) ?>" type="submit" <?php echo $message; ?>><?php esc_html_e( 'Save Changes', TIELABS_TEXTDOMAIN ) ?></button>
	</div>
	<?php
}



/**
 * Add Extra Mimes Types
 */
add_filter( 'upload_mimes', 'tie_fonts_mimes_types', 1, 1 );
function tie_fonts_mimes_types( $mime_types ) {

	// Allow this for Administrators and in the backend only.
	if( ! is_admin() || ! current_user_can( 'manage_options' ) ){
		return $mime_types;
	}

	$mime_types['html']  = 'text/html';
	$mime_types['svg']   = 'image/svg+xml';
	$mime_types['eot']   = 'application/vnd.ms-fontobject';
	$mime_types['ttf']   = 'application/x-font-ttf';
	$mime_types['woff']  = 'application/x-font-woff';
	$mime_types['woff2'] = 'application/x-font-woff2';

	return $mime_types;
}


/**
 * In certain scenarios, ModSecurity will interfere with and disrupt the theme.
 * The reason this happens is that some ModSecurity configurations will flag the theme’s requests as a potential threat to the security of the system and will throw a 403 Forbidden or 500 Internal Server error.
 * 
 * We use this method to disable the AJAX saving method
 */
function tie_theme_wordpress_extra_settings_section_desc(){
	echo '<p style="font-size: 14px; max-width: 800px">';
	printf( esc_html__( 'If the saving process has been blocked by your server. %1scheck this article%2s to learn how to fix it.', TIELABS_TEXTDOMAIN ), '<a href="https://tielabs.com/go/jannah-save-error" target="_blank">', '</a>' );
	echo '<br /><strong>'. esc_html__( 'If the issue persists, enable the option below to use an alternative method for saving the settings.', TIELABS_TEXTDOMAIN ) .'</strong>';
	echo '</p>';
}

function tie_theme_wordpress_extra_settings_callback(){
	$checked = get_option( 'tie-save-normal-method-'.TIELABS_THEME_SLUG ) ? checked(1,1, false) : '';
	echo '<input name="'. esc_attr( 'tie-save-normal-method-'.TIELABS_THEME_SLUG ) .'" type="checkbox" '. $checked .' />';
}

add_action( 'admin_init', 'tie_theme_wordpress_extra_settings', 100 );
function tie_theme_wordpress_extra_settings(){

	register_setting( 'general', 'tie-save-normal-method-'.TIELABS_THEME_SLUG );

	add_settings_section(
		'tie_theme_wordpress_extra_settings_section',
		sprintf( esc_html__( '%s - Saving Method', TIELABS_TEXTDOMAIN ), apply_filters( 'TieLabs/theme_name', 'TieLabs' ) ),
		'tie_theme_wordpress_extra_settings_section_desc',
		'general',
		array(
			'before_section' => '<hr id="settings-'. TIELABS_TEXTDOMAIN .'" />',
			'after_section' => '<hr />'
		)
	);

	add_settings_field(
		'tie-save-normal-method-'.TIELABS_THEME_SLUG,
		esc_html__( 'Use the alternative saving method', TIELABS_TEXTDOMAIN ),
		'tie_theme_wordpress_extra_settings_callback',
		'general',
		'tie_theme_wordpress_extra_settings_section'
	);

}


/**
 * The Panel UI
 */
function tie_show_theme_options(){

	wp_enqueue_media();

	$settings_tabs = apply_filters( 'TieLabs/options_tab_title', '' );

	// Make connection to revoke the theme
	if( isset( $_REQUEST['revoke-theme'] ) && check_admin_referer( 'revoke-theme', 'revoke_theme_nonce' ) ){
		tie_get_latest_theme_data( '', false, false, false, true );
	}
	
	// Make connection to refresh support
	elseif( isset( $_REQUEST['refresh-support'] ) && check_admin_referer( 'refresh-support', 'refresh_support_nonce' ) ){
		tie_get_latest_theme_data( '', false, true );
		delete_transient( 'tie_theme_dot_net_'. TIELABS_THEME_ID );
	}
	
	/**
	 * In certain scenarios, ModSecurity will interfere with and disrupt the theme.
	 * The reason this happens is that some ModSecurity configurations will flag the theme’s requests as a potential threat to the security of the system and will throw a 403 Forbidden or 500 Internal Server error.
	 * 
	 * We use this method to disable the AJAX saving method
	 */
	if( get_option( 'tie-save-normal-method-'.TIELABS_THEME_SLUG ) && isset( $_REQUEST['action'] ) && 'tie_theme_data_save' == $_REQUEST['action'] && isset( $_REQUEST['tie_options'] ) && isset( $_REQUEST['tie-security'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['tie-security'] ), 'tie-theme-data' ) ){
		tie_save_theme_options( wp_unslash( $_REQUEST ) );

		echo '
			<div class="tie-message-hint tie-message-success"> 
				<p><span class="dashicons dashicons-yes"></span>' . esc_html__( 'Your changes have been saved.', TIELABS_TEXTDOMAIN ) . '</p>
			</div>
		';
	}

	?>

	<div id="tie-page-overlay"></div>

	<div id="tie-saving-settings">
		<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
			<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
			<path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
			<path class="checkmark__error_1" d="M38 38 L16 16 Z"/>
			<path class="checkmark__error_2" d="M16 38 38 16 Z" />
		</svg>

		<?php if( ! get_option( 'tie-save-normal-method-'.TIELABS_THEME_SLUG ) ){ ?>
			<div id="tie-modsecurity-error" class="tie-error-message" style="display:none">
				<?php 
					printf( esc_html__( 'It seems the saving process has been blocked by your server. %1scheck this article%2s to learn how to fix it.', TIELABS_TEXTDOMAIN ), '<a href="https://tielabs.com/go/jannah-save-error" target="_blank">', '</a>' );
					echo '<br />';
					printf( esc_html__( 'OR enable the %1sUse the alternative saving method%2s option in the WordPress General Settings to use an alternative method for saving the settings.', TIELABS_TEXTDOMAIN ), '<strong><a href="'. get_admin_url( null, 'options-general.php#settings-'.TIELABS_TEXTDOMAIN ) .'">', '</a></strong>' );
				?>
			</div>
		<?php } ?>

	</div>

	<?php do_action( 'TieLabs/before_theme_panel' );?>

	<?php 
		if( get_option( 'tie-save-normal-method-'.TIELABS_THEME_SLUG ) ){
			echo '<form method="post">';
		}
		else{
			echo '<form method="post" name="tie_form" id="tie_form" enctype="multipart/form-data">';
		}
	?>

		<div class="tie-panel">

			<div class="tie-panel-tabs">

				<?php

					if( tie_get_option( 'white_label_options_logo' ) ){
						echo '
							<span class="tie-logo">
								<img loading="lazy" class="tie-logo-normal" src="'. tie_get_option( 'white_label_options_logo' ) .'" alt="" />
							</span>
						';
					}
					else{ ?>
						<a href="http://tielabs.com/" target="_blank" class="tie-logo">
							<img loading="lazy" class="tie-logo-normal" src="<?php echo TIELABS_TEMPLATE_URL .'/framework/admin/assets/images/tielabs-logo.png' ?>" alt="<?php esc_html_e( 'TieLabs', TIELABS_TEXTDOMAIN ) ?>" />
							<img loading="lazy" class="tie-logo-mini" src="<?php echo TIELABS_TEMPLATE_URL .'/framework/admin/assets/images/tielabs-logo-mini.png' ?>" alt="<?php esc_html_e( 'TieLabs', TIELABS_TEXTDOMAIN ) ?>" />
						</a>
						<?php
					}
				?>

				<ul>
					<?php
					foreach( $settings_tabs as $tab => $settings ){

						if( ! empty( $settings['title'] ) ){
							$icon  = $settings['icon'];
							$title = $settings['title'];

							echo "
								<li class=\"tie-tabs tie-options-tab-$tab\">
									<a href=\"#tie-options-tab-$tab\">
										<span class=\"dashicons-before dashicons-$icon tie-icon-menu\"></span>
										$title
									</a>
								</li>
							";
						}
						else{
							echo "<li class=\"tie-tab-menu-head\">$settings</li>";
						}
					}
					?>

					<li class="tie-tab-menu-head"></li>
					<?php
					if( ! tie_is_theme_rated() ){
						?>
							<li class="tie-tabs tie-rate tie-not-tab"><a target="_blank" href="<?php echo tie_get_purchase_link(); ?>"><span class="dashicons-before dashicons-star-filled tie-icon-menu"></span><?php printf( esc_html__( 'Rate %s', TIELABS_TEXTDOMAIN ), apply_filters( 'TieLabs/theme_name', 'TieLabs' ) ); ?></a></li>
						<?php
					}
					?>
					<li class="tie-tabs tie-more tie-not-tab"><a target="_blank" href="<?php echo apply_filters( 'TieLabs/External/portfolio', '' ); ?>"><span class="dashicons-before dashicons-smiley tie-icon-menu"></span><?php esc_html_e( 'More Themes', TIELABS_TEXTDOMAIN ) ?></a></li>
				</ul>
				<div class="clear"></div>

			</div> <!-- .tie-panel-tabs -->

			<div class="tie-panel-content">

				<div class="tie-tab-head">
					<div id="theme-options-search-wrap">
						<input id="theme-panel-search" type="text" placeholder="<?php esc_html_e( 'Search', TIELABS_TEXTDOMAIN ) ?>">
						<div id="theme-search-list-wrap" class="has-custom-scroll">
							<ul id="theme-search-list"></ul>
						</div>
					</div>

					<div class="tiepanel-head-elements">

						<?php do_action( 'TieLabs/save_button' ); ?>
					
						<ul>
							<li id="tiepanel-head-elements-help">
								<a href="<?php echo apply_filters( 'TieLabs/External/knowledge_base', '' ); ?>" target="_blank" title="<?php esc_html_e( 'Knowledge Base', TIELABS_TEXTDOMAIN ); ?>"><svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><rect height="416" rx="48" ry="48" style="fill:none;stroke:#000;stroke-linejoin:round;stroke-width:32px" width="320" x="96" y="48"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px" x1="176" x2="336" y1="128" y2="128"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px" x1="176" x2="336" y1="208" y2="208"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px" x1="176" x2="256" y1="288" y2="288"/></svg></a>
							</li>

							<li>
								<div id="tiepanel-darkskin-wrap">
									<span class="darkskin-label"><svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="256" x2="256" y1="48" y2="96"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="256" x2="256" y1="416" y2="464"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="403.08" x2="369.14" y1="108.92" y2="142.86"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="142.86" x2="108.92" y1="369.14" y2="403.08"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="464" x2="416" y1="256" y2="256"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="96" x2="48" y1="256" y2="256"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="403.08" x2="369.14" y1="403.08" y2="369.14"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="142.86" x2="108.92" y1="142.86" y2="108.92"/><circle cx="256" cy="256" r="80" style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px"/></svg></span>
									<input id="tiepanel-darkskin" class="tie-js-switch" type="checkbox" value="true">
									<span class="darkskin-label"><svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><path d="M160,136c0-30.62,4.51-61.61,16-88C99.57,81.27,48,159.32,48,248c0,119.29,96.71,216,216,216,88.68,0,166.73-51.57,200-128-26.39,11.49-57.38,16-88,16C256.71,352,160,255.29,160,136Z" style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"/></svg></span>
									<script>
										if( 'undefined' != typeof localStorage ){
											var skin = localStorage.getItem('tie-backend-skin');
											if( skin == 'dark' ){
												document.getElementById('tiepanel-darkskin').setAttribute('checked', 'checked');
											}
										}
									</script>
								</div>
							</li>

						</ul>
					</div><!-- .tiepanel-head-elements -->

				</div>
				
				<?php
					foreach( $settings_tabs as $tab => $settings ){

						if( ! empty( $settings['title'] ) ){
							echo "
							<!-- $tab Settings -->
							<div id=\"tie-options-tab-$tab\" class=\"tabs-wrap\">";

							TIELABS_HELPER::get_template_part( 'framework/admin/theme-options/'.$tab );

							do_action( 'tie_theme_options_tab_'.$tab );

							echo "</div>";
						}
					}
				?>

				<?php wp_nonce_field( 'tie-theme-data', 'tie-security' ); ?>
				<input type="hidden" name="action" value="tie_theme_data_save" />

				<div class="tie-footer">
					<?php //TIELABS_VERIFICATION::support_compact_notice(); ?>

					<?php do_action( 'TieLabs/save_button' ); ?>
				</div>

			</div><!-- .tie-panel-content -->
			<div class="clear"></div>

		</div><!-- .tie-panel -->

	</form>

	<?php if( ! empty( $footer_notice = tie_get_latest_from_dot_net( 'footer-notice' ) ) ){ ?>
	<div class="tie-footer-notice">
		<?php echo $footer_notice; ?>
	</div>
	<?php } ?>
	
	<?php

	// HelpSout Beacon
	if( tie_get_token() && ! tie_get_option( 'white_label_beacon' ) ){ ?>
		<script type="text/javascript">!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});</script>
		<script type="text/javascript">
			window.Beacon('init', 'e9254113-3842-4968-97fa-13dee4551b96');
			window.Beacon('config', {
				display: {
					style: 'iconAndText',
					iconImage: 'help',
					text: '<?php esc_html_e( 'Need Help?', TIELABS_TEXTDOMAIN ) ?>',
					textAlign: '<?php echo is_rtl() ? 'left'  : 'right'; ?>',
					position:  '<?php echo is_rtl() ? 'right' : 'left'; ?>',
					zIndex: 9991,
				},
				labels: {
					suggestedForYou: '<?php esc_html_e( 'Knowledge Base', TIELABS_TEXTDOMAIN ) ?>',
					searchLabel: '<?php esc_html_e( 'Need Help? Search the knowledge base', TIELABS_TEXTDOMAIN ) ?>',
				}
			})
		</script>
		<?php
	}

}


/**
 * Share buttons
 */
function tie_get_share_buttons_options( $share_position = '' ){

	$position = ! empty( $share_position ) ? '_'.$share_position : '';

	tie_build_theme_option(
		array(
			'name'   => esc_html__( 'X', TIELABS_TEXTDOMAIN ),
			'id'     => 'share_twitter'.$position,
			'type'   => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Facebook', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_facebook'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'LinkedIn', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_linkedin'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Pinterest', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_pinterest'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Reddit', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_reddit'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Tumblr', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_tumblr'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'VKontakte', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_vk'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Odnoklassniki', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_odnoklassniki'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Pocket', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_pocket'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Flipboard', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_flipboard'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Skype', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_skype'.$position,
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Messenger', TIELABS_TEXTDOMAIN ),
			'id'   => 'share_messenger'.$position,
			'type' => 'checkbox',
		));

	if( $share_position != 'sticky' && $share_position != 'sticky_menu' ){
		tie_build_theme_option(
			array(
				'name' => esc_html__( 'WhatsApp', TIELABS_TEXTDOMAIN ),
				'id'   => 'share_whatsapp'.$position,
				'type' => 'checkbox',
				'hint' => ( $share_position != 'mobile' ) ? esc_html__( 'For Mobiles Only', TIELABS_TEXTDOMAIN ) : '',
			));

		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Telegram', TIELABS_TEXTDOMAIN ),
				'id'   => 'share_telegram'.$position,
				'type' => 'checkbox',
				'hint' => ( $share_position != 'mobile' ) ? esc_html__( 'For Mobiles Only', TIELABS_TEXTDOMAIN ) : '',
			));

		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Viber', TIELABS_TEXTDOMAIN ),
				'id'   => 'share_viber'.$position,
				'type' => 'checkbox',
				'hint' => ( $share_position != 'mobile' ) ? esc_html__( 'For Mobiles Only', TIELABS_TEXTDOMAIN ) : '',
			));

		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Line', TIELABS_TEXTDOMAIN ),
				'id'   => 'share_line'.$position,
				'type' => 'checkbox',
				'hint' => ( $share_position != 'mobile' ) ? esc_html__( 'For Mobiles Only', TIELABS_TEXTDOMAIN ) : '',
			));
	}

	if( $share_position != 'mobile' ){
		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Email', TIELABS_TEXTDOMAIN ),
				'id'   => 'share_email'.$position,
				'type' => 'checkbox',
			));

		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Print', TIELABS_TEXTDOMAIN ),
				'id'   => 'share_print'.$position,
				'type' => 'checkbox',
			));
	}

}


