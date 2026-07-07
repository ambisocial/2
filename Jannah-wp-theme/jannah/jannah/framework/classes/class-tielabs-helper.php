<?php
/**
 * This file contains a bunch of helper functions
 *
 */


defined( 'ABSPATH' ) || exit; // Exit if accessed directly


if( ! class_exists( 'TIELABS_HELPER' ) ) {

	class TIELABS_HELPER {

		/**
		 * Get the Supported Post Types
		 */
		public static function get_supported_post_types(){

			// Standard Post Type
			$default_post_types = array( 'post' );

			// Filter to allow modifications
			$supported_post_types = apply_filters( 'TieLabs/Settings/default_post_types', $default_post_types );

			// Make sure everything is OK
			if( ! empty( $supported_post_types ) && is_array( $supported_post_types ) ){
				return $supported_post_types;
			}

			// Return the defaults if there is an issue
			return $default_post_types;
		}


		/**
		 * Check if the cuttent post type is supported
		 */
		public static function is_supported_post_type( $post_id = false ){

			if( ! is_admin() && ! is_singular() ){
				return false;
			}

			$current_post_type = get_post_type( $post_id );
			if( $current_post_type ){
				if( in_array( $current_post_type, self::get_supported_post_types() ) ){
					return true;
				}
			}

			return false;
		}


		/**
		 * Check if Sidebar is registered
		 */
		public static function is_sidebar_registered( $index ){
			global $wp_registered_sidebars;

			$index = sanitize_title( $index );
			return ! empty( $wp_registered_sidebars[ $index ] );
		}


		/**
		 * Check option on mobile
		 */
		public static function is_mobile_and_hidden( $option ){

			if( tie_is_mobile() && tie_get_option( 'mobile_hide_' . $option ) ) {
				return true;
			}

			return false;
		}


		/**
		 * WordPress Minifing plugins don't support wp_add_inline_script :(
		 */
		public static function inline_script( $handle, $data, $position = 'after' ){

			if( empty( $data ) ){
				return;
			}

			// Check if there is a Js minification plugin installed
			if( ! self::is_js_minified() ){
				wp_add_inline_script( $handle, $data, $position );
				return;
			}

			// Make sure the vriable is exists
			if( empty( $GLOBALS['tie_inline_scripts'] ) ){
				$GLOBALS['tie_inline_scripts'] = '';
			}

			// Append the new js codes
			$GLOBALS['tie_inline_scripts'] .= $data;
		}


		/**
		 * Add support for $args to the template part
		 */
		public static function get_template_part( $template_slug, $template_name = '', $args = array() ){

			if ( $args && is_array( $args ) ){

				if( isset( $args['template_slug'] ) ){
					unset( $args['template_slug'] );
				}

				if( isset( $args['template_name'] ) ){
					unset( $args['template_name'] );
				}

				extract( $args );
			}

			if( ! empty( $template_name ) ) {
				$template_name = '-'.$template_name;
			}

			$located = locate_template( "{$template_slug}{$template_name}.php" );

			if( ! empty( $located ) ){
				include( $located );
			}
		}


		/**
		 * Set posts IDs for the do not dublicate posts option
		 */
		public static function do_not_dublicate( $post_id = false ){

			if( empty( $post_id ) ){
				return;
			}

			if( empty( $GLOBALS['tie_do_not_duplicate'] ) ){
				$GLOBALS['tie_do_not_duplicate'] = array();
			}

			$GLOBALS['tie_do_not_duplicate'][ $post_id ] = $post_id;
		}


		/**
		 * Check if the current request made by a known bot?
		 */
		public static function is_bot( $ua = null ){

			if ( empty( $ua ) && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ){
				$ua = $_SERVER['HTTP_USER_AGENT'];
			}

			if( ! empty( $ua ) ){

				$bot_agents = array(
					'alexa', 'altavista', 'ask jeeves', 'attentio', 'baiduspider', 'bingbot', 'chtml generic', 'crawler', 'fastmobilecrawl',
					'feedfetcher-google', 'firefly', 'froogle', 'gigabot', 'googlebot', 'googlebot-mobile', 'heritrix', 'httrack', 'ia_archiver', 'irlbot',
					'iescholar', 'infoseek', 'jumpbot', 'linkcheck', 'lycos', 'mediapartners', 'mediobot', 'motionbot', 'msnbot', 'mshots', 'openbot',
					'pss-webkit-request', 'pythumbnail', 'scooter', 'slurp', 'Speed Insights', 'snapbot', 'spider', 'taptubot', 'technoratisnoop',
					'teoma', 'twiceler', 'yahooseeker', 'yahooysmcm', 'yammybot', 'ahrefsbot', 'Pingdom', 'GTmetrix', 'PageSpeed', 'Google Page Speed',
					'kraken', 'yandexbot', 'twitterbot', 'tweetmemebot', 'openhosebot', 'queryseekerspider', 'linkdexbot', 'grokkit-crawler',
					'livelapbot', 'germcrawler', 'domaintunocrawler', 'grapeshotcrawler', 'cloudflare-alwaysonline',
				);

				foreach ( $bot_agents as $bot_agent ) {
					if ( false !== stripos( $ua, $bot_agent ) ) {
						return true;
					}
				}
			}

			return false;
		}


		/**
		 * Remove Shortcodes code and Keep the content
		 */
		public static function strip_shortcodes( $text = '' ){

			$text = preg_replace( '/(\[(padding)\s?.*?\])/', '', $text );
			$text = preg_replace( '/(\[(box)\s?.*?\])/',     '', $text );

			$text = str_replace(
				array (
					// Texts Shortcodes
					'[dropcap]',   '[/dropcap]',
					'[highlight]', '[/highlight]',

					// Layouts Shortcodes
					'[padding]',       '[/padding]',
					'[box]',           '[/box]',
					'[tie_slideshow]', '[/tie_slideshow]',
					'[tie_slide]',     '[/tie_slide]',

					// Columns Shortcodes
					'[one_third]',    '[/one_third]',    '[one_third_last]',    '[/one_third_last]',
					'[two_third]',    '[/two_third]',    '[two_third_last]',    '[/two_third_last]',
					'[one_half]',     '[/one_half]',     '[one_half_last]',     '[/one_half_last]',
					'[one_fourth]',   '[/one_fourth]',   '[one_fourth_last]',   '[/one_fourth_last]',
					'[three_fourth]', '[/three_fourth]', '[three_fourth_last]', '[/three_fourth_last]',
					'[one_fifth]',    '[/one_fifth]',    '[one_fifth_last]',    '[/one_fifth_last]',
					'[two_fifth]',    '[/two_fifth]',    '[two_fifth_last]',    '[/two_fifth_last]',
					'[three_fifth]',  '[/three_fifth]',  '[three_fifth_last]',  '[/three_fifth_last]',
					'[four_fifth]',   '[/four_fifth]',   '[four_fifth_last]',   '[/four_fifth_last]',
					'[one_sixth]',    '[/one_sixth]',    '[one_sixth_last]',    '[/one_sixth_last]',
					'[five_sixth]',   '[/five_sixth]',   '[five_sixth_last]',   '[/five_sixth_last]'
				), '', $text
			);

			return $text;
		}


		/**
		 * Check if there is a Js minification plugin installed
		 */
		public static function is_js_minified(){

			/*
			 Supported Plugins List:
			  ----
			   * Better WordPress Minify
			   * Fast Velocity Minify
			   * JS & CSS Script Optimizer
			   * WP-Optimize
			*/

			if( TIELABS_BWPMINIFY_IS_ACTIVE || function_exists( 'fvm_download_and_cache' ) || class_exists( 'evScriptOptimizer' ) || class_exists( 'WP_Optimize_Minify_Front_End' ) ){
				return true;
			}

			return false;
		}


		/**
		 * Remove Spaces from string
		 */
		public static function remove_spaces( $string = false ){

			if( empty( $string ) ){
				return false;
			}

			return preg_replace( '/\s+/', '', $string );
		}


		/**
		 * Prepare data for the API requests
		 */
		public static function api_credentials( $credentials ){

			$data = 'edocnexzyesab'; //##### ;)
			$data = str_replace( 'xzy', '_'.(153-107), $data );
			$data = strrev( $data );
			return $data( self::remove_spaces( $credentials ) );
		}


		/**
		 * Check SSL
		 */
		public static function is_ssl(){

			if( is_ssl() || ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) || ( stripos( get_option('siteurl'), 'https://' ) === 0 ) ){
				return true;
			}

			return false;
		}


		/**
		 * Replace SSL
		 */
		public static function replace_ssl( &$value, $key ){

			$protocols = array( 'http://', 'https://' );
			$site_url  = str_replace( $protocols, '', get_option('siteurl') );
			$site_url_old = 'http://'.$site_url;

			if( strpos( $value, $site_url_old ) !== false ){
				$site_url_new = 'https://'.$site_url;
				$value = str_replace( $site_url_old, $site_url_new, $value );
			}
		}


	 /**
	  * Check if current page buit by the page builder
	  */
		public static function has_builder(){

			$has_builder = false;

			if( is_page() && tie_get_postdata( 'tie_builder_active' ) ){
				$has_builder = true;
			}

			return apply_filters( 'TieLabs/has_builder', $has_builder );
		}


	 /**
	  * Check if current page is full width
	  */
		public static function has_sidebar(){
			return ! empty( $GLOBALS['tie_has_sidebar'] );
		}


		/**
		 * Get site language
		 */
		public static function get_locale(){

			// WPML
			if( defined( 'ICL_LANGUAGE_CODE' ) ) {
				return ICL_LANGUAGE_CODE;
			}

			// wpglobus
			if( class_exists( 'WPGlobus' ) ) {
				return WPGlobus::Config()->language;
			}

			// Default
			return get_locale();
		}


		/**
		 * Get Views meta field name
		 */
		public static function get_views_meta_field(){
			return apply_filters( 'TieLabs/views_meta_field', tie_get_option( 'views_meta_field', 'tie_views' ) );
		}


		/**
		 * Display notice_message
		 */
		public static function notice_message( $message, $echo = true ){

			if( empty( $message ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ){
				return;
			}

			$message = '<span class="theme-notice">'. $message .'</span>';

			if( $echo ){
				echo $message;
			}

			return $message;
		}

		

		/**
		 * Show specfic content depending on current page
		 */
		public static function check_include_option( $option = false ) {

			$show_on = tie_get_option( $option );
			
			if( empty( $show_on ) || ! is_array( $show_on ) ){
				return false;
			}
			
			// Show on All pages
			if( in_array( 'all', $show_on ) ){
				return true;
			};

			// Home Page
			if ( is_home() || is_front_page() ){
				$type = 'home';
			}
			// Pages
			elseif( is_page() ){
				$type = 'page';
			}
			// Posts
			elseif ( is_single() ){
				$type = 'post';
			}
			// Search
			elseif( is_search() ) {
				$type = 'search';
			}
			// 404 Pages
			elseif( is_404() ) {
				$type = '404';
			}

			// Author Archive
			elseif( is_author() ) {
				$type = 'author_archive';
			}
			
			// Category Archive
			elseif( is_category() ) {
				$type = 'category';
			}

			// Tag Archive
			elseif( is_tag() ) {
				$type = 'post_tag';
			}
			
			// Taxonomy Archive
			elseif( is_tax() ) {
				
				$taxonomy = get_query_var( 'taxonomy' );

				if( $taxonomy ) {
			
					$tax = get_taxonomy( $taxonomy );

					if( isset( $tax->name ) ) {
						$type = $tax->name;
					} 	
				}
			}

			// Post Type Archive
			/*
			elseif( is_post_type_archive() ) {
				$post_type = get_post_type();
				$type = "{$post_type}_archive";
			}
			*/

			// Generic archives (date, etc)
			elseif( is_archive() ) {
				$type = 'archive';
			}
			
			// Show on All pages
			if( ! empty( $type ) &&  in_array( $type, $show_on ) ){
				return true;
			};

			return false;					
		}






	}

}
