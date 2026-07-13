<?php
/**
 * Post views Class
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly



if( ! class_exists( 'TIELABS_POSTVIEWS' ) ) {

	class TIELABS_POSTVIEWS{

		/**
		 * __construct
		 *
		 * Class constructor where we will call our filter and action hooks.
		 */
		function __construct(){

			add_filter( 'TieLabs/post_options_meta',         array( $this, 'save_custom_views' ) );

			add_filter( 'manage_posts_columns',              array( $this, 'posts_column_views' ) );
			add_filter( 'manage_edit-post_sortable_columns', array( $this, 'sort_postviews_column' ) );

			add_action( 'manage_posts_custom_column',        array( $this, 'posts_custom_column_views' ), 5, 2 );
			add_action( 'pre_get_posts',                     array( $this, 'sort_postviews' ) );

			add_action( 'wp_footer',                         array( $this, 'set_post_views' ) );
			add_action( 'amp_post_template_head',            array( $this, 'set_post_views' ) );

			add_action( 'wp_enqueue_scripts',                array( $this, 'postview_cache_enqueue' ), 25 );

			add_action( 'wp_ajax_tie_postviews',             array( $this, 'set_post_views_ajax' ) );
			add_action( 'wp_ajax_nopriv_tie_postviews',      array( $this, 'set_post_views_ajax' ) );
		}


		/**
		 * increment_views
		 *
		 * Count number of views
		 */
		function increment_views( $post_id = false ){
			
			if( ! $post_id ){
				$post_id = get_the_ID();
			}

			if( wp_is_post_revision( $post_id ) || get_post_status( $post_id ) == 'draft' ){
				return false;
			}

			// Increase number of views +1
			$count     = 0;
			$count_key = TIELABS_HELPER::get_views_meta_field();
			$count     = (int) get_post_meta( $post_id, $count_key, true );

			// The Starter Number
			if( ( empty( $count ) || $count == 0 ) && tie_get_option( 'views_starter_number' ) ){
				$count = (int) tie_get_option( 'views_starter_number' );
			}

			$new_count = $count + 1;
			update_post_meta( $post_id, $count_key, $new_count );

			// Last 7 days
			if( tie_get_option( 'views_7_days' ) ){
				
				$views_last_7_days = get_post_meta( $post_id, 'tie_views_7_days_array', true );
				$views_last_7_days = is_array( $views_last_7_days ) ? $views_last_7_days : array();

				$todays_date = date( 'Ymd' );
				
				if( isset( $views_last_7_days[ $todays_date ] ) ){
					$views_last_7_days[ $todays_date ] = $views_last_7_days[ $todays_date ] + 1;					
				}
				else{

					$views_last_7_days[ $todays_date ] = 1;

					$start_of_7_days = date( 'Ymd', strtotime('-7 days') );

					foreach( $views_last_7_days as $day_date => $views_number ){
						if( $day_date < $start_of_7_days ){
							unset( $views_last_7_days[ $day_date ] );
						}
					}
				} 

				update_post_meta( $post_id, 'tie_views_7_days_array', $views_last_7_days );
				update_post_meta( $post_id, 'tie_views_7_days', array_sum( $views_last_7_days ) ); // total
				update_post_meta( $post_id, 'tie_views_7_days_last_date', $todays_date ); // The Date of the last stored day

				/*
				var_dump( array_sum( $views_last_7_days ) );
				echo '<br />';
				var_dump( $views_last_7_days );
				exit;
				*/
			}


			return $new_count;
		}


		/**
		 * set_post_views
		 *
		 * Count number of views
		 */
		function set_post_views(){

			// Disable via filter
			if( ! apply_filters( 'TieLabs/Post_Views/increment', true ) ){
				return;
			}

			// Run only if the post views option is set to THEME's post views module
			if( tie_get_option( 'tie_post_views' ) != 'theme' || ! is_single() || TIELABS_HELPER::is_bot() ){
				return;
			}

			// Run only on the first page of the post
			$page = get_query_var( 'paged', 1 );

			if( $page > 1 ){
				return false;
			}

			if( ! self::is_cache_enabled() || ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) ){

				$this->increment_views();			
			}
		}


		/**
		 * postview_cache_enqueue
		 *
		 * Calculate Post Views With WP_CACHE Enabled
		 */
		function postview_cache_enqueue(){

			// Disable via filter
			if( ! apply_filters( 'TieLabs/Post_Views/increment', true ) ){
				return;
			}

			// Run only if the post views option is set to THEME's post views module
			// Single Post page
			// Cache is active
			if( tie_get_option( 'tie_post_views' ) != 'theme' || ! is_single() || ! self::is_cache_enabled() ){
				return;
			}

			// Add the js code
			$cache_js = '
				jQuery.ajax({
					type : "GET",
					url  : "'. esc_url( admin_url('admin-ajax.php') ) .'",
					data : "postviews_id='. get_the_ID() .'&action=tie_postviews",
					cache: !1,
					success: function( data ){
						jQuery(".single-post-meta").find(".meta-views").html( data );
					}
				});

			';

			TIELABS_HELPER::inline_script( 'tie-scripts', $cache_js );
		}


		/**
		 * is_cache_enabled
		 *
		 */
		function is_cache_enabled(){

			// Most of the Cache plugins uses the WP_CACHE
			if ( defined( 'WP_CACHE' ) && WP_CACHE ){
				return true;
			}

			// Wp Fatest Cache
			if( class_exists( 'WpFastestCache' ) ){
				if( ! empty( $GLOBALS['wp_fastest_cache_options']->wpFastestCacheStatus ) && $GLOBALS['wp_fastest_cache_options']->wpFastestCacheStatus == 'on' ){
					return true;
				}
			}

			return false;
		}


		/**
		 * set_post_views_ajax
		 *
		 * Increment Post Views With WP_CACHE Enabled
		 */
		function set_post_views_ajax(){

			// Run only if the post views option is set to THEME's post views module
			if( tie_get_option( 'tie_post_views' ) != 'theme' || TIELABS_HELPER::is_bot() ){
				return;
			}

			// Increase number of views +1
			if( ! empty( $_GET['postviews_id'] ) && tie_get_option( 'tie_post_views' ) && self::is_cache_enabled() ){

				$new_count = $this->increment_views( intval( $_GET['postviews_id'] ) );

				$formated = apply_filters( 'TieLabs/post_views_number', number_format_i18n( (float)$new_count ) );
				echo '<span class="tie-icon-fire" aria-hidden="true"></span> '. $formated .'</span>';
			}

			exit();
		}


		/**
		 * save_custom_views
		 *
		 * Add the views meta name to the meta_fields array
		 */
		function save_custom_views( $meta_fields ){

			if( tie_get_option( 'tie_post_views') == 'theme' ){
				$meta_fields[] = TIELABS_HELPER::get_views_meta_field();
			}

			return $meta_fields;
		}


		/**
		 * posts_column_views
		 *
		 * Dashboared column title
		 */
		function posts_column_views( $defaults ){

			$post_type = get_post_type();
			
			// Run only if the post views option is set to THEME's post views module
			if( is_post_type_viewable( $post_type ) && tie_get_option( 'tie_post_views' )  == 'theme' ){
				$defaults['tie_post_views'] = esc_html__( 'Views', TIELABS_TEXTDOMAIN );
			}

			return $defaults;
		}


		/**
		 * posts_custom_column_views
		 *
		 * Dashboared column content
		 */
		function posts_custom_column_views( $column_name, $id ){

			// Run only if the post views option is set to THEME's post views module
			if( tie_get_option( 'tie_post_views' ) != 'theme' ){
				return;
			}

			if( $column_name === 'tie_post_views' ){
				echo TIELABS_POSTVIEWS::get_views( '', get_the_ID() );
			}
		}


		/**
		 * sort_postviews_column
		 *
		 * Sort Post views column in the dashboared
		 */
		function sort_postviews_column( $defaults ){

			// Run only if the post views option is set to THEME's post views module
			if( tie_get_option( 'tie_post_views' ) == 'theme' ){
				$defaults['tie_post_views'] = 'tie-views';
			}

			return $defaults;
		}


		/**
		 * sort_postviews
		 *
		 * Sort Post views in the dashboared
		 */
		function sort_postviews( $query ) {

			if( ! is_admin() ){
				return;
			}

			$orderby   = $query->get('orderby');
			$count_key = TIELABS_HELPER::get_views_meta_field();

			if( $orderby == 'tie-views' ) {
				$query->set( 'meta_key', $count_key );
				$query->set( 'orderby',  'meta_value_num' );
			}
		}


		/*
		 * Display number of views
		 */
		public static function get_views( $text = '', $post_id = 0 ){

			// Return if thr post views module is disabled
			$post_views_type = tie_get_option( 'tie_post_views' );

			if( ! $post_views_type ){
				return;
			}

			if( empty( $post_id ) ) {
				$post_id = get_the_ID();
			}

			$views_class = '';
			$formated = $count = 0;

			if( $post_views_type == 'wp-statistics' && function_exists( 'wp_statistics_pages' ) ){

				$count = wp_statistics_pages( 'total', "", $post_id) ;
			}
			else{
				$count_key = TIELABS_HELPER::get_views_meta_field();
				$count     = get_post_meta( $post_id, $count_key, true );
				$count     = empty( $count ) ? 0 : $count;
			}

			if( tie_get_option( 'views_colored' ) ){

				if( $count > tie_get_option( 'views_veryhot_color', 5000 ) ){
					$views_class = 'very-hot';
				}
				elseif( $count > tie_get_option( 'views_hot_color', 2000 ) ){
					$views_class = 'hot';
				}
				elseif( $count > tie_get_option( 'views_warm_color', 500 ) ){
					$views_class = 'warm';
				}
			}

			$formated = apply_filters( 'TieLabs/post_views_number', number_format_i18n( (float)$count ) );

			$output = '<span class="meta-views meta-item '. $views_class .'"><span class="tie-icon-fire" aria-hidden="true"></span> '.$formated.' '.$text.'</span>';

			return apply_filters( 'TieLabs/post_views_output', $output, $post_id, $formated, $text );
		}

	}

	// Instantiate the class
	new TIELABS_POSTVIEWS();

}
