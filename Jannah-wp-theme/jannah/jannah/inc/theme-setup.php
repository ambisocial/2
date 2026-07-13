<?php
/**
 * Theme Custom Styles
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly


/** 
 * Setup Theme
 */
add_action( 'after_setup_theme', 'jannah_theme_setup' );
function jannah_theme_setup(){

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/**
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/**
	 * Switch default core markup for comment form, and comments to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'comment-list',
		'comment-form',
		'search-form',
		'gallery',
		'caption'
	) );

	/**
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );

	// Register the custom image sizes
	foreach( tie_default_image_sizes() as $name => $args ){
		
		if( ! tie_get_option( 'disable_image_size_'.  $name ) ){

			if( $image_size = tie_get_option( 'image_size_'.$name ) ){

				if( isset( $image_size['crop'] ) ){
					$image_size['crop'] = ( $image_size['crop'] == 'yes' ) ? true : false;
				}

				$args = wp_parse_args( $image_size, $args );
			}

			add_image_size( $name, $args['width'], $args['height'], $args['crop'] );
		}
	}


	// Add Support for the Arqam Lite plugin.
	add_theme_support( 'Arqam_Lite' );

	// Gutenberg
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );

	/*
	 * This theme styles the visual editor to resemble the theme style,
	 * specifically font, colors, and column width.
	 */
	if( ! tie_get_option( 'disable_editor_styles' ) && is_admin() ){
		add_editor_style( 'assets/css/editor-style.css' );
	}

	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 */
	load_theme_textdomain( TIELABS_TEXTDOMAIN, TIELABS_TEMPLATE_PATH . '/languages' );

	// The theme uses wp_nav_menu() in multiple locations.
	register_nav_menus( array(
		'top-menu'    => esc_html__( '​Secondary Nav Menu', TIELABS_TEXTDOMAIN ),
		'primary'     => esc_html__( 'Main Nav Menu',     TIELABS_TEXTDOMAIN ),
		'404-menu'    => esc_html__( '404 Page menu',     TIELABS_TEXTDOMAIN ),
		'footer-menu' => esc_html__( 'Footer Navigation', TIELABS_TEXTDOMAIN ),
	));

}


/** 
 * Default Image Sizes
 */
function tie_default_image_sizes(){

	return apply_filters( 'TieLabs/default_image_sizes', array(
		TIELABS_THEME_SLUG.'-image-small' => array(
			'width'  => 220,
			'height' => 150,
			'crop'   => true,
		),
		TIELABS_THEME_SLUG.'-image-large' => array(
			'width'  => 390,
			'height' => 220,
			'crop'   => true,
		),
		TIELABS_THEME_SLUG.'-image-post' => array(
			'width'  => 780,
			'height' => 470,
			'crop'   => true,
		),
	));
}


/*
 * Register Main Scripts and Styles
 */
add_action( 'wp_enqueue_scripts', 'jannah_register_assets', 20 );
function jannah_register_assets(){

	//$ver = current_user_can( 'switch_themes' ) ? time() : TIELABS_DB_VERSION; // Always avoid browser cache for admins
	$ver = apply_filters( 'TieLabs/enqueue_scripts/version', TIELABS_DB_VERSION );
	$min = TIELABS_STYLES::is_minified();

	/**
	 * Scripts
	 */

	/*
	 * Main Scripts file
	 */
	wp_register_script( 'tie-scripts', TIELABS_TEMPLATE_URL . '/assets/js/scripts'. $min .'.js', array( 'jquery' ), $ver, true );

	/*
	 * Scripts requrie tie-scripts
	 */
	// Sliders
	wp_register_script( 'tie-js-sliders', TIELABS_TEMPLATE_URL . '/assets/js/sliders'. $min .'.js', array( 'jquery', 'tie-scripts' ), $ver, true );
	// Desktop only JS
	wp_register_script( 'tie-js-desktop', TIELABS_TEMPLATE_URL . '/assets/js/desktop'. $min .'.js', array( 'jquery', 'tie-scripts' ), $ver, true );
	// single
	wp_register_script( 'tie-js-single', TIELABS_TEMPLATE_URL . '/assets/js/single'. $min .'.js',  array( 'jquery', 'tie-scripts' ), $ver, true );
	// ViewPort
	wp_register_script( 'tie-js-viewport', TIELABS_TEMPLATE_URL . '/assets/js/viewport-scripts.js',  array( 'jquery', 'tie-scripts' ), $ver, true );
	// Breaking News
	wp_register_script( 'tie-js-breaking', TIELABS_TEMPLATE_URL . '/assets/js/br-news.js', array( 'jquery' ), $ver, true );
	// Live Search
	wp_register_script( 'tie-js-livesearch', TIELABS_TEMPLATE_URL . '/assets/js/live-search.js', array( 'jquery' ), $ver, true );
	// iLightBox
	wp_register_script( 'tie-js-ilightbox', TIELABS_TEMPLATE_URL . '/assets/ilightbox/lightbox.js', array( 'jquery' ), $ver, true );
	// Velocity
	wp_register_script( 'tie-js-velocity', TIELABS_TEMPLATE_URL . '/assets/js/velocity.js', array( 'jquery' ), $ver, true );
	// Parallax
	wp_register_script( 'tie-js-parallax', TIELABS_TEMPLATE_URL . '/assets/js/parallax.js', array( 'jquery', 'imagesloaded' ), $ver, true );
	// css-vars-ponyfill
	//wp_register_script( 'tie-js-css-vars-ponyfill', TIELABS_TEMPLATE_URL . '/assets/js/css-vars-ponyfill.js', array(), $ver, true );
	
	// Google Search
	if( $google_search_id = tie_get_option( 'google_search_engine_id' ) ){

		// Some users mistakly add the full HTML code of the search instead of the ID
		if( strpos( $google_search_id, '<' ) !== false ){
			preg_match( '/src="([^"]*)"/i', $google_search_id, $google_search_parts );
			if( ! empty( $google_search_parts[1] ) ){
				$google_search_id = str_replace( 'https://cse.google.com/cse.js?cx=', '', $google_search_parts[1] );
			}
		}

		wp_register_script( 'tie-google-search', 'https://cse.google.com/cse.js?cx='. TIELABS_HELPER::remove_spaces( $google_search_id ), array(), 1, true );
	}

	/**
	 * Styles
	 */
	// base.css file
	wp_register_style( 'tie-css-base', TIELABS_TEMPLATE_URL . '/assets/css/base'. $min .'.css', array(), $ver );

	// Main style.css file
	wp_register_style( 'tie-css-styles', TIELABS_TEMPLATE_URL . '/assets/css/style'. $min .'.css', array(), $ver );

	// Single Post CSS file
	wp_register_style( 'tie-css-single', TIELABS_TEMPLATE_URL . '/assets/css/single'. $min .'.css', array(), $ver );

	// Widgets
	wp_register_style( 'tie-css-widgets', TIELABS_TEMPLATE_URL . '/assets/css/widgets'. $min .'.css', array(), $ver );

	// Widgets
	wp_register_style( 'tie-css-helpers', TIELABS_TEMPLATE_URL . '/assets/css/helpers'. $min .'.css', array(), $ver );

	// Font Awesome 5.0
	wp_register_style( 'tie-fontawesome5', TIELABS_TEMPLATE_URL . '/assets/css/fontawesome.css', array(), $ver );

	// Print
	wp_register_style( 'tie-css-print', TIELABS_TEMPLATE_URL . '/assets/css/print.css', array(), $ver, 'print' );

	// LightBox
	wp_register_style( 'tie-css-ilightbox', TIELABS_TEMPLATE_URL . '/assets/ilightbox/'. tie_get_option( 'lightbox_skin', 'dark' ) .'-skin/skin.css', array(), $ver );

	// Mp-Timetable css file
	if ( TIELABS_MPTIMETABLE_IS_ACTIVE ){
		wp_enqueue_style( 'tie-css-mptt', TIELABS_TEMPLATE_URL.'/assets/css/plugins/mptt'. $min .'.css', array(), $ver );
	}

	// Shortcodes
	if( TIELABS_EXTENSIONS_IS_ACTIVE ){
		wp_register_style( 'tie-css-shortcodes', TIELABS_TEMPLATE_URL . '/assets/css/plugins/shortcodes'. $min .'.css', array(), $ver );
		wp_register_script('tie-js-shortcodes',  TIELABS_TEMPLATE_URL . '/assets/js/shortcodes.js', array( 'tie-js-sliders' ), $ver, true );
	}

	// Prevent TieLabs shortcodes plugin from loading its JS and CSS files
	add_filter( 'tie_plugin_shortcodes_enqueue_assets', '__return_false' );
}


/*
 * Enqueue Main Scripts
 */
add_action( 'wp_enqueue_scripts', 'jannah_enqueue_scripts', 20 );
function jannah_enqueue_scripts(){

	// Scripts
	wp_enqueue_script( 'tie-scripts' );

	// Add support for CSS vars to the Legact Browsers
	//wp_enqueue_script( 'tie-js-css-vars-ponyfill' );

	// LightBox
	if( tie_get_option( 'lightbox_all' ) || ( tie_get_option( 'footer_instagram' ) && tie_get_option( 'footer_instagram_media_link' ) == 'file' ) ){
		wp_enqueue_script( 'tie-js-ilightbox' );
	}

	// Shortcodes
	if( TIELABS_EXTENSIONS_IS_ACTIVE ){
		wp_enqueue_script( 'tie-js-shortcodes' );
	}

	// Desktop only scripts
	if( ! tie_is_mobile() ){

		wp_enqueue_script( 'tie-js-desktop' );

		// Live search
		if( tie_menu_has_search( 'top_nav', true ) || tie_menu_has_search( 'main_nav', true ) ){
			wp_enqueue_script( 'tie-js-livesearch' );
		}
	}

	// Mobile
	// else{ // Always load the file even on desktop
		if( tie_get_option( 'mobile_header_components_search') && tie_get_option( 'mobile_header_live_search') ){
			wp_enqueue_script( 'tie-js-livesearch' );
		}
	//}

	// Single pages with no builder
	if( is_singular() && ! TIELABS_HELPER::has_builder() ){

		// Single.js
		wp_enqueue_script( 'tie-js-single' );

		// Queue Comments reply js
		if ( comments_open() && get_option('thread_comments') ){
			wp_enqueue_script( 'comment-reply' );
		}
	}
}


/*
 * Enqueue Styles
 */
add_action( 'wp_enqueue_scripts', 'jannah_enqueue_styles', 20 );
function jannah_enqueue_styles(){

	wp_enqueue_style( 'tie-css-base' );
	wp_enqueue_style( 'tie-css-styles' );
	wp_enqueue_style( 'tie-css-widgets' );
	wp_enqueue_style( 'tie-css-helpers' );
	wp_enqueue_style( 'tie-fontawesome5' );

	if( tie_get_option( 'lightbox_all' ) || ( tie_get_option( 'footer_instagram' ) && tie_get_option( 'footer_instagram_media_link' ) == 'file' ) ){
		wp_enqueue_style( 'tie-css-ilightbox' );
	}

	if( TIELABS_EXTENSIONS_IS_ACTIVE ){
		wp_enqueue_style( 'tie-css-shortcodes' );
	}

	// Single pages with no builder
	if( ( is_singular() && ! TIELABS_HELPER::has_builder() ) || ( TIELABS_BBPRESS_IS_ACTIVE && is_bbpress() ) ){

		// Single page styling
		wp_enqueue_style( 'tie-css-single' );

		// Print File
		wp_enqueue_style( 'tie-css-print' );
	}
}


/**
 * Demos
 */
 add_filter( 'TieLabs/Demo_Importer/demos_data', 'jannah_demo_importer_data' );
 function jannah_demo_importer_data( $demos_data = false ){

 	if( ! empty( $demos_data ) || tie_get_token() ){
 		return $demos_data;
 	}

 	// --
	$demos = array(
		'demo'           => 'Main Demo',
		'tech'           => 'Tech',
		'sport'          => 'Sport',
		'auto'           => 'Auto',
		'creative'       => 'Creative',
		'foods'          => 'Recipes & Tips',
		'times'          => 'Times',
		'photography'    => 'Photography',
		'hotels'         => 'Hotels',
		'health'         => 'Health',
		'house'          => 'House',
		'videos'         => 'Videos',
		'videos-2'       => 'Videos 2',
		'pets'           => 'Pets',
		'travel'         => 'Travel',
		'traveling'      => 'Traveling',
		'science'        => 'Science',
		'blog'           => 'Personal Blog',
		'minimal-blog'   => 'Minimal Blog',
		'city'           => 'City',
		'school'         => 'school',
		'games'          => 'Games',
		'geo'            => 'Geo',
		'cryptocurrency' => 'Cryptocurrency',
		'salad-dash'     => 'Salad Dash',
		'fitness'        => 'Fitness',
		'seo'            => 'SEO',
	);

	$demos_data = array();

	foreach ( $demos as $slug => $name ) {
		$demos_data[] = array(
			'name' => $name,
			'url'      => 'https://jannah.tielabs.com/'.$slug,
			'img'      => TIELABS_TEMPLATE_URL ."/framework/admin/assets/images/demos-thumbnails/$slug.jpg",
			'desc'     => '',
			'xml'      => '-',
			'settings' => '-',
		);
	}

	return $demos_data;
 }


/**
 * Fallback To Force Show The Plugins Install Page.
 */
 add_filter( 'TieLabs/Plugins_Installer/data', 'jannah_plugins_installer_data' );
 function jannah_plugins_installer_data( $plugins_data = false ){

 	if( ! empty( $plugins_data ) ){
 		return $plugins_data;
 	}

	return array(
		'taqyeem' => array(
			'name'   => 'Taqyeem',
			'slug'   => 'taqyeem-tie_sample',
			'source' => '-',
		),
		'jannah-extensions' => array(
			'name'   => 'Jannah Extensions',
			'slug'   => 'jannah-extensions-tie_sample',
			'source' => '-',
		),
		'arqam-lite' => array(
			'name'   => 'Arqam Lite',
			'slug'   => 'arqam-lite-tie_sample',
			'source' => '-',
		),
		'jannah-switcher' => array(
			'name'   => 'Jannah Switcher',
			'slug'   => 'jannah-switcher-tie_sample',
			'source' => '-',
		),
		'jannah-optimization' => array(
			'name'   => 'Jannah Speed Optimization',
			'slug'   => 'jannah-optimization-tie_sample',
			'source' => '-',
		),
		'tielabs-instagram' => array(
			'name'   => 'TieLabs Instagram Feed',
			'slug'   => 'tielabs-instagram-tie_sample',
			'source' => '-',
		),
		'jannah-autoload-posts' => array(
			'name'   => 'Jannah Autoload Posts',
			'slug'   => 'jannah-autoload-posts-tie_sample',
			'source' => '-',
		),
	);

 }




function tie_inline_componnets_styles( $key, $componnet, $prefix = false ){

	$contents = array(
		'sliders' => array(
			'depends' => '
				.tie-grid-slider{
					.slide{
						display: none;
						grid-template-columns: var(--tie-slider-template-columns);
						grid-gap: var(--tie-slider-grid-gap, 4px);
						height: var(--tie-slider-height, 380px);

						@media (min-width: 992px) {
							.full-width &{
								height: var(--tie-slider-height-full-width, 470px);
							}
						}

						@media (max-width: 767px) {
							grid-template-columns: var(--tie-slider-template-columns-mobile, var(--tie-slider-template-columns) );
							height: var(--tie-slider-height-mobile, 250px);
						}
					}

					.tie-slick-slider{
						&:not(.slick-initialized){
							.tie-slider-nav+.slide,
							.slide:first-child{
								display: grid;
							}
						}

						&.slick-initialized{
							.slide{
								display: grid;
							}
						}
					}

					.grid-item{
						position: relative;
						overflow: hidden;
						background-repeat: no-repeat;
						background-position: center top;
						background-size: cover;
						height: 100%;
					}

					.thumb-overlay,
					.thumb-content{
						padding: 20px 20px 15px;

						@media (max-width: 767px) {
							padding: 10px;
						}
					}

					.thumb-title{
						font-size: 20px;

						@media (max-width: 767px) {
							font-size: 16px;
							white-space: normal;
							display: block;
							display: -webkit-box;
							-webkit-line-clamp: 2;
							-webkit-box-orient: vertical;
							overflow: hidden;
							text-overflow: ellipsis;
							line-height: 1.4;
							max-height: 2.8em;
						}
					}

					.has-builder .has-sidebar & .thumb-desc{
						display: none;
					}

					@media (max-width: 767px) {
						.thumb-meta{
							display: none;
						}
					}
				}
			',
			'9' => '
				.tie-slider-9{
					--tie-slider-template-columns: repeat(2, 1fr);
					--tie-slider-template-columns-mobile: repeat(1, 1fr);
					--tie-slider-height-full-width: 400px;

					.has-builder .has-sidebar & .thumb-desc{
						display: block;
					}
				}
			',
			'10' => '
				.tie-slider-10{
					--tie-slider-template-columns: repeat(3, 1fr);
					--tie-slider-template-columns-mobile: repeat(2, 1fr);
					--tie-slider-height-mobile: 500px;

					.grid-item{
						&:nth-child(1){
							grid-area: 1 / 1 / 3 / 3;

							.thumb-desc{
								opacity: 1;
								max-height: 100px;
								margin-top: 5px;
							}

							@media (min-width: 768px) {
								.thumb-title{
									font-size: 28px;
								}
							}
						}
					}
				}
			',

			'11' => '
				.tie-slider-11{
					--tie-slider-template-columns: repeat(2, 1fr);
					--tie-slider-height-full-width: 500px;
					--tie-slider-height-mobile: 500px;

					.grid-item{
						&:nth-child(odd):last-child{
							grid-area: 2 / 1 / 3 / 3;
						}
					}
				}
			',

			'12' => '
				.tie-slider-12{
					--tie-slider-template-columns: repeat(6, 1fr);
					--tie-slider-template-columns-mobile: repeat(2, 1fr);
					--tie-slider-height-full-width: 500px;
					--tie-slider-height-mobile: 700px;

					.grid-item{

						&:nth-child(1){
							grid-area: 1 / 1 / 3 / 3; 
						}

						@media (min-width: 768px) {
							&:nth-child(1){
								grid-area: 1 / 1 / 2 / 4; 
							}
							&:nth-child(2){
								grid-area: 1 / 4 / 2 / 7;
							}
							&:nth-child(3){
								grid-area: 2 / 1 / 3 / 3;
							}
							&:nth-child(4){
								grid-area: 2 / 3 / 3 / 5;
							}
							&:nth-child(5){
								grid-area: 2 / 5 / 3 / 7;
							}
						}

					}
				}
			',

			'13' => '
				.tie-slider-13{
					--tie-slider-template-columns: repeat(4, 1fr);
					--tie-slider-template-columns-mobile: repeat(2, 1fr);
					--tie-slider-height-mobile: 700px;

					.grid-item{
						&:nth-child(1){
							grid-area: 1 / 2 / 3 / 4; 

							@media (max-width: 768px) {
								grid-area: 1 / 1 / 3 / 3; 
							}

							.thumb-desc{
								opacity: 1;
								max-height: 100px;
								margin-top: 5px;
							}

							@media (min-width: 768px) {
								.thumb-title{
									font-size: 28px;
								}
							}
						}
					}
				}
			',

			'14' => '
				.tie-slider-14{
					--tie-slider-template-columns: repeat(4, 1fr);
					--tie-slider-template-columns-mobile: repeat(2, 1fr);
					--tie-slider-height-mobile: 700px;

					.grid-item{
						&:nth-child(1){
							grid-area: 1 / 1 / 3 / 3; 

							.thumb-desc{
								opacity: 1;
								max-height: 100px;
								margin-top: 5px;
							}

							@media (min-width: 768px) {
								.thumb-title{
									font-size: 28px;
								}
							}
						}
					}
				}
			',

			'15' => '
				.tie-slide-15{
					--tie-slider-template-columns: repeat(3, 1fr);
					--tie-slider-template-columns-mobile: repeat(2, 1fr);
					--tie-slider-height-mobile: 700px;
				}
			',

			'16' => '
				.tie-slider-16{	
					--tie-slider-template-columns: repeat(4, 1fr);
					--tie-slider-template-columns-mobile: repeat(2, 1fr);
					--tie-slider-height-mobile: 500px;

					@media (min-width: 768px) {
						.grid-item{
							&:nth-child(1){
								grid-area: 1 / 1 / 3 / 3;
			
								.thumb-title{
									font-size: 28px;
								}

								.thumb-desc{
									opacity: 1;
									max-height: 100px;
									margin-top: 5px;
								}
							}
							&:nth-child(2){
								grid-area: 1 / 3 / 2 / 5;
							}
							&:nth-child(3){
								grid-area: 2 / 3 / 3 / 4;
							}
							&:nth-child(4){
								grid-area: 2 / 4 / 3 / 5;
							}
						}
					}
				}
			',
			
			'17' => '
				.tie-slider-17{
					--tie-slider-template-columns: repeat(4, 1fr);
					--tie-slider-template-columns-mobile: repeat(2, 1fr);
					--tie-slider-height-mobile: 500px;

					.grid-item{
						&:nth-child(1){
							grid-area: 1 / 1 / 2 / 3;

							.thumb-desc{
								opacity: 1;
								max-height: 100px;
								margin-top: 5px;
							}

							@media (min-width: 768px) {
								.thumb-title{
									font-size: 28px;
								}
							}
						}
					}
				}
			',


		),
		'blocks' => array(

		),
	);



	if( isset( $contents[ $componnet ][ $key ] ) ){

		$data = ! empty( $contents[ $componnet ]['depends'] ) ? $contents[ $componnet ]['depends'] : '';
		$data .= $contents[ $componnet ][ $key ];

		if( $prefix ){
			$data = $prefix .'{'. $data .'}';
		}

		return TIELABS_STYLES::print_inline_css( $data );
	}
}