<?php
/**
 * Video Playlist Class
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly



if( ! class_exists( 'TIELABS_VIDEOS' ) ) {

	class TIELABS_VIDEOS {


		/**
		 * Runs on class initialization. Adds filters and actions.
		 */
		function __construct() {

			// Download and set the featured image
			add_filter( 'save_post', array( $this, 'assign_featured_image' ) );
		}


		/**
		 * Download and assign the featured image while Saving the Post
		 */
		function assign_featured_image( $post_id ){

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// We need to prevent trying to assign when trashing or untrashing posts in the list screen.
			// get_current_screen() was not providing a unique enough value to use here.
			if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'trash', 'untrash' ) )  ) {
				return;
			}

			// Check if the post has a featured image
			if ( empty( $post_id ) || has_post_thumbnail( $post_id ) || wp_is_post_revision( $post_id ) ){
				return;
			}

			// Check if the post is a Video and has tie_video_url
			if( ! empty( $_POST[ 'tie_post_head' ] ) && $_POST[ 'tie_post_head' ] == 'video' && ! empty( $_POST[ 'tie_video_url' ] ) ){

				$video = $_POST[ 'tie_video_url' ];

				// YouTube
				if( $video_id = self::is_youtube( $video ) ){

					$video_data = self::get_youtube_info( $video_id );

					$sizes_array = array( 'maxres', 'standard', 'high', 'medium' );

					foreach ( $sizes_array as $size ) {

						if( ! empty( $video_data['snippet']['thumbnails'][ $size ] ) ){
							$video_thumbnail_url = $video_data['snippet']['thumbnails'][ $size ]['url'];
							break;
						}
					}
				}
				// Vimeo
				elseif( $video_id = self::is_vimeo( $video ) ) {

					$video_data = self::get_vimeo_info( $video_id );

					if( ! empty( $video_data['thumbnail_large'] ) ){
						$video_thumbnail_url = $video_data['thumbnail_large'];
					}
					elseif( ! empty( $video_data['thumbnail_medium'] ) ){
						$video_thumbnail_url = $video_data['thumbnail_medium'];
					}
				}

				// Set Thumbnail
				if( ! empty( $video_thumbnail_url ) ) {

					$attachment_id = self::download_thumbnail( $video_thumbnail_url, $post_id, $video_id );

					if ( is_wp_error( $attachment_id ) ) {
						return;
					}

					// Woot! We got an image, so set it as the post thumbnail.
					set_post_thumbnail( $post_id, $attachment_id );
				}
			}
		}


		/**
		 * Set the post thumbnail
		 */
		public static function download_thumbnail( $url, $post_id, $video_id ) {

			$filename = sanitize_title( preg_replace( '/[^a-zA-Z0-9\s]/', '-', get_the_title() ) ) . '-' . $video_id;

			require_once( ABSPATH . 'wp-admin/includes/file.php' );

			// Download file to temp location, returns full server path to temp file, ex; /home/user/public_html/mysite/wp-content/26192277_640.tmp.
			$tmp = download_url( $url );

			// If error storing temporarily, unlink.
			if ( is_wp_error( $tmp ) ) {
				// And output wp_error.
				return $tmp;
			}

			// Fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG|webp|WebP)/', $url, $matches );

			// Extract filename from url for title.
			$url_filename = basename( $matches[0] );

			// Determine file type (ext and mime/type).
			$url_type = wp_check_filetype( $url_filename );

			// Vimeo doesn't add .webp at the end of images
			if( empty( $url_type['ext'] ) && strpos( $url, '.vimeocdn.' ) !== false ){
				$url_type = array(
					'ext' => 'webp'
				);
			}

			// Override filename if given, reconstruct server path.
			if ( ! empty( $filename ) ) {
				$filename = sanitize_file_name( $filename );

				// Extract path parts.
				$tmppath = pathinfo( $tmp );

				// Build new path.
				$new = $tmppath['dirname'] . '/' . $filename . '.' . $tmppath['extension'];

				// Renames temp file on server.
				rename( $tmp, $new );

				// Push new filename (in path) to be used in file array later.
				$tmp = $new;
			}

			// Full server path to temp file.
			$file_array['tmp_name'] = $tmp;

			if ( ! empty( $filename ) ) {
				// User given filename for title, add original URL extension.
				$file_array['name'] = $filename . '.' . $url_type['ext'];
			}
			else {
				// Just use original URL filename.
				$file_array['name'] = $url_filename;
			}

			$post_data = array(
				// Just use the original filename (no extension).
				'post_title'  => get_the_title( $post_id ),
				// Make sure gets tied to parent.
				'post_parent' => $post_id,
			);

			// Required libraries for media_handle_sideload.
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Do the validation and storage stuff.
			// $post_data can override the items saved to wp_posts table, like post_mime_type, guid, post_parent, post_title, post_content, post_status.
			$att_id = media_handle_sideload( $file_array, $post_id, null, $post_data );

			// If error storing permanently, unlink.
			if ( is_wp_error( $att_id ) ) {
				// Clean up.
				@unlink( $file_array['tmp_name'] );

				// And output wp_error.
				return $att_id;
			}

			return $att_id;
		}


		/**
		 * Check if the Video is YouTube
		 */
		private static function is_youtube( $video ) {
			preg_match( "#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $video, $matches );

			if( ! empty( $matches[0] ) ){
				return TIELABS_HELPER::remove_spaces( $matches[0] );
			}

			return false;
		}


		/**
		 * Get YouTube Video data
		 */
		private static function get_youtube_info( $vid ){

			if( ! tie_get_option( 'api_youtube' ) ){
				return false;
			}

			// Build the Api request
			$params = array(
				'part' => 'snippet,contentDetails',
				'id'   => $vid,
				'key'  => TIELABS_HELPER::remove_spaces( tie_get_option( 'api_youtube' ) ),
			);

			$api_url = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query( $params );
			$request = wp_remote_get( $api_url );

			// Check if there are errors
			if( is_wp_error( $request ) ){
				tie_debug_log( $request->get_error_message(), true );
				return null;
			}

			// Prepare the data
			$result = json_decode( wp_remote_retrieve_body( $request ), true );

			// Check Youtube API Errors
			if( ! empty( $result['error']['errors'][0]['message'] ) ){
				update_option( 'tie_youtube_api_error', $result['error']['errors'][0]['message'], 'no' );
				tie_debug_log( $result['error']['errors'][0]['message'], true );
				return null;
			}

			// Check if the video title is exists
			if( empty( $result['items'][0]['snippet']['title'] ) ) {
				return null;
			}

			return $result['items'][0];
		}


		/**
		 * Check if the Video is Vimeo
		 */
		private static function is_vimeo( $video ) {
			preg_match( "/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/", $video, $matches );

			if( ! empty( $matches[5] ) ){
				return TIELABS_HELPER::remove_spaces( $matches[5] );
			}

			return false;
		}


		/**
		 * Get Vimeo Video data
		 */
		private static function get_vimeo_info( $vid ){

			// Build the Api request
			$api_url = "https://vimeo.com/api/v2/video/$vid.json";
			$request = wp_remote_get( $api_url );

			// Check if there is no any errors
			if( is_wp_error( $request ) ){

				tie_debug_log( $request->get_error_message(), true );

				return null;
			}

			// Prepare the data
			$result = json_decode( wp_remote_retrieve_body( $request ), true );

			// Check if the video title exists
			if( empty( $result[0]['title'] ) ){
				return null;
			}

			return $result[0];
		}


	}

	// Instantiate the class
	new TIELABS_VIDEOS();

}
