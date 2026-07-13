<?php
/**
 * BuddyPress Class
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly



if( ! class_exists( 'TIELABS_BUDDYPRESS' ) ) {

	class TIELABS_BUDDYPRESS{

		/**
		 * __construct
		 *
		 * Class constructor where we will call our filter and action hooks.
		 */
		function __construct(){

			// Disable if the BuddyPress plugin is not active
			if( ! TIELABS_BUDDYPRESS_IS_ACTIVE ){
				return;
			}

			// Wrapper Start
			add_action( 'bp_before_group_body',                    array( $this, 'before_content' ) );
			add_action( 'bp_before_member_body',                   array( $this, 'before_content' ) );
			add_action( 'bp_before_register_page',                 array( $this, 'before_content' ) );
			add_action( 'bp_before_activation_page',               array( $this, 'before_content' ) );
			add_action( 'bp_before_directory_blogs',               array( $this, 'before_content' ) );
			add_action( 'bp_before_directory_groups',              array( $this, 'before_content' ) );
			add_action( 'bp_before_directory_members',             array( $this, 'before_content' ) );
			add_action( 'bp_before_directory_activity_content',    array( $this, 'before_content' ) );
			add_action( 'bp_before_create_group_content_template', array( $this, 'before_content' ) );

			// Wrapper End
			add_action( 'bp_after_group_body',                     array( $this, 'after_content' ) );
			add_action( 'bp_after_member_body',                    array( $this, 'after_content' ) );
			add_action( 'bp_after_register_page',                  array( $this, 'after_content' ) );
			add_action( 'bp_after_activation_page',                array( $this, 'after_content' ) );
			add_action( 'bp_after_directory_blogs',                array( $this, 'after_content' ) );
			add_action( 'bp_after_directory_groups',               array( $this, 'after_content' ) );
			add_action( 'bp_after_directory_members',              array( $this, 'after_content' ) );
			add_action( 'bp_after_directory_activity_content',     array( $this, 'after_content' ) );
			add_action( 'bp_after_create_group_content_template',  array( $this, 'after_content' ) );

			// Enqueue and Dequeue CSS files
			add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_resources' ), 5 );

			//
			add_action( 'bp_nouveau_enqueue_styles', array( $this, 'remove_default_buddypress_dependency' ), 20 );

			// Covers args
			add_filter( 'bp_before_members_cover_image_settings_parse_args', array( $this, 'cover_image_css' ), 1 );
			add_filter( 'bp_before_groups_cover_image_settings_parse_args',  array( $this, 'cover_image_css' ), 1 );

			// Notifications Menu Content
			add_filter( 'TieLabs/BuddyPress/notifications', array( $this, 'get_notifications' ) );

			// Js Vars
			add_filter( 'TieLabs/js_main_vars', array( $this, 'js_var' ) );

			// BuddyPress Nouveau Templates
			add_theme_support('buddypress-use-nouveau');
		}


		/**
		 * BuddyPress Pages HTML markup | before content
		 */
		function before_content(){

			tie_html_before_main_content();

			echo '<div ' .tie_content_column_attr( false ). '>';
			echo '<div class="container-wrapper">';
		}


		/**
		 * BuddyPress Pages HTML markup | after content
		 */
		function after_content(){

			echo '<div class="clearfix"></div>';
			echo '</div><!-- .container-wrapper /-->';
			echo '</div><!-- .main-content  /-->';

			get_sidebar();
			tie_html_after_main_content();
		}


		/**
		 * Dequeue buddyPress Default Css files
		 */
		function dequeue_styles(){

			wp_dequeue_style( 'bp-nouveau' );
			wp_dequeue_style( 'bp-nouveau-priority-nav' );
		}


		/**
		 * remove_default_buddypress_dependency
		 */
		function remove_default_buddypress_dependency( $styles ){

			foreach ( $styles as $file => $attr ) {

				$key = array_search( 'bp-nouveau', $attr['dependencies'], false );

				if( isset( $key ) ){
					$styles[$file]['dependencies'][$key] = 'tie-css-buddypress';
				}
			}

			return $styles;
		}


		/**
		 * Enqueue JS and CSS files
		 */
		function enqueue_resources(){

			// Enqueue buddyPress Custom Css file
			wp_enqueue_style( 'tie-css-buddypress', TIELABS_TEMPLATE_URL.'/assets/css/plugins/buddypress'. TIELABS_STYLES::is_minified() .'.css', array('dashicons'), TIELABS_DB_VERSION, 'all' );

			// For Grid Archives
			if( ! is_buddypress() ){
				return;
			}

			wp_enqueue_script( 'jquery-masonry' );

			$masonry_js = "
				jQuery(document).ready(function(){

					jQuery( '#buddypress' ).on( 'bp_ajax_request', '.dir-list', function(){

						if( jQuery.fn.masonry ){

							var grid = jQuery('.bp-list.grid');

							if( grid.length ){

								grid.masonry({
									percentPosition : true,
									isInitLayout    : false, // v3
									initLayout      : false, // v4
									originLeft      : ! is_RTL,
									isOriginLeft    : ! is_RTL
								});

								setTimeout(function(){
									grid.masonry('layout');
								}, 1);

								if( jQuery.fn.imagesLoaded ){
									grid.imagesLoaded().progress( function(){
										grid.masonry('layout');
									});
								}
							}
						}
					});
				});
			";

			TIELABS_HELPER::inline_script( 'jquery-masonry', $masonry_js );
		}


		/**
		 * Notifications Menu Content
		 */
		function get_notifications(){

			if( ! function_exists( 'bp_notifications_get_notifications_for_user' ) ){
				return false;
			}

			$notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
			$count = ( ! empty( $notifications ) && is_array( $notifications ) ) ? count( $notifications ) : 0;
			$count = (int) $count > 0 ? number_format_i18n( $count ) : '';

			$menu_link = '#';
			if( function_exists( 'bp_get_notifications_permalink' ) ){
				$menu_link = bp_get_notifications_permalink();
			}
			
			$out_data = '<ul class="bp-notifications">';

			if ( ! empty( $notifications ) ){
				foreach ( (array) $notifications as $notification ){
					$out_data .= '<li id="'. $notification->id .'" class="notifications-item"><a href="'. $notification->href .'"><span class="tie-icon-bell"></span> '. $notification->content .'</a></li>';
				}
			}
			else {
				$out_data .= '<li id="no-notifications" class="notifications-item"><a href="'. $menu_link .'"><span class="tie-icon-bell"></span>  '. esc_html__( 'No new notifications', TIELABS_TEXTDOMAIN ) .'</a></li>';
			}

			$out_data .= '</ul>';

			return array(
				'data'  => $out_data,
				'count' => $count,
				'link'  => $menu_link,
			);
		}


		/**
		 * BuddyPress Cover Image
		 */
		function cover_image_css( $settings = array() ){

			$settings['callback']      = array( $this, 'cover_image_callback' );
			$settings['theme_handle']  = 'tie-css-buddypress';
			$settings['width']         = 1400;
			$settings['height']        = 440;
			$settings['default_cover'] = TIELABS_TEMPLATE_URL. '/assets/images/default-cover-image.jpg';

			return $settings;
		}


		/**
		 * Cover Image CSS
		 */
		function cover_image_callback( $params = array() ){

			if ( empty( $params ) ){
				return;
			}

			$background_attr = '';

			if( $params['cover_image'] == TIELABS_TEMPLATE_URL. '/assets/images/default-cover-image.jpg' ){
				$background_attr = '
		    	background-repeat: repeat !important;
		    	background-size: 400px !important;
		    ';
			}

			return '
				#buddypress #header-cover-image {
					background-image: url(' . $params['cover_image'] . ');
					'. $background_attr .'
				}
			';
		}


		/**
		 * Get BuddyPress Custom Option
		 */
		public static function get_page_data( $option, $default = false ){

			// Members
			if( bp_is_user() || bp_is_current_component( 'members' ) ){
				return tie_get_option( 'bp_members_' . $option );
			}

			// Groups
			if( bp_is_current_component( 'groups' ) ){
				return tie_get_option( 'bp_groups_' . $option );
			}

			// Activity
			if( bp_is_current_component( 'activity' ) ){
				return tie_get_option( 'bp_activity_' . $option );
			}

			// Registration
			if( bp_is_current_component( 'register' ) ){
				return tie_get_option( 'bp_register_' . $option );
			}

			// Default
			if( $default ){
				return $default;
			}

			return false;
		}


		/**
		 * Add is_buddypress to main tie js var
		 */
		public static function js_var( $array ){

			$array['is_buddypress_active'] = true;

			return $array;
		}

	}

	// Instantiate the class
	new TIELABS_BUDDYPRESS();
}
