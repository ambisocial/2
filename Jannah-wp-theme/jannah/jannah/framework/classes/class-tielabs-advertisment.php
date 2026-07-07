<?php
/**
 * Ads Class
 *
 */


defined( 'ABSPATH' ) || exit; // Exit if accessed directly


/**
 * TIELABS_ADVERTISMENT class with filter hooks
 */
if( ! class_exists( 'TIELABS_ADVERTISMENT' ) ) {

	class TIELABS_ADVERTISMENT {

		/**
		 * Runs on class initialization. Adds filters and actions.
		 */
		function __construct() {

			// Background Ad
			add_action( 'TieLabs/before_wrapper', array( $this, 'background_ad' ) );

			// Before Header
			add_action( 'TieLabs/before_header', array( $this, 'before_header' ) );

			// Before Header
			add_action( 'TieLabs/Logo/after_wrapper', array( $this, 'beside_logo' ) );

			// After Header
			add_action( 'TieLabs/after_header', array( $this, 'after_header' ) );

			// Before Footer
			add_action( 'TieLabs/before_footer', array( $this, 'before_footer' ) );

			// After Post Title
			add_filter( 'TieLabs/after_post_sub_title', array( $this, 'after_post_title' ), 5 );

			// Before Post Ad
			add_filter( 'TieLabs/before_the_article', array( $this, 'before_post' ), 5 );

			// After Post Ad
			add_filter( 'TieLabs/before_post_components', array( $this, 'after_post' ), 5 );

			// Before Post Content Ad
			add_filter( 'TieLabs/before_post_content', array( $this, 'before_post_content' ), 10 );

			// After Post Content Ad
			add_filter( 'TieLabs/after_post_content', array( $this, 'after_post_content' ), 5 );

			//Below Comments
			add_filter( 'TieLabs/post_components', array( $this, 'after_comments' ), 200 );

			// After X posts in Archives
			add_action( 'TieLabs/after_post_in_archives', array( $this, 'ad_after_x_posts' ), 10, 2 );

			// Below Category Slider
			add_action( 'TieLabs/Category/after_slider', array( $this, 'after_category_slider' ) );

			// Above Category Title
			add_action( 'TieLabs/before_archive_title', array( $this, 'before_category_title' ) );

			// Below Category Title
			add_action( 'TieLabs/after_archive_title', array( $this, 'after_category_title' ) );

			// Below Category Posts
			add_action( 'TieLabs/after_archive_posts', array( $this, 'after_category_posts' ) );

			// Below Category Pagination
			add_action( 'TieLabs/after_archive_pagination', array( $this, 'after_category_pagination' ) );

			// Article Inline Ads
			add_filter( 'the_content', array( $this, 'article_inline_ad' ) );

			// Left Right Ads
			add_action( 'wp_enqueue_scripts', array( $this, 'left_right_scripts' ) );
			add_action( 'wp_footer', array( $this, 'left_right_ads' ) );

			// Ad Blocker
			add_action( 'TieLabs/after_main_content', array( $this, 'ad_blocker_append' ), 500 );
			add_action( 'wp_footer', array( $this, 'ad_blocker_popup' ), 500 );

			// Ad before AutoLoaded the posts
			add_action( 'TieLabs/main_content_row/after', array( $this, 'before_autoloaded_post_ad' ), 49 );

		}


		/**
		 * Get the Ad
		 */
		function get_ad( $banner, $before = false, $after = false, $echo = true ){

			// Check if the banner is disabled or hidden on mobiles
			if( ! tie_get_option( $banner ) || TIELABS_HELPER::is_mobile_and_hidden( $banner ) ) {
				return;
			}

			// Check if Disable All ads is enabled for a post
			if( ! TIELABS_HELPER::has_builder() && tie_get_postdata( 'tie_disable_all_ads' ) ) {
				return;
			}

			// Add HTML before the Ad
			$the_ad = apply_filters( 'TieLabs/ad/before', $before, $banner );

			// Ad Title
			if( tie_get_option( $banner.'_title' ) ){
				$the_ad .= tie_get_option( $banner.'_title_link' ) ? '<a title="'. esc_attr( tie_get_option( $banner.'_title' ) ) .'" href="'. esc_attr( tie_get_option( $banner.'_title_link' ) ) .'" rel="nofollow noopener" target="_blank" class="stream-title">' : '<span class="stream-title">';
				$the_ad .= tie_get_option( $banner.'_title' );
				$the_ad .= tie_get_option( $banner.'_title_link' ) ? '</a>' : '</span>';
			}

			// Ad Rotate
			if( tie_get_option( $banner.'_adrotate' ) && function_exists( 'adrotate_ad' ) ) {

				$adrotate_id = tie_get_option( $banner.'_adrotate_id' ) ? tie_get_option( $banner.'_adrotate_id' ) : '';

				if( tie_get_option( $banner.'_adrotate_type' ) == 'group' && function_exists( 'adrotate_group' ) ) {
					$the_ad .= adrotate_group( $adrotate_id, 0, 0, 0);
				}
				elseif( tie_get_option( $banner.'_adrotate_type' ) == 'single' ){
					$the_ad .= adrotate_ad( $adrotate_id, true, 0, 0, 0);
				}
			}

			// Custom Code
			elseif( $code = tie_get_option( $banner.'_adsense' ) ){

				$styles = array();
				$styles[] = tie_get_option( $banner.'_img_width' )  ? 'max-width:'.  tie_get_option( $banner.'_img_width'  ) .'px;': '';
				$styles[] = tie_get_option( $banner.'_img_height' ) ? 'min-height:'. tie_get_option( $banner.'_img_height' ) .'px;' : '';

				if( ! empty( $styles ) ){
					$the_ad .= '<div class="stream-item-size" style="'. join( ' ', array_filter( $styles ) ) .'">';
				}

				$the_ad .= do_shortcode( apply_filters( 'TieLabs/custom_ad_code', $code, $banner ) );

				if( ! empty( $styles ) ){
					$the_ad .= '</div>';
				}
			}

			// Image
			elseif( $img = tie_get_option( $banner.'_img' ) ){

				$target   = tie_get_option( $banner.'_tab' )        ? 'target="_blank"'                       : '';
				$nofollow = tie_get_option( $banner.'_nofollow' )   ? 'rel="nofollow noopener"'               : '';
				$title    = tie_get_option( $banner.'_alt' )        ? tie_get_option( $banner.'_alt' )        : '';
				$width    = tie_get_option( $banner.'_img_width' )  ? tie_get_option( $banner.'_img_width' )  : '728';
				$height   = tie_get_option( $banner.'_img_height' ) ? tie_get_option( $banner.'_img_height' ) : '91';

				$url      = apply_filters( 'TieLabs/ads_url', tie_get_option( $banner.'_url' ) ? tie_get_option( $banner.'_url' ) : '' );

				$the_ad .= '
					<a href="'. esc_url( $url ) .'" title="'. esc_attr( $title ).'" '. $target .' '. $nofollow .'>
						<img src="'. esc_url( $img ) .'" alt="'. esc_attr( $title ).'" width="'. esc_attr( $width ).'" height="'. esc_attr( $height ).'" />
					</a>
				';
			}

			// Add HTML after the Ad
			$the_ad .= apply_filters( 'TieLabs/ad/after', $after, $banner );

			// --
			$the_ad = apply_filters( 'TieLabs/ad/output', $the_ad, $banner );

			// Print the Ad
			if( $echo ){
				echo $the_ad;
			}

			return $the_ad;
		}


		/**
		 * Background Ad
		 */
		function background_ad(){

			if( tie_get_option( 'banner_bg' ) && tie_get_option( 'banner_bg_url' ) && ! tie_is_auto_loaded_post() ){
				echo '<a id="background-stream-cover" href="'. esc_url( tie_get_option('banner_bg_url') ) .'" target="_blank" rel="nofollow noopener"></a>';
			}
		}


		/**
		 * Before Header
		 */
		function before_header(){

			$this->get_ad( 'banner_header', '<div class="stream-item stream-item-above-header">', '</div>' );
		}


		/**
		 * Beside Logo Ad
		 */
		function beside_logo(){

			$this->get_ad( 'banner_top', '<div class="tie-col-md-8 stream-item stream-item-top-wrapper"><div class="stream-item-top">', '</div></div><!-- .tie-col /-->' );
		}


		/**
		 * After Header
		 */
		function after_header(){

			$header_layout = tie_get_option( 'header_layout', 3 );

			// Get the Header AD for Layout 3
			if( $header_layout  == 1 || $header_layout  == 4 ){
				$this->get_ad( 'banner_top', '<div class="stream-item stream-item-top-wrapper"><div class="stream-item-top">', '</div></div><!-- .tie-col /-->' );
			}

			// Below Header Ad
			$this->get_ad( 'banner_below_header', '<div class="stream-item stream-item-below-header">', '</div>' );
		}


		/**
		 * Before Footer
		 */
		function before_footer(){

			$this->get_ad( 'banner_bottom', '<div class="stream-item stream-item-above-footer">', '</div>' );
		}


		/**
		 * Before Post Ad
		 */
		function before_post(){

			if( ! tie_get_postdata( 'tie_hide_above' ) ) {
				if( tie_get_postdata( 'tie_get_banner_above' ) ) {
					echo '<div class="stream-item stream-item-above-post">'. do_shortcode( tie_get_postdata( 'tie_get_banner_above' )) .'</div>';
				}
				else{
					$this->get_ad( 'banner_above', '<div class="stream-item stream-item-above-post">', '</div>' );
				}
			}
		}


		/**
		 * After Post Ad
		 */
		function after_post(){

			if( ! tie_get_postdata( 'tie_hide_below' ) ) {
				if( tie_get_postdata( 'tie_get_banner_below' ) ) {
					echo '<div class="stream-item stream-item-below-post">'. do_shortcode( tie_get_postdata( 'tie_get_banner_below' )) .'</div>';
				}
				else{
					$this->get_ad( 'banner_below', '<div class="stream-item stream-item-below-post">', '</div>' );
				}
			}
		}



		/**
		 * After Post title Ad
		 */
		function after_post_title(){

			if( ! tie_get_postdata( 'tie_hide_after_post_title' ) ) {
				if( tie_get_postdata( 'tie_get_banner_after_post_title' ) ) {
					echo '<div class="stream-item stream-item-after-post-title">'. do_shortcode( tie_get_postdata( 'tie_get_banner_after_post_title' )) .'</div>';
				}
				else{
					$this->get_ad( 'banner_after_post_title', '<div class="stream-item stream-item-after-post-title">', '</div>' );
				}
			}
		}


		/**
		 * Before Post Content Ad
		 */
		function before_post_content(){

			if( ! tie_get_postdata( 'tie_hide_above_content' ) ) {
				if( tie_get_postdata( 'tie_get_banner_above_content' ) ) {
					echo '<div class="stream-item stream-item-above-post-content">'. do_shortcode( tie_get_postdata( 'tie_get_banner_above_content' )) .'</div>';
				}
				else{
					$this->get_ad( 'banner_above_content', '<div class="stream-item stream-item-above-post-content">', '</div>' );
				}
			}
		}


		/**
		 * After Post Content Ad
		 */
		function after_post_content(){

			if( ! tie_get_postdata( 'tie_hide_below_content' ) ) {
				if( tie_get_postdata( 'tie_get_banner_below_content' ) ) {
					echo '<div class="stream-item stream-item-below-post-content">'. do_shortcode( tie_get_postdata( 'tie_get_banner_below_content' )) .'</div>';
				}
				else{
					$this->get_ad( 'banner_below_content', '<div class="stream-item stream-item-below-post-content">', '</div>' );
				}
			}
		}


		/**
		 * After Post Comments Ad
		 */
		function after_comments(){

			$this->get_ad( 'banner_comments', '<div class="stream-item stream-item-below-post-comments">', '</div>' );
		}


		/**
		 * After Category Slider
		 */
		function after_category_slider(){

			$this->get_ad( 'banner_category_below_slider', '<div class="stream-item stream-item-below-category-slider">', '</div>' );
		}


		/**
		 * Before Category Title
		 */
		function before_category_title(){

			if ( ! is_category() ) {
				return;
			}

			$this->get_ad( 'banner_category_above_title', '<div class="stream-item stream-item-above-category-title">', '</div>' );
		}


		/**
		 * After Category Title
		 */
		function after_category_title(){

			if ( ! is_category() ) {
				return;
			}

			$this->get_ad( 'banner_category_below_title', '<div class="stream-item stream-item-below-category-title">', '</div>' );
		}


		/**
		 * After Category Posts
		 */
		function after_category_posts(){

			if ( ! is_category() ) {
				return;
			}

			$this->get_ad( 'banner_category_below_posts', '<div class="stream-item stream-item-below-category-posts">', '</div>' );
		}


		/**
		 * After Category Pagination
		 */
		function after_category_pagination(){

			if ( ! is_category() ) {
				return;
			}

			$this->get_ad( 'banner_category_below_pagination', '<div class="stream-item stream-item-below-category-pagination">', '</div>' );
		}


		/**
		 * After X posts in Archives
		 */
		function ad_after_x_posts( $layout, $latest_count ){

			// Ads Html format
			if( $layout == 'overlay' || $layout == 'overlay-spaces' || $layout == 'masonry' ){

				$before_ad = '<div class="post-element stream-item stream-item-between stream-item-between-2">';
				$after_ad  = '</div>';
			}
			else{
				$before_ad = '<li class="post-item stream-item stream-item-between stream-item-between-2"><div class="post-item-inner">';
				$after_ad  = '</div></li>';
			}

			for ( $i=1; $i <= 5; $i++ ) { 
				if( $latest_count == tie_get_option( 'between_posts_'. $i .'_posts_number' ) ){
					$this->get_ad( 'between_posts_'.$i, $before_ad, $after_ad );
				}
			}

		}


		/**
		 * Article Inline Ads
		 */
		function article_inline_ad( $content ){

			if( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ){
				return $content;
			}

			if ( is_singular('post') && ! is_admin() ) {

				for ( $i=1; $i <= 7 ; $i++) {

					$ad_id = 'article_inline_ad_'. $i;

					if( tie_get_option( $ad_id ) ){
						$paragraph_number = tie_get_option( $ad_id . '_paragraphs_number', 4 + $i );
						$content = $this->insert_article_inline_ad( $ad_id, $paragraph_number, $content );
					}
				}
			}

			return $content;
		}


		/**
		 * Insert the Article Inline Ads
		 */
		function insert_article_inline_ad( $ad_id, $paragraph_number, $content ) {

			$ad_align = tie_get_option( $ad_id . '_align', 'center' );
			$ad_code  = $this->get_ad( $ad_id, '<div class="stream-item stream-item-in-post stream-item-inline-post align'. $ad_align .'">', '</div>', false );

			return tie_post_inline_content( $ad_code, $paragraph_number, $content );
		}


		/**
		 * Before the AutoLoaded Post
		 */
		function before_autoloaded_post_ad(){
			
			if( ! is_singular( 'post' ) || ! defined( 'JANNAH_AUTOLOAD_POSTS_VERSION' ) || ! tie_is_loaded_posts_active() ){
				return;
			}
						
			$this->get_ad( 'autoload_post_before', '<div class="stream-item stream-item-before-autoloaded">', '</div>' );
		}


		/**
		 * Ad Blocker
		 */
		function ad_blocker_append(){

			if( tie_get_option( 'ad_blocker_detector' ) || tie_get_option( 'ad_blocker_disallow_images' ) ){				
				echo '
					<div class="adsbygoogle Ad-Container sidebar-ad ad-slot" data-ad-manager-id="1" data-ad-module="1" data-ad-width="100" data-adblockkey="200" data-advadstrackid="1">
						<div style="z-index:-1; height:0; width:1px; visibility: hidden; bottom: -1px; left: 0;"></div>
					</div>
				';
			}
		}


		/**
		 * Ad Blocker Message
		 */
		function ad_blocker_popup(){

			// The Ad Blocker POPup message
			if( ! tie_get_option( 'ad_blocker_detector' ) ){
				return;
			}

			// --
			if( tie_get_option( 'ad_blocker_dismissable' ) && tie_get_option( 'ad_blocker_show_once' ) && isset( $_COOKIE[ 'AdBlockerDismissed' ] ) ){
				return;
			}

			$class = 'is-fixed-popup';
				
			?>
				<div id="tie-popup-adblock" class="tie-popup <?php echo $class ?>">

					<div class="tie-popup-container">
						<div class="container-wrapper">

							<?php
								if( tie_get_option( 'ad_blocker_dismissable' ) ){ ?>
									<a href="#" class="tie-btn-close remove big-btn light-btn" <?php if( tie_get_option( 'ad_blocker_show_once' ) ) echo 'data-show-once="true"'; ?>>
										<span class="screen-reader-text"><?php esc_html_e( 'Close', TIELABS_TEXTDOMAIN ); ?></span>
									</a>
									<?php
								}
							?>

							<span class="tie-adblock-icon tie-icon-ban" aria-hidden="true"></span>

							<h2><?php echo tie_get_option( 'adblock_title', esc_html__( 'Adblock Detected', TIELABS_TEXTDOMAIN ) ); ?></h2>

							<div class="adblock-message">
								<?php echo tie_get_option( 'adblock_message', esc_html__( 'Please consider supporting us by disabling your ad blocker', TIELABS_TEXTDOMAIN ) ); ?>
							</div>

						</div><!-- .container-wrapper  /-->
					</div><!-- .tie-popup-container /-->
				</div><!-- .tie-popup /-->
			<?php
		}


		/**
		 * Left Right Ads
		 */
		function left_right_scripts(){

			if( ! tie_get_option( 'side_e3lan' ) || tie_is_mobile() || tie_is_auto_loaded_post() ){
				return;
			}

			wp_enqueue_script( 'tie-side-e3lan', TIELABS_TEMPLATE_URL . '/assets/js/side-e3lan.js', array(), false, true );

			$side_e3lan_vars = array(
				'is_boxed'          => ( tie_get_option( 'theme_layout' ) == 'boxed' || tie_get_option( 'theme_layout' ) == 'framed' ),
				'screen_width'      => tie_get_option( 'side_e3lan_screen_width' ),
				'side_margin'       => 15,
				'margin_top'        => tie_get_option( 'side_e3lan_margin_top' ),
				'margin_top_scroll' => tie_get_option( 'side_e3lan_margin_top_scroll' ),
				'left_ad_code'      => tie_get_option( 'side_e3lan_left_code' ),
				'left_ad_width'     => tie_get_option( 'side_e3lan_left_width', 200 ),
				'left_ad_height'    => tie_get_option( 'side_e3lan_left_height' ),
				'right_ad_code'     => tie_get_option( 'side_e3lan_right_code' ),
				'right_ad_width'    => tie_get_option( 'side_e3lan_right_width', 200 ),
				'right_ad_height'   => tie_get_option( 'side_e3lan_right_height' ),
			);

			wp_localize_script( 'tie-side-e3lan', 'tie_side_e3lan', apply_filters( 'TieLabs/js_side_e3lan_vars', $side_e3lan_vars ) );
			
			if( tie_get_option( 'side_e3lan_screen_width' ) ){
				echo '
					<style>
						@media (max-width: '. tie_get_option( 'side_e3lan_screen_width' ) .'px){
							.side-stream{
								display: none !important;
							}
						}
					</style>
				';
			}
		}

		/**
		 * Left Right Ads
		 */
		function left_right_ads(){

			if( ! tie_get_option( 'side_e3lan' ) || tie_is_mobile() || tie_is_auto_loaded_post() ){
				return;
			}

			?>
				<div id="side-stream-container" class="container"></div>
				<div class="side-stream" id="side-stream-right" style="display:none; z-index:108; position:absolute; text-align:center; top:0px; width:<?php echo tie_get_option( 'side_e3lan_right_width', 200 ) ?>px;<?php echo ( tie_get_option( 'side_e3lan_right_height' ) ) ? 'height:'. tie_get_option( 'side_e3lan_right_height' ).'px;':''?> overflow:hidden;">
					<?php
						$right_ad = tie_get_option( 'side_e3lan_right_code' );
						
						if( strpos( $right_ad, 'adsbygoogle.js' ) !== false && tie_get_option( 'side_e3lan_screen_width' ) ){
							$right_ad = str_replace( 'src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', 'disabled="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', $right_ad );
							$adsense_code_detected = true;
						}

						echo do_shortcode( $right_ad );
					?>
				</div>

				<div class="side-stream" id="side-stream-left"  style="display:none; z-index:108; position:absolute; text-align:center; top:0px; width:<?php echo tie_get_option( 'side_e3lan_left_width',  200 ) ?>px;<?php echo ( tie_get_option( 'side_e3lan_left_height'  ) ) ? 'height:'. tie_get_option( 'side_e3lan_left_height'  ).'px;':''?> overflow:hidden;">
					<?php
						$left_ad = tie_get_option( 'side_e3lan_left_code' );
						
						if( strpos( $left_ad, 'adsbygoogle.js' ) !== false && tie_get_option( 'side_e3lan_screen_width' ) ){
							$left_ad = str_replace( 'src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', 'disabled="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', $left_ad );
							$adsense_code_detected = true;
						}

						echo do_shortcode( $left_ad );
					?>
				</div>
			<?php

			// Fix Adsense Uncaught TagError: adsbygoogle.push() error: No slot size for availableWidth=0
			if( isset( $adsense_code_detected ) ){
				?>
					<script>
						if( window.screen.width > <?php echo tie_get_option( 'side_e3lan_screen_width' ) ?> ){
							function tieAdsDownloadAdsenseJSAtOnload() {
								var element = document.createElement("script");
								element.src = "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js";
								document.body.appendChild(element);
							}
							if (window.addEventListener){
								window.addEventListener("load", tieAdsDownloadAdsenseJSAtOnload, false);
							}
							else if (window.attachEvent){
								window.attachEvent("onload", tieAdsDownloadAdsenseJSAtOnload);
							}
							else{
								window.onload = tieAdsDownloadAdsenseJSAtOnload;
							}
						}
					</script>
				<?php

			}
		}

	}


	// Single instance.
	$TIELABS_ADVERTISMENT = new TIELABS_ADVERTISMENT();
}
