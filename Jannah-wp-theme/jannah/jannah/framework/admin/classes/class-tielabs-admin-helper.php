<?php
/**
 * This file contains a bunch of helper functions used in the admin
 *
 */


defined( 'ABSPATH' ) || exit; // Exit if accessed directly


if( ! class_exists( 'TIELABS_ADMIN_HELPER' ) ) {

	class TIELABS_ADMIN_HELPER {


		/**
		 * Get List of the video Playlists
		 */
		public static function get_playlists( $label = false ){

			if ( function_exists( 'wmvp_get_playlists_list') ){
				return wmvp_get_playlists_list( true, true );
			}
			
			//The wmvp_get_playlists_list doesn't exist, Install the WMVP plugin.
			return '<div class="tie-message-hint">'. sprintf( esc_html__( 'You need to install the %s plugin to use this feature.', TIELABS_TEXTDOMAIN ), '<a href="'. admin_url('admin.php?page=tie-install-plugins') .'" target="_blank"><strong>WM Video Playlists</strong></a>' ) .'</div>';
		}


		/**
		 * Get List of custom sliders
		 */
		public static function get_sliders( $label = false ){

			$sliders = array();

			// Default Label
			if( ! empty( $label ) ){
				$sliders[] = esc_html__( '- Select a Slider -', TIELABS_TEXTDOMAIN );
			}

			// Query the custom sliders
			$args = array(
				'post_type'        => 'tie_slider',
				'post_status'      => 'publish',
				'posts_per_page'   => 500,
				'offset'           => 0,
				'no_found_rows'    => 1,
				'no_found_rows'    => true,
			);

			$sliders_list = get_posts( $args );

			// Add the custom sliders to the array
			if( ! empty( $sliders_list ) && is_array( $sliders_list ) ){
				foreach ( $sliders_list as $slide ){
					$sliders[ $slide->ID ] = $slide->post_title;
				}
			}

			return $sliders;
		}


		/**
		 * Get all categories as array of ID and name
		 */
		public static function get_categories( $label = false ){

			$categories = array();

			// Default Label
			if( ! empty( $label ) ){
				$categories[] = esc_html__( '- Select a Category -', TIELABS_TEXTDOMAIN );
			}

			$max_number = apply_filters( 'TieLabs/get_categories/max_number', 500 );

			$args = array(
				'hide_empty' => false,
				'number'     => $max_number
			);

			// Some websites have more than 5000 categories, which cause slowness
			if ( get_option( 'tie_huge_categories_list' ) ){
				$args['hide_empty'] = true;
				//$args['orderby']    = 'count';
			}

			// Query the categories
			$get_categories = get_categories( $args );

			// Add the categories to the array
			if( ! empty( $get_categories ) && is_array( $get_categories ) ){

				foreach ( $get_categories as $category ){
					$categories[ $category->cat_ID ] = $category->cat_name;
				}

				// Some websites have more than 5000 categories, which cause slowness
				if( count( $get_categories ) > $max_number && ! $args['hide_empty'] ){
					update_option( 'tie_huge_categories_list', count( $get_categories ), false );
				}

			}

			return $categories;
		}


		/**
		 * Get all taxonomies as array of slug and name
		 */
		public static function get_taxonomies( $label = false, $supported_only = false ){

			$taxonomies = array();

			// Default Label
			if( ! empty( $label ) ){
				$taxonomies[] = esc_html__( '- Select a Taxonomy -', TIELABS_TEXTDOMAIN );
			}

			// Query the taxonomies
			$get_taxonomies = get_taxonomies( array(
				//'public'   => true,
				'_builtin' => false
			), 'objects' );

			$exclude_list = self::excluded_taxonomies_list();


			// Add the categories to the array
			if( ! empty( $get_taxonomies ) && is_array( $get_taxonomies ) ){
				foreach ( $get_taxonomies as $slug => $taxonomy ){

					if( ! empty( $exclude_list ) && is_array( $exclude_list ) && in_array( $slug, $exclude_list ) ){
						continue;
					}

					if( $supported_only ){
						$custom_taxonomies  = tie_get_option( 'custom_taxonomies' );	

						if( ! empty( $custom_taxonomies ) && is_array( $custom_taxonomies ) && ! in_array( $slug, $custom_taxonomies ) ){
							continue;
						}
					}

					$taxonomies[ $slug ] = ! empty( $taxonomy->label ) ? $taxonomy->label : $taxonomy->labels->name;
				}
			}

			return $taxonomies;
		}


		/**
		 * Get all terms of specfic taxonomy as array of ID and name
		 */
		public static function get_terms_by_taxonomy( $taxonomy = false, $label = false ){

			$terms = array();

			// Default Label
			if( ! empty( $label ) ){
				$terms[] = esc_html__( '- Select a Term -', TIELABS_TEXTDOMAIN );
			}

			// Query the categories
			$get_terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			));

			// Add the terms to the array
			if( ! empty( $get_terms ) && is_array( $get_terms ) ){
				foreach ( $get_terms as $term ){
					$terms[ $term->term_id ] = $term->name;
				}
			}

			return $terms;
		}


		/**
		 * Get default orderby list
		 */
		public static function get_orderby_list(){

			$orderby = array(
				'latest'   => esc_html__( 'Recent Posts',         TIELABS_TEXTDOMAIN ),
				'rand'     => esc_html__( 'Random Posts',         TIELABS_TEXTDOMAIN ),
				'modified' => esc_html__( 'Last Modified Posts',  TIELABS_TEXTDOMAIN ),
				'popular'  => esc_html__( 'Most Commented posts', TIELABS_TEXTDOMAIN ),
				'title'    => esc_html__( 'Alphabetically',       TIELABS_TEXTDOMAIN ),
			);
		
			if( tie_get_option( 'tie_post_views' ) ){
				$orderby['views'] = esc_html__( 'Most Viewed posts', TIELABS_TEXTDOMAIN );

				if( tie_get_option( 'views_7_days' ) && tie_get_option( 'tie_post_views' ) == 'theme' ){
					$orderby['views_7_days'] = esc_html__( 'Most Viewed for 7 days', TIELABS_TEXTDOMAIN );
				}
			}

			return apply_filters( 'TieLabs/orderby_list', $orderby );
		}


		/**
		 * Get all Web Stories Categories as array of ID and name
		 */
		public static function get_web_stories_categories( $label = false ){

			if( ! TIELABS_WEBSTORIES_IS_ACTIVE ){
				return array();
			}

			$categories = array();

			// Default Label
			if( ! empty( $label ) ){
				$menus[] = esc_html__( '- Select a Category -', TIELABS_TEXTDOMAIN );
			}

			// Query the categories
			$get_category = get_terms( array( 'taxonomy' => 'web_story_category', 'hide_empty' => false ) );

			// Add the categories to the array
			if( ! empty( $get_category ) && is_array( $get_category ) ){
				foreach ( $get_category as $category ){
					$categories[ $category->term_id ] = $category->name;
				}
			}

			return $categories;
		}



		/**
		 * Get all menus as array of ID and name
		 */
		public static function get_menus( $label = false, $custom = false ){

			$menus = array();

			// Default Label
			if( ! empty( $label ) ){
				$menus[] = esc_html__( '- Select a Menu -', TIELABS_TEXTDOMAIN );
			}

			// Custom Menus
			if( ! empty( $custom ) && is_array( $custom ) ){
				$menus = array_merge( $menus, $custom );
			}

			// Query the menus
			$get_menus = get_terms( array( 'taxonomy' => 'nav_menu', 'hide_empty' => false ) );

			// Add the menus to the array
			if( ! empty( $get_menus ) && is_array( $get_menus ) ){
				foreach ( $get_menus as $menu ){
					$menus[ $menu->term_id ] = $menu->name;
				}
			}

			return $menus;
		}


		/**
		 * Get List of the Sidebars
		 */
		public static function get_sidebars(){

			global $wp_registered_sidebars;

			$sidebars      = array( '' => esc_html__( 'Default', TIELABS_TEXTDOMAIN ) );
			$sidebars_list = $wp_registered_sidebars;

			$custom_sidebars = tie_get_option( 'sidebars' );
			if( ! empty( $custom_sidebars ) && is_array( $custom_sidebars ) ) {
				foreach ( $custom_sidebars as $sidebar ){

					// Remove sanitized custom sidebars titles from the sidebars array.
					$sanitized_sidebar = sanitize_title( $sidebar );
					unset( $sidebars_list[ $sanitized_sidebar ] );

					// Add the Unsanitized custom sidebars titles to the array.
					$sidebars_list[ $sidebar ] = array( 'name' => $sidebar );
				}
			}

			if( ! empty( $sidebars_list ) && is_array( $sidebars_list ) ) {
				foreach( $sidebars_list as $name => $sidebar ){
					$sidebars[ $name ] = $sidebar['name'];
				}
			}

			return $sidebars;
		}


		/**
		 * Get all background Patterns
		 */
		public static function get_patterns(){

			$patterns = array();

			for( $i=1 ; $i<=47 ; $i++ ){
				$patterns['body-bg'.$i]	=	'patterns/'.$i.'.png';
			}

			return $patterns;
		}


		/**
		 * Remove Empty values from the Multi Dim Arrays
		 */
		public static function array_filter( $input ){

			foreach ( $input as &$value ){

				if( is_array( $value ) ){
					$value = self::array_filter( $value );
				}
			}

			return array_filter( $input );
		}


		/**
		 * Remove all settings with value -tie-101
		 */
		public static function clean_settings( $input ){

			return $input;
			/*
			if( is_array( $input ) ){
				foreach ( $input as &$value ){
					$value = self::clean_settings( $value );
				}
			}

			if ( $input == '-tie-101' ) {
				$input = '';
			}

			return $input;
			*/
		}


		/**
		 * Get list of all excluded taxonomies
		 */
		public static function excluded_taxonomies_list(){

			return apply_filters( 'TieLabs/exclude_taxonomies_list', array(
				'product_shipping_class',
				'topic-tag',
				'product_tag',
				'product_cat',
				'product_type',
				'product_visibility',
				'amp_validation_error',
			) );
		}


		/**
		 * Check if the current page is the theme options
		 */
		public static function is_theme_options_page(){

			$current_page = ! empty( $_REQUEST['page'] ) ? $_REQUEST['page'] : '';
			return $current_page == 'tie-theme-options';
		}


		/**
		 * Check if the current page uses Gutenberg
		 */
		public static function is_edit_gutenberg(){

			if( version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' ) ) {
				$current_screen = get_current_screen();
				if ( $current_screen && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Disable section if license is not active
		 */
		public static function license_disable_section(){

			if( ! empty( $_SERVER['SERVER_NAME'] ) && ( strpos( $_SERVER['SERVER_NAME'], 'tielabs.com' ) !== false || strpos( $_SERVER['SERVER_NAME'], 'localhost' ) !== false ) ){
				return;
			}
			
			if( tie_get_token() ){
				return;
			}

			?>
		
			<style>
				#tie-page-builder-button,
				#tie-page-builder{
					filter: blur(3px);	
				}
	
				.tie-license-disable-section{
					position: absolute;
					width: 100%;
					height: 100%;
					top: 0;
					bottom: 0;
					left: 0;
					right: 0;
					z-index: 2;
					background: rgba(0,0,0,.7);
					cursor: not-allowed;
					display: flex;
					justify-content: center;
					align-content: safe;
					align-items: center;
				}
	
				.tie-license-disable-section .tie-message-error{
					text-align: center;
					border-width: 2px;
					padding: 20px 40px;
					font-weight: bold;
					border-radius: 12px;
				}
			</style>
			<div class="tie-license-disable-section"><div class="tie-message-hint tie-message-error"><?php esc_html_e( 'Verify your license to unlock this section.', TIELABS_TEXTDOMAIN ) ?></div></div>
	
			<?php
		}



	}

}
