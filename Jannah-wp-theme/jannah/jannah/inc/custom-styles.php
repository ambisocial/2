<?php
/**
 * Theme Custom Styles
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly


/**
 * Main :Root vars
 */
add_action( 'wp_head', 'jannah_css_root', 3 );
function jannah_css_root(){

	// For Legacy Browsers support
	echo '
		<style type="text/css">
			:root{				
			--tie-preset-gradient-1: linear-gradient(135deg, rgba(6, 147, 227, 1) 0%, rgb(155, 81, 224) 100%);
			--tie-preset-gradient-2: linear-gradient(135deg, rgb(122, 220, 180) 0%, rgb(0, 208, 130) 100%);
			--tie-preset-gradient-3: linear-gradient(135deg, rgba(252, 185, 0, 1) 0%, rgba(255, 105, 0, 1) 100%);
			--tie-preset-gradient-4: linear-gradient(135deg, rgba(255, 105, 0, 1) 0%, rgb(207, 46, 46) 100%);
			--tie-preset-gradient-5: linear-gradient(135deg, rgb(238, 238, 238) 0%, rgb(169, 184, 195) 100%);
			--tie-preset-gradient-6: linear-gradient(135deg, rgb(74, 234, 220) 0%, rgb(151, 120, 209) 20%, rgb(207, 42, 186) 40%, rgb(238, 44, 130) 60%, rgb(251, 105, 98) 80%, rgb(254, 248, 76) 100%);
			--tie-preset-gradient-7: linear-gradient(135deg, rgb(255, 206, 236) 0%, rgb(152, 150, 240) 100%);
			--tie-preset-gradient-8: linear-gradient(135deg, rgb(254, 205, 165) 0%, rgb(254, 45, 45) 50%, rgb(107, 0, 62) 100%);
			--tie-preset-gradient-9: linear-gradient(135deg, rgb(255, 203, 112) 0%, rgb(199, 81, 192) 50%, rgb(65, 88, 208) 100%);
			--tie-preset-gradient-10: linear-gradient(135deg, rgb(255, 245, 203) 0%, rgb(182, 227, 212) 50%, rgb(51, 167, 181) 100%);
			--tie-preset-gradient-11: linear-gradient(135deg, rgb(202, 248, 128) 0%, rgb(113, 206, 126) 100%);
			--tie-preset-gradient-12: linear-gradient(135deg, rgb(2, 3, 129) 0%, rgb(40, 116, 252) 100%);
			--tie-preset-gradient-13: linear-gradient(135deg, #4D34FA, #ad34fa);
			--tie-preset-gradient-14: linear-gradient(135deg, #0057FF, #31B5FF);
			--tie-preset-gradient-15: linear-gradient(135deg, #FF007A, #FF81BD);
			--tie-preset-gradient-16: linear-gradient(135deg, #14111E, #4B4462);
			--tie-preset-gradient-17: linear-gradient(135deg, #F32758, #FFC581);

			';

			if( tie_get_option( 'main_nav_dark' ) ){
				echo '
					--main-nav-background: #1f2024;
					--main-nav-secondry-background: rgba(0,0,0,0.2);
					--main-nav-primary-color: #0088ff;
					--main-nav-contrast-primary-color: #FFFFFF;
					--main-nav-text-color: #FFFFFF;
					--main-nav-secondry-text-color: rgba(225,255,255,0.5);
					--main-nav-main-border-color: rgba(255,255,255,0.07);
					--main-nav-secondry-border-color: rgba(255,255,255,0.04);
				';
			}
			else{
				echo '
					--main-nav-background: #FFFFFF;
					--main-nav-secondry-background: rgba(0,0,0,0.03);
					--main-nav-primary-color: #0088ff;
					--main-nav-contrast-primary-color: #FFFFFF;
					--main-nav-text-color: #2c2f34;
					--main-nav-secondry-text-color: rgba(0,0,0,0.5);
					--main-nav-main-border-color: rgba(0,0,0,0.1);
					--main-nav-secondry-border-color: rgba(0,0,0,0.08);
				';
			}

			// Buttons
			if( $buttons_style = tie_get_option( 'buttons_style', 1 ) ) {
				$button_color = tie_get_option( 'buttons_color' );
				$button_text  = tie_get_option( 'buttons_text_color' );

				if ( $buttons_style == 2 || $buttons_style == 5 ){
					echo '--tie-buttons-radius: 8px;';
				}
				elseif ( $buttons_style == 3 || $buttons_style == 6 ){
					echo '--tie-buttons-radius: 100px;';
				}
	
				if ( $buttons_style < 4  ){
					echo $button_color ? "--tie-buttons-color: $button_color;" : '';
					echo $button_text ? "--tie-buttons-text: $button_text;" : '';
				}
				else{
					echo '--tie-buttons-color: transparent;';
					echo '--tie-buttons-border-width: 1px;';
					echo $button_color ? "--tie-buttons-text: $button_color;" : '';
					echo $button_color ? "--tie-buttons-border-color: $button_color;" : '';
				}

				echo $button_color ? '--tie-buttons-hover-color: '. TIELABS_STYLES::color_brightness( $button_color, -50 ) .';' : '';
				echo $button_color ? '--tie-buttons-hover-text: '. TIELABS_STYLES::light_or_dark( $button_color ) .';' : '';
			}

			echo '
			}
		</style>
	';
}



/*
 * Styles
 */
if( ! function_exists( 'jannah_get_custom_styling' ) ) {

	add_filter( 'TieLabs/CSS/after_theme_color', 'jannah_get_custom_styling' );
	function jannah_get_custom_styling( $out = '' ){

		// Dark Mode Images
		if( tie_get_option( 'dark_skin_image' ) ) {
			$out .="
				.dark-skin .side-aside img,
				.dark-skin .site-footer img,
				.dark-skin .sidebar img,
				.dark-skin .main-content img{
					filter: brightness(.8) contrast(1.2);
				}
			";
		}
		
		// Theme Blocks style
		$block_style = tie_get_option( 'blocks_style', 1 );

		// Slider Background Position
		if( tie_get_option( 'blocks_shadow' ) && tie_get_option( 'boxes_style' ) != 2  ) {
			$out .="
				.tie-weather-widget.widget,
				.container-wrapper{
					box-shadow: 0 5px 15px 0 rgba(0,0,0,0.05);
				}

				.dark-skin .tie-weather-widget.widget,
				.dark-skin .container-wrapper{
					box-shadow: 0 5px 15px 0 rgba(0,0,0,0.2);
				}
			";
		}

		// Slider Background Position
		if( $background_position = tie_get_option( 'background_position' ) ) {
			$out .="
				.main-slider .slide-bg,
				.main-slider .slide{
					background-position: $background_position;
				}
			";
		}

		// Highlighted Color
		if( $color = tie_get_option( 'highlighted_color' ) ) {

			$bright = TIELABS_STYLES::light_or_dark( $color );

			$out .="
				::-moz-selection{
					background-color: $color;
					color: $bright;
				}

				::selection{
					background-color: $color;
					color: $bright;
				}
			";
		}

		// Links Color
		if( $color = tie_get_option( 'links_color' ) ) {
			$out .="
				a,
				body .entry a,
				.dark-skin body .entry a,
				.comment-list .comment-content a{
					color: $color;
				}
			";
		}

		// Links Color Hover
		if( $color = tie_get_option( 'links_color_hover' ) ) {
			$out .="
				a:hover,
				body .entry a:hover,
				.dark-skin body .entry a:hover,
				.comment-list .comment-content a:hover{
					color: $color;
				}
			";
		}

		// Links hover underline
		if( tie_get_option( 'underline_links_hover' ) ) {
			$out .="
				#content a:hover{
					text-decoration: underline !important;
				}
			";
		}

		if( tie_get_option( 'post_title_hover_style' ) == 'modern' ) {
			$out .="
				#tie-container a.post-title:hover,
				#tie-container .post-title a:hover,
				#tie-container .thumb-overlay .thumb-title a:hover{
					background-size: 100% 2px;
					text-decoration: none !important;
				}

				a.post-title,
				.post-title a{
					background-image: linear-gradient(to bottom,#000 0%,#000 98%);
					background-size: 0 1px;
					background-repeat: no-repeat;
					background-position: left 100%;
					color: #000;
				}

				.dark-skin a.post-title,
				.dark-skin .post-title a{
					color: #fff;
					background-image: linear-gradient(to bottom,#fff 0%,#fff 98%);
				}
			";
		}
		

		// Buttons
		if ( tie_get_option( 'buttons_style' ) > 3  ){
			$buttons_classes = ".more-link, .button, [type='submit'], .generic-button a, .generic-button button";
			$out .="
				$buttons_classes{
					background: transparent;
				}
			";
		}
		

		// Theme Main Borders
		if( $color = tie_get_option( 'borders_color' ) ) {

			$out .="
				.container-wrapper,
				.the-global-title,
				.comment-reply-title,
				.tabs,
				.flex-tabs .flexMenu-popup,
				.magazine1 .tabs-vertical .tabs li a,
				.magazine1 .tabs-vertical:after,
				.mag-box .show-more-button,
				.white-bg .social-icons-item a,
				textarea, input, select,
				.toggle,
				.post-content-slideshow,
				.post-content-slideshow .slider-nav-wrapper,
				.share-buttons-bottom,
				.pages-numbers a,
				.pages-nav-item,
				.first-last-pages .pagination-icon,
				.multiple-post-pages .post-page-numbers,
				#story-highlights li,
				.review-item, .review-summary, .user-rate-wrap,
				.review-final-score,
				.tabs a{
					border-color: $color !important;
				}

				.magazine1 .tabs a{
					border-bottom-color: transparent !important;
				}

				.fullwidth-area .tagcloud a:not(:hover){
					background: transparent;
					box-shadow: inset 0 0 0 3px $color;
				}

				.subscribe-widget-content .subscribe-subtitle:after,
				.white-bg .social-icons-item:before{
					background-color: $color !important;
				}
			";

			if ( TIELABS_WOOCOMMERCE_IS_ACTIVE ){
				$out .="
					.related.products > h2,
					.up-sells > h2,
					.cross-sells > h2,
					.cart_totals > h2,
					.comment-text,
					.related.products,
					.up-sells,
					.cart_totals,
					.cross-sells,
					.woocommerce-product-details__short-description,
					.shop_table,
					form.cart,
					.checkout_coupon{
						border-color: $color !important;
					}
				";
			}

			if ( TIELABS_BUDDYPRESS_IS_ACTIVE ){
				$out .="
					.item-options a,
					.ac-textarea,
					.buddypress-header-outer,
					#groups-list > li,
					#member-list > li,
					#members-list > li,
					.generic-button a,
					#profile-edit-form .editfield,
					ul.button-nav,
					ul.button-nav li a{
						border-color: $color !important;
					}
				";
			}

			if ( TIELABS_BBPRESS_IS_ACTIVE ){
				$out .="
					.bbp-form legend,
					ul.topic,
					.bbp-header,
					.bbp-footer,
					.bbp-body .hentry,
					#wp-bbp_reply_content-editor-container{
						border-color: $color !important;
					}
				";
			}
		}



		// Notifications Bar Background
		if( $color = tie_get_option( 'notifications_bar_background' ) ) {
			$out .= "
				#header-notification-bar{
					background: $color;
				}
			";
			
			if( $color_2 = tie_get_option( 'notifications_bar_background_2' ) ) {
				$out .= "
					#header-notification-bar{
						". TIELABS_STYLES::gradiant( $color, $color_2, 90 ) ."
					}
				";
			}
		}
		else if( $gradient = tie_get_option( 'notifications_bar_gradients' ) ){
			$out .= "
				#header-notification-bar{
					background: var( --tie-preset-gradient-$gradient );
				}
			";
		}
		if( $color = tie_get_option( 'notifications_bar_text_color' ) ) {
			$out .= "
				#header-notification-bar, #header-notification-bar p a{
					color: $color;
				}
			";
		}
		if( $color = tie_get_option( 'notifications_bar_buttons_color' ) ) {
			$darker = TIELABS_STYLES::color_brightness( $color, -30 );
			$bright = TIELABS_STYLES::light_or_dark( $darker );
			$out .= "
				#header-notification-bar{
					--tie-buttons-color: $color;
					--tie-buttons-border-color: $color;
					--tie-buttons-hover-color: $darker;
					--tie-buttons-hover-text: $bright;
				}
			";
		}
		if( $color = tie_get_option( 'notifications_bar_buttons_text_color' ) ) {
			$out .= "
				#header-notification-bar{
					--tie-buttons-text: $color;
				}
			";
		}

		
		// Secondry nav Background
		if( $color = tie_get_option( 'secondry_nav_background' ) ) {
			$dark   = TIELABS_STYLES::color_brightness( $color, -30 );
			$darker = TIELABS_STYLES::color_brightness( $color, -50 );
			$bright = TIELABS_STYLES::light_or_dark( $color );

			$out .="
				#top-nav,
				#top-nav .sub-menu,
				#top-nav .comp-sub-menu,
				#top-nav .ticker-content,
				#top-nav .ticker-swipe,
				.top-nav-boxed #top-nav .topbar-wrapper,
				.top-nav-dark .top-menu ul,
				#autocomplete-suggestions.search-in-top-nav{
					background-color : $color;
				}

				#top-nav *,
				#autocomplete-suggestions.search-in-top-nav{
					border-color: rgba( $bright, 0.08);
				}

				#top-nav .icon-basecloud-bg:after{
					color: $color;
				}
			";
		}

		// Secondry nav links
		if( $color = tie_get_option( 'topbar_links_color' ) ) {

			$out .="
				#top-nav a:not(:hover),
				#top-nav input,
				#top-nav #search-submit,
				#top-nav .fa-spinner,
				#top-nav .dropdown-social-icons li a span,
				#top-nav .components > li .social-link:not(:hover) span,
				#autocomplete-suggestions.search-in-top-nav a{
					color: $color;
				}

				#top-nav input::-moz-placeholder{
					color: $color;
				}

				#top-nav input:-moz-placeholder{
					color: $color;
				}

				#top-nav input:-ms-input-placeholder{
					color: $color;
				}

				#top-nav input::-webkit-input-placeholder{
					color: $color;
				}
			";

			/** Google Search */
			if( tie_get_option( 'google_search_engine_id' ) ){

				$out .="
					#top-nav .tie-google-search .gsc-search-box *{
						color: $color !important;
					}
					#top-nav .tie-google-search .gsc-search-button-v2 svg {
						fill: $color !important;
					}
				";
			}

		}

		// Secondry nav links on hover
		if( $color = tie_get_option( 'topbar_links_color_hover' ) ) {

			$darker = TIELABS_STYLES::color_brightness( $color, -30 );
			$bright = TIELABS_STYLES::light_or_dark( $color );

			$out .="

				#top-nav,
				.search-in-top-nav{
					--tie-buttons-color: $color;
					--tie-buttons-border-color: $color;
					--tie-buttons-text: $bright;
					--tie-buttons-hover-color: $darker;
				}

				#top-nav a:hover,
				#top-nav .menu li:hover > a,
				#top-nav .menu > .tie-current-menu > a,
				#top-nav .components > li:hover > a,
				#top-nav .components #search-submit:hover,
				#autocomplete-suggestions.search-in-top-nav .post-title a:hover{
					color: $color;
				}
			";
		}

		// Top-bar text
		if( $color = tie_get_option( 'topbar_text_color' ) ) {

			$rgb = TIELABS_STYLES::rgb_color( $color );

			$out .="
				#top-nav,
				#top-nav .comp-sub-menu,
				#top-nav .tie-weather-widget{
					color: $color;
				}

				#autocomplete-suggestions.search-in-top-nav .post-meta,
				#autocomplete-suggestions.search-in-top-nav .post-meta a:not(:hover){
					color: rgba( $rgb, 0.7 );
				}


				#top-nav .weather-icon .icon-cloud,
				#top-nav .weather-icon .icon-basecloud-bg,
				#top-nav .weather-icon .icon-cloud-behind{
					color: $color !important;
				}
			";
		}

		// Breaking News label
		if( $color = tie_get_option( 'breaking_title_bg' ) ) {

			$bright = TIELABS_STYLES::light_or_dark( $color );

			$out .="
				#top-nav .breaking-title{
					color: $bright;
				}

				#top-nav .breaking-title:before{
					background-color: $color;
				}

				#top-nav .breaking-news-nav li:hover{
					background-color: $color;
					border-color: $color;
				}
			";
		}


		// Main nav Background
		$main_nav_selector = tie_get_option( 'main_nav_layout' ) ? '#main-nav .main-menu-wrapper' : '#main-nav';

		if( tie_get_option( 'header_layout' ) == 1 || tie_get_option( 'header_layout' ) == 4 ){
			$main_nav_selector = '#main-nav';
		}

		if( $color = tie_get_option( 'main_nav_background' ) ) {

			$bright = TIELABS_STYLES::light_or_dark( $color, true );
			$darker = TIELABS_STYLES::color_brightness( $color, -30 );

			// Main nav Gradiant
			if( $color_2 = tie_get_option( 'main_nav_background_2' ) ) {

				$out .= "
					.main-nav-boxed .main-nav.fixed-nav,
					$main_nav_selector{
						". TIELABS_STYLES::gradiant( $color, $color_2, 90 ) ."
					}

					$main_nav_selector .icon-basecloud-bg:after{
						color: inherit !important;
					}
				";

				$color = TIELABS_STYLES::average_color( $color, $color_2 ); // The avaerga color
			}

			$out .="
				$main_nav_selector,
				#main-nav .menu-sub-content,
				#main-nav .comp-sub-menu,
				#main-nav ul.cats-vertical li a.is-active,
				#main-nav ul.cats-vertical li a:hover,
				#autocomplete-suggestions.search-in-main-nav{
					background-color: $color;
				}

				#main-nav{
					border-width: 0;
				}

				#theme-header #main-nav:not(.fixed-nav){
					bottom: 0;
				}

				#main-nav .icon-basecloud-bg:after{
					color: $color;
				}

				#autocomplete-suggestions.search-in-main-nav{
					border-color: rgba($bright, 0.07);
				}

				.main-nav-boxed #main-nav .main-menu-wrapper{
					border-width: 0;
				}
			";
		}
		else if( $gradient = tie_get_option( 'main_nav_pre_gradients' )  ) {
			$out .= "
				.main-nav-boxed .main-nav.fixed-nav,
				$main_nav_selector{
					background: var( --tie-preset-gradient-$gradient );
				}

				$main_nav_selector .icon-basecloud-bg:after{
					color: inherit !important;
				}
			";
		}


		// Main nav links
		if( $color = tie_get_option( 'main_nav_links_color' ) ) {

			$out .= "
				#main-nav a:not(:hover),
				#main-nav a.social-link:not(:hover) span,
				#main-nav .dropdown-social-icons li a span,
				#autocomplete-suggestions.search-in-main-nav a{
					color: $color;
				}
			";

			/** Google Search */
			if( tie_get_option( 'google_search_engine_id' ) ){

				$out .="
					#main-nav .tie-google-search .gsc-search-box *{
						color: $color !important;
					}
					#main-nav .tie-google-search .gsc-search-button-v2 svg {
						fill: $color !important;
					}
				";
			}
						
		}

		// Main nav Borders
		if( tie_get_option( 'main_nav_border_top' ) || tie_get_option( 'main_nav_border_bottom' ) ){

			if( tie_get_option( 'main_nav_border_top_color' ) || tie_get_option( 'main_nav_border_top_width' ) ||
					tie_get_option( 'main_nav_border_bottom_color' ) || tie_get_option( 'main_nav_border_bottom_width' ) ){

				// Top
				$border_top_color = tie_get_option( 'main_nav_border_top_color' ) ? 'border-top-color:'. tie_get_option( 'main_nav_border_top_color' ) .' !important;'   : '';
				$border_top_width = tie_get_option( 'main_nav_border_top_width' ) ? 'border-top-width:'. tie_get_option( 'main_nav_border_top_width' ) .'px !important;' : '';

				// Bottom
				$border_bottom_color = tie_get_option( 'main_nav_border_bottom_color' ) ? 'border-bottom-color:'. tie_get_option( 'main_nav_border_bottom_color' ) .' !important;'   : '';
				$border_bottom_width = tie_get_option( 'main_nav_border_bottom_width' ) ? 'border-bottom-width:'. tie_get_option( 'main_nav_border_bottom_width' ) .'px !important;' : '';

				$out .= "
					#theme-header:not(.main-nav-boxed) #main-nav,
					.main-nav-boxed .main-menu-wrapper{
						$border_top_color
						$border_top_width
						$border_bottom_color
						$border_bottom_width
						border-style: solid;
						border-right: 0 none;
						border-left : 0 none;
					}
				";

				if( tie_get_option( 'main_nav_border_bottom_color' ) || tie_get_option( 'main_nav_border_bottom_width' ) ) {
					$out .= "
						.main-nav-boxed #main-nav.fixed-nav{
							box-shadow: none;
						}
					";
				}
			}
		}

		if( ! tie_get_option( 'main_nav_border_top' ) ){
			$out .= "
				#theme-header:not(.main-nav-boxed) #main-nav,
				.main-nav-boxed .main-menu-wrapper{
					border-right: 0 none !important;
					border-left : 0 none !important;
					border-top : 0 none !important;
				}
			";
		}

		if( ! tie_get_option( 'main_nav_border_bottom' ) ){
			$out .= "
				#theme-header:not(.main-nav-boxed) #main-nav,
				.main-nav-boxed .main-menu-wrapper{
					border-right: 0 none !important;
					border-left : 0 none !important;
					border-bottom : 0 none !important;
				}
			";
		}

		// Main nav links on hover
		if( $color = tie_get_option( 'main_nav_links_color_hover' ) ) {

			$darker = TIELABS_STYLES::color_brightness( $color, -30 );
			$bright = TIELABS_STYLES::light_or_dark( $color );

			$out .= "
				.main-nav,
				.search-in-main-nav{
					--main-nav-primary-color: $color;
					--tie-buttons-color: $color;
					--tie-buttons-border-color: $color;
					--tie-buttons-text: $bright;
					--tie-buttons-hover-color: $darker;
				}

				#main-nav .mega-links-head:after,
				#main-nav .cats-horizontal a.is-active,
				#main-nav .cats-horizontal a:hover,
				#main-nav .spinner > div{
					background-color: $color;
				}

				#main-nav .menu ul li:hover > a,
				#main-nav .menu ul li.current-menu-item:not(.mega-link-column) > a,
				#main-nav .components a:hover,
				#main-nav .components > li:hover > a,
				#main-nav #search-submit:hover,
				#main-nav .cats-vertical a.is-active,
				#main-nav .cats-vertical a:hover,
				#main-nav .mega-menu .post-meta a:hover,
				#main-nav .mega-menu .post-box-title a:hover,
				#autocomplete-suggestions.search-in-main-nav a:hover,
				#main-nav .spinner-circle:after{
					color: $color;
				}

				#main-nav .menu > li.tie-current-menu > a,
				#main-nav .menu > li:hover > a,
				.theme-header #main-nav .mega-menu .cats-horizontal a.is-active,
				.theme-header #main-nav .mega-menu .cats-horizontal a:hover{
					color: $bright;
				}

				#main-nav .menu > li.tie-current-menu > a:before,
				#main-nav .menu > li:hover > a:before{
					border-top-color: $bright;
				}
			";
		}

		// Main Nav text
		if( $color = tie_get_option( 'main_nav_text_color' ) ) {

			$rgb = TIELABS_STYLES::rgb_color( $color );

			$out .="
				#main-nav,
				#main-nav input,
				#main-nav #search-submit,
				#main-nav .fa-spinner,
				#main-nav .comp-sub-menu,
				#main-nav .tie-weather-widget{
					color: $color;
				}

				#main-nav input::-moz-placeholder{
					color: $color;
				}

				#main-nav input:-moz-placeholder{
					color: $color;
				}

				#main-nav input:-ms-input-placeholder{
					color: $color;
				}

				#main-nav input::-webkit-input-placeholder{
					color: $color;
				}

				#main-nav .mega-menu .post-meta,
				#main-nav .mega-menu .post-meta a,
				#autocomplete-suggestions.search-in-main-nav .post-meta{
					color: rgba($rgb, 0.6);
				}

				#main-nav .weather-icon .icon-cloud,
				#main-nav .weather-icon .icon-basecloud-bg,
				#main-nav .weather-icon .icon-cloud-behind{
					color: $color !important;
				}
			";
		}




		// In Post links
		if( tie_get_option( 'post_links_color' ) ) {
			$out .='
			#the-post .entry-content a:not(.shortc-button){
				color: '. tie_get_option( 'post_links_color' ) .' !important;
			}';
		}

		if( tie_get_option( 'post_links_color_hover' ) ) {
			$out .='
			#the-post .entry-content a:not(.shortc-button):hover{
				color: '. tie_get_option( 'post_links_color_hover' ) .' !important;
			}';
		}


		// Widget head color
		if( $color = tie_get_option( 'widgets_head_main_color' ) ) {

			switch ( $block_style ) {

				case 1:
					$out .="
						#tie-body .sidebar .widget-title:after{
							background-color: $color;
						}
						#tie-body .sidebar .widget-title:before{
							border-top-color: $color;
						}";
					break;

				case 3:
				case 10:
					$out .="
						#tie-body .sidebar .widget-title:after{
							background-color: $color;
						}";
					break;

				case 2:
					$out .="
						#tie-body .sidebar .widget-title{
							border-color: $color;
							color: $color;
						}";
					break;

				case 4:
				case 5:
				case 8:
					$out .="
						#tie-body .sidebar .widget-title:before{
							background-color: $color;
						}";
					break;

				case 6:
					$out .="
						#tie-body .sidebar .widget-title:before,
						#tie-body .sidebar .widget-title:after{
							background-color: $color;
						}";
					break;

				case 7:
					$out .="
						#tie-body .sidebar .widget-title{
							background-color: $color;
						}";
					break;

				case 11:
					$direction = is_rtl() ? 'right' : 'left';

					$out .="
						#tie-body .sidebar .widget-title:after{
							border-$direction-color: $color;
						}";
					break;
			}
		}


		// Backgrounds
		$backround_areas = array(
			'main_content_bg'      => '#tie-container #tie-wrapper, .post-layout-8 #content', // in post-layout-8 tie-wrapper will be transparent so, the #content area,
			'footer_background'    => '#footer',
			'copyright_background' => '#site-info',
			'banner_bg'            => '#background-stream-cover',
			'mobile_header_bg'     => '',
		);

		$header_layout = tie_get_option( 'header_layout' );

		if( $header_layout != 1 && $header_layout != 4 ){
			$backround_areas['header_background'] = '#tie-wrapper #theme-header';
		}

		foreach ( $backround_areas as $area => $elements ){

			if( tie_get_option( $area . '_color' ) || tie_get_option( $area . '_img' ) ){

				$background_color = tie_get_option( $area . '_color' ) ? 'background-color: '. tie_get_option( $area . '_color' ) .';' : '';
				$background_image = tie_get_option( $area . '_img' );

				# Background Image
				$background_image = TIELABS_STYLES::bg_image_css( $background_image );

				if( ! empty( $background_color ) || ! empty( $background_image ) ){

					if( $area == 'mobile_header_bg'  ){

						$out .='
							@media (max-width: 991px) {
								#tie-wrapper #theme-header,
								#tie-wrapper #theme-header #main-nav .main-menu-wrapper,
								#tie-wrapper #theme-header .logo-container{
									background: transparent;
								}';

						// Gradiant
						if( tie_get_option( 'mobile_header_bg_color_2' ) && empty( $background_image ) ) {
							$out .= "
								#tie-wrapper #theme-header .logo-container,
								#tie-wrapper #theme-header #main-nav {
									". TIELABS_STYLES::gradiant( tie_get_option( 'mobile_header_bg_color' ), tie_get_option( 'mobile_header_bg_color_2' ), 90 ) ."
								}
								#mobile-header-components-area_1 .components .comp-sub-menu{
									background-color: ". tie_get_option( 'mobile_header_bg_color' ) .";
								}
								#mobile-header-components-area_2 .components .comp-sub-menu{
									background-color: ". tie_get_option( 'mobile_header_bg_color_2' ) .";
								}
							";
						}
						else{
							$out .='
								#tie-wrapper #theme-header .logo-container,
								#tie-wrapper #theme-header .logo-container.fixed-nav,
								#tie-wrapper #theme-header #main-nav {
									'. $background_color .'
									'. $background_image .'
								}

								.mobile-header-components .components .comp-sub-menu{
									'. $background_color .'
								}
							';
						}

						$out .='}';

					}


					# Header Related Colors
					elseif( $area == 'header_background' && ( tie_get_option( 'header_layout' ) == 1 || tie_get_option( 'header_layout' ) == 2 ) ){

						$out .=
							$elements .'{
								'. $background_color .'
								'. $background_image .'
							}
						';

						// Text Site Title color
						if( tie_get_option( $area . '_color' ) ){

							$out .='
								#logo.text-logo a,
								#logo.text-logo a:hover{
									color: '. TIELABS_STYLES::light_or_dark( tie_get_option( $area . '_color' ) ) .';
								}

								@media (max-width: 991px){
									#tie-wrapper #theme-header .logo-container.fixed-nav{
										background-color: rgba('. TIELABS_STYLES::rgb_color(tie_get_option( $area . '_color' )) .', 0.95);
									}
								}
							';
						}

						// Gradiant
						if( tie_get_option( 'header_background_color_2' ) && empty( $background_image ) ) {
							$out .= "
								$elements{
									". TIELABS_STYLES::gradiant( tie_get_option( 'header_background_color' ), tie_get_option( 'header_background_color_2' ), 90 ) ."
								}
							";
						}

						/*$out .='
							@media (max-width: 991px){
								#tie-wrapper #theme-header .logo-container{
								'. $background_color .'
								'. $background_image .'
								}
							}
						';*/

					} // Header Custom Colors

					else{

						$out .=
							$elements .'{
								'. $background_color .'
								'. $background_image .'
							}
						';

					} // else

				}

			}
		}





		// Footer area
		if( tie_get_option( 'footer_margin_top' ) || tie_get_option( 'footer_padding_bottom' ) ){

			$footer_margin_top     = tie_get_option( 'footer_margin_top' ) ?     'margin-top: '.     tie_get_option( 'footer_margin_top' )     .'px;' : '';
			$footer_padding_bottom = tie_get_option( 'footer_padding_bottom' ) ? 'padding-bottom: '. tie_get_option( 'footer_padding_bottom' ) .'px;' : ''; // Asking why? check the School Demo :)

			$out .="
				#footer{
					$footer_margin_top
					$footer_padding_bottom
				}
			";
		}

		if( tie_get_option( 'footer_padding_top' )  ){
			$out .='
				#footer .footer-widget-area:first-child{
					padding-top: '. tie_get_option( 'footer_padding_top' ) .'px;
				}
			';
		}

		if( $color = tie_get_option( 'footer_background_color' ) ) {
			$rgb    = TIELABS_STYLES::rgb_color( $color );
			$darker = TIELABS_STYLES::color_brightness( $color, -30 );
			$bright = TIELABS_STYLES::light_or_dark( $color, true );

			$out .="
				#footer .posts-list-counter .posts-list-items li.widget-post-list:before{
					border-color: $color;
				}

				#footer .timeline-widget a .date:before{
					border-color: rgba($rgb, 0.8);
				}

				#footer .footer-boxed-widget-area,
				#footer textarea,
				#footer input:not([type=submit]),
				#footer select,
				#footer code,
				#footer kbd,
				#footer pre,
				#footer samp,
				#footer .show-more-button,
				#footer .slider-links .tie-slider-nav span,
				#footer #wp-calendar,
				#footer #wp-calendar tbody td,
				#footer #wp-calendar thead th,
				#footer .widget.buddypress .item-options a{
					border-color: rgba($bright, 0.1);
				}

				#footer .social-statistics-widget .white-bg li.social-icons-item a,
				#footer .widget_tag_cloud .tagcloud a,
				#footer .latest-tweets-widget .slider-links .tie-slider-nav span,
				#footer .widget_layered_nav_filters a{
						border-color: rgba($bright, 0.1);
				}

				#footer .social-statistics-widget .white-bg li:before{
					background: rgba($bright, 0.1);
				}

				.site-footer #wp-calendar tbody td{
					background: rgba($bright, 0.02);
				}

				#footer .white-bg .social-icons-item a span.followers span,
				#footer .circle-three-cols .social-icons-item a .followers-num,
				#footer .circle-three-cols .social-icons-item a .followers-name{
					color: rgba($bright, 0.8);
				}

				#footer .timeline-widget ul:before,
				#footer .timeline-widget a:not(:hover) .date:before{
					background-color: $darker;
				}
			";
		}

		if( $color = tie_get_option( 'footer_widgets_head_color' ) ) {

			switch ( $block_style ) {

				case 1:
				case 2:
				case 3:
				case 10:
					$out .="
						#tie-body #footer .widget-title::after{
							background-color: $color;
						}";
					break;

				case 4:
				case 5:
				case 8:
					$out .="
						#tie-body #footer .widget-title::before{
							background-color: $color;
						}";
					break;

				case 6:
					$out .="
						#tie-body #footer .widget-title::before,
						#tie-body #footer .widget-title::after{
							background-color: $color;
						}";
					break;

				case 7:
					$out .="
						#tie-body #footer .widget-title{
							background-color: $color;
						}";
					break;

				case 11:
					$direction = is_rtl() ? 'right' : 'left';

					$out .="
						#tie-body #footer .widget-title:after{
							border-$direction-color: $color;
						}";
					break;
			}
		}

		if( tie_get_option( 'footer_title_color' ) ) {
			$out .='
				#footer .widget-title,
				#footer .widget-title a:not(:hover){
					color: '. tie_get_option( 'footer_title_color' ) .';
				}
			';
		}

		if( $color = tie_get_option( 'footer_text_color' ) ) {
			$rgb = TIELABS_STYLES::rgb_color( $color );

			$out .="
				#footer,
				#footer textarea,
				#footer input:not([type='submit']),
				#footer select,
				#footer #wp-calendar tbody,
				#footer .tie-slider-nav li span:not(:hover),

				#footer .widget_categories li a:before,
				#footer .widget_product_categories li a:before,
				#footer .widget_layered_nav li a:before,
				#footer .widget_archive li a:before,
				#footer .widget_nav_menu li a:before,
				#footer .widget_meta li a:before,
				#footer .widget_pages li a:before,
				#footer .widget_recent_entries li a:before,
				#footer .widget_display_forums li a:before,
				#footer .widget_display_views li a:before,
				#footer .widget_rss li a:before,
				#footer .widget_display_stats dt:before,

				#footer .subscribe-widget-content h3,
				#footer .about-author .social-icons a:not(:hover) span{
					color: $color;
				}

				#footer post-widget-body .meta-item,
				#footer .post-meta,
				#footer .stream-title,
				#footer.dark-skin .timeline-widget .date,
				#footer .wp-caption .wp-caption-text,
				#footer .rss-date{
					color: rgba($rgb, 0.7);
				}

				#footer input::-moz-placeholder{
					color: $color;
				}

				#footer input:-moz-placeholder{
					color: $color;
				}

				#footer input:-ms-input-placeholder{
					color: $color;
				}

				#footer input::-webkit-input-placeholder{
					color: $color;
				}
			";
		}

		if( tie_get_option( 'footer_links_color' ) ) {
			$out .='
				.site-footer.dark-skin a:not(:hover){
					color: '. tie_get_option( 'footer_links_color' ) .';
				}
			';
		}

		if( $color = tie_get_option( 'footer_links_color_hover' ) ) {

			$darker = TIELABS_STYLES::color_brightness( $color, -30 );
			$bright = TIELABS_STYLES::light_or_dark( $color );

			$out .="
				.site-footer.dark-skin a:hover,
				#footer .stars-rating-active,
				#footer .twitter-icon-wrap span,
				.block-head-4.magazine2 #footer .tabs li a{
					color: $color;
				}

				#footer .circle_bar{
					stroke: $color;
				}

				#footer .widget.buddypress .item-options a.selected,
				#footer .widget.buddypress .item-options a.loading,
				#footer .tie-slider-nav span:hover,
				.block-head-4.magazine2 #footer .tabs{
					border-color: $color;
				}

				.magazine2:not(.block-head-4) #footer .tabs a:hover,
				.magazine2:not(.block-head-4) #footer .tabs .active a,
				.magazine1 #footer .tabs a:hover,
				.magazine1 #footer .tabs .active a,
				.block-head-4.magazine2 #footer .tabs.tabs .active a,
				.block-head-4.magazine2 #footer .tabs > .active a:before,
				.block-head-4.magazine2 #footer .tabs > li.active:nth-child(n) a:after,

				#footer .digital-rating-static,
				#footer .timeline-widget li a:hover .date:before,
				#footer #wp-calendar #today,
				#footer .posts-list-counter .posts-list-items li.widget-post-list:before,
				#footer .cat-counter span,
				#footer.dark-skin .the-global-title:after,
				#footer .button,
				#footer [type='submit'],
				#footer .spinner > div,

				#footer .widget.buddypress .item-options a.selected,
				#footer .widget.buddypress .item-options a.loading,
				#footer .tie-slider-nav span:hover,
				#footer .fullwidth-area .tagcloud a:hover{
					background-color: $color;
					color: $bright;
				}

				.block-head-4.magazine2 #footer .tabs li a:hover{
					color: $darker;
				}

				.block-head-4.magazine2 #footer .tabs.tabs .active a:hover,
				#footer .widget.buddypress .item-options a.selected,
				#footer .widget.buddypress .item-options a.loading,
				#footer .tie-slider-nav span:hover{
					color: $bright !important;
				}

				#footer .button:hover,
				#footer [type='submit']:hover{
					background-color: $darker;
					color: $bright;
				}
			";
		}


		// Quote Styles
		if( $color = tie_get_option( 'quote_bg' ) ) {
			$out .='
			blockquote.quote-light,
			blockquote.quote-simple,
			q,
			blockquote{
				background: '. $color .';
			}';
		}

		if( $color = tie_get_option( 'quote_primary_color' ) ) {
			$out .='
			q cite,
			blockquote cite,
			q:before, blockquote:before,
			.wp-block-quote cite,
			.wp-block-quote footer{
				color: '. $color .';
			}
			blockquote.quote-light{
				border-color: '. $color .';
			}
			';
		}

		if( $color = tie_get_option( 'quote_text_color' ) ) {
			$out .='
			blockquote.quote-light,
			blockquote.quote-simple,
			q,
			blockquote{
				color: '. $color .';
			}';
		}


		// Copyright area
		if( tie_get_option( 'copyright_text_color' ) ) {
			$out .='
			#site-info,
			#site-info ul.social-icons li a:not(:hover) span{
				color: '. tie_get_option( 'copyright_text_color' ) .';
			}';
		}

		if( tie_get_option( 'copyright_links_color' ) ) {
			$out .='
			#footer .site-info a:not(:hover){
				color: '. tie_get_option( 'copyright_links_color' ) .';
			}';
		}

		if( tie_get_option( 'copyright_links_color_hover' ) ) {
			$out .='
			#footer .site-info a:hover{
				color: '. tie_get_option( 'copyright_links_color_hover' ) .';
			}
			';
		}


		// Go to Top Button
		if( tie_get_option( 'back_top_background_color' ) ) {
			$out .='
				a#go-to-top{
					background-color: '. tie_get_option( 'back_top_background_color' ) .';
				}';
		}

		if( tie_get_option( 'back_top_text_color' ) ) {
			$out .='
				a#go-to-top{
					color: '. tie_get_option( 'back_top_text_color' ) .';
				}';
		}


		// AdBlock Popup
		if( $color = tie_get_option( 'adblock_background' ) ) {

			$bright = TIELABS_STYLES::light_or_dark( $color );

			$out .='
				#tie-popup-adblock .container-wrapper{
					background-color: '. tie_get_option( 'adblock_background' ) .' !important;
					color: '. $bright .';
				}
				#tie-popup-adblock .container-wrapper .tie-btn-close:before{
					color: '. $bright .';
				}
			';
		}


		// Custom Social Networks colors
		for( $i=1 ; $i<=5 ; $i++ ){
			if ( tie_get_option( "custom_social_title_$i" ) && ( tie_get_option( "custom_social_icon_img_$i" ) || tie_get_option( "custom_social_icon_$i" ) ) && tie_get_option( "custom_social_url_$i" ) ) {

				$color = tie_get_option( "custom_social_color_$i", '#333' );

				$out .="
					.social-icons-item .custom-link-$i-social-icon{
						background-color: $color !important;
					}

					.social-icons-item .custom-link-$i-social-icon span{
						color: $color;
					}
				";

				if( tie_get_option( "custom_social_icon_img_$i" ) ){
					$out .="
						.social-icons-item .custom-link-$i-social-icon.custom-social-img span.social-icon-img{
							background-image: url('". tie_get_option( "custom_social_icon_img_$i" ) ."');
						}
					";
				}
			}
		}


		// Colored Categories labels
		if( $cat_custom_color = tie_get_option( 'category_label_color' ) ){
			
			$out .='
				.post-cat{
					background-color:'. $cat_custom_color .' !important;
					color:'. TIELABS_STYLES::light_or_dark( $cat_custom_color ) .' !important;
				}
			';
		}
		else{

			$cats_options = get_option( 'tie_cats_options' );

			if( ! empty( $cats_options ) && is_array( $cats_options ) ) {
				foreach ( $cats_options as $cat => $options){
					if( ! empty( $options['cat_color'] ) ) {

						$cat_custom_color = $options['cat_color'];
						$bright_color = TIELABS_STYLES::light_or_dark( $cat_custom_color );

						$out .='
							.tie-cat-'.$cat.',
							.tie-cat-item-'.$cat.' > span{
								background-color:'. $cat_custom_color .' !important;
								color:'. $bright_color .' !important;
							}

							.tie-cat-'.$cat.':after{
								border-top-color:'. $cat_custom_color .' !important;
							}
							.tie-cat-'.$cat.':hover{
								background-color:'. TIELABS_STYLES::color_brightness( $cat_custom_color ) .' !important;
							}

							.tie-cat-'.$cat.':hover:after{
								border-top-color:'. TIELABS_STYLES::color_brightness( $cat_custom_color ) .' !important;
							}
						';
					}
				}
			}
		}


		// Arqam Plugin Custom colors
		if( TIELABS_ARQAM_IS_ACTIVE ){
			$arqam_options = get_option( 'arq_options' );
			if( ! empty( $arqam_options['color'] ) && is_array( $arqam_options['color'] ) ) {
				foreach ( $arqam_options['color'] as $social => $color ){
					if( ! empty( $color ) ) {
						if( $social == '500px' ){
							$social = 'px500';
						}
						$out .= "
							.social-statistics-widget .solid-social-icons .social-icons-item .$social-social-icon{
								background-color: $color !important;
								border-color: $color !important;
							}
							.social-statistics-widget .$social-social-icon span.counter-icon{
								background-color: $color !important;
							}
						";
					}
				}
			}
		}


		// Take Over Ad top margin
		if( tie_get_option( 'banner_bg' ) && tie_get_option( 'banner_bg_url' ) && tie_get_option( 'banner_bg_site_margin' ) && ! tie_is_auto_loaded_post() ){
			$out .= '
				@media (min-width: 992px){
					#tie-wrapper{
						margin-top: '. tie_get_option( 'banner_bg_site_margin' ) .'px !important;
					}
				}
			';
		}


		// Site Width
		if( tie_get_option( 'site_width' ) && tie_get_option( 'site_width' ) != '1200px' ){
			$out .= '
				@media (min-width: 1200px){
				.container{
						width: auto;
					}
				}
			';

			if( strpos( tie_get_option( 'site_width' ), '%' ) !== false ){
				$out .= '
					@media (min-width: 992px){
						.container,
						.boxed-layout #tie-wrapper,
						.boxed-layout .fixed-nav,
						.wide-next-prev-slider-wrapper .slider-main-container{
							max-width: '.tie_get_option( 'site_width' ).';
						}
						.boxed-layout .container{
							max-width: 100%;
						}
					}
				';
			}
			else{
				$outer_width = str_replace( 'px', '', tie_get_option( 'site_width' ) ) + 30;
				$out .= '
					.boxed-layout #tie-wrapper,
					.boxed-layout .fixed-nav{
						max-width: '.  $outer_width .'px;
					}
					@media (min-width: '.tie_get_option( 'site_width' ).'){
						.container,
						.wide-next-prev-slider-wrapper .slider-main-container{
							max-width: '.tie_get_option( 'site_width' ).';
						}
					}
				';
			}
		}

		// Sidebar Width
		if( tie_get_option( 'sidebar_width' ) ){

			$sidebar_width = (int) tie_get_option( 'sidebar_width' );
			$sidebar_width = min( 85, max( 15, $sidebar_width ) );

			$out .= '
				@media (min-width: 992px){
					.sidebar{
						width: '. $sidebar_width .'%;
					}
					.main-content{
						width: '. ( 100 - $sidebar_width ) .'%;
					}
				}
			';
		}


		// Post Views icon
		if( tie_get_option( 'views_icon' ) == 1 ){
			$out .= '
				.meta-views.meta-item .tie-icon-fire:before{
					content: "\f06e" !important;
				}
			';
		}

		// Sticky Share break point
		if( tie_get_option( 'share_post_sticky' ) ){
			$sticky_break_point = tie_get_option( 'share_breakpoint_sticky', '1250' );
			$out .='
				@media (max-width: '. $sticky_break_point .'px){
					.share-buttons-sticky{
						display: none;
					}
				}
			';
		}

		// Mobile Menu Background
		if( tie_get_option( 'mobile_header_components_menu' ) ){

			if( tie_get_option( 'mobile_menu_background_type' ) == 'color' ){
				if( tie_get_option( 'mobile_menu_background_color' ) ){
					$mobile_bg = 'background-color: '. tie_get_option( 'mobile_menu_background_color' ) .';';
					$out .='
						@media (max-width: 991px){
							.side-aside #mobile-menu .menu > li{
								border-color: rgba('.TIELABS_STYLES::light_or_dark( tie_get_option( 'mobile_menu_background_color' ), true ).',0.05);
							}
						}
					';
				}
			}

			elseif( tie_get_option( 'mobile_menu_background_type' ) == 'gradient' ){
				if( tie_get_option( 'mobile_menu_background_gradient_color_1' ) &&  tie_get_option( 'mobile_menu_background_gradient_color_2' ) ){
					$color1 = tie_get_option( 'mobile_menu_background_gradient_color_1' );
					$color2 = tie_get_option( 'mobile_menu_background_gradient_color_2' );

					$mobile_bg = TIELABS_STYLES::gradiant( $color1, $color2 );
				}
			}

			elseif ( tie_get_option( 'mobile_menu_background_type' ) == 'image' ){
				if( tie_get_option( 'mobile_menu_background_image' ) ){
					$background_image = tie_get_option( 'mobile_menu_background_image' );
					$mobile_bg = TIELABS_STYLES::bg_image_css( $background_image );
				}
			}


			if( ! empty( $mobile_bg ) ){
				$out .='
					@media (max-width: 991px){
						.side-aside.normal-side{
							'.$mobile_bg.'
						}
					}
				';
			}

			if( tie_get_option( 'mobile_menu_text_color' ) ){
				$out .='
					.side-aside #mobile-menu li a,
					.side-aside #mobile-menu .mobile-arrows,
					.side-aside #mobile-search .search-field{
						color: '. tie_get_option( 'mobile_menu_text_color' ) .';
					}

					#mobile-search .search-field::-moz-placeholder {
						color: '. tie_get_option( 'mobile_menu_text_color' ) .';
					}

					#mobile-search .search-field:-moz-placeholder {
						color: '. tie_get_option( 'mobile_menu_text_color' ) .';
					}

					#mobile-search .search-field:-ms-input-placeholder {
						color: '. tie_get_option( 'mobile_menu_text_color' ) .';
					}

					#mobile-search .search-field::-webkit-input-placeholder {
						color: '. tie_get_option( 'mobile_menu_text_color' ) .';
					}

					@media (max-width: 991px){
						.tie-btn-close span{
							color: '. tie_get_option( 'mobile_menu_text_color' ) .';
						}
					}
				';
			}

			if( tie_get_option( 'mobile_menu_social_color' ) ){
				$out .='
					.side-aside.dark-skin a.remove:not(:hover)::before,
					#mobile-social-icons .social-icons-item a:not(:hover) span{
						color: '. tie_get_option( 'mobile_menu_social_color' ) .'!important;
					}
				';
			}

			/*
			if( tie_get_option( 'mobile_menu_search_color' ) ){
				$search_color = tie_get_option( 'mobile_menu_search_color' );
				$out .='
					#mobile-search .search-submit{
						background-color: '. $search_color .';
						color: '.TIELABS_STYLES::light_or_dark( $search_color ).';
					}

					#mobile-search .search-submit:hover{
						background-color: '. TIELABS_STYLES::color_brightness( $search_color ) .';
					}
				';
			}
			*/
		}


		if( tie_get_option( 'mobile_menu_icon_color' ) ){
			$out .='
				.mobile-header-components li.custom-menu-link > a,
				#mobile-menu-icon .menu-text{
					color: '. tie_get_option( 'mobile_menu_icon_color' ) .'!important;
				}

				#mobile-menu-icon .nav-icon,
				#mobile-menu-icon .nav-icon:before,
				#mobile-menu-icon .nav-icon:after{
					background-color: '. tie_get_option( 'mobile_menu_icon_color' ) .'!important;
				}
			';
		}

		// Mobile Logo Width
		if( tie_get_option( 'mobile_logo_width' ) ){
			$out .='
				@media (max-width: 991px){
					#theme-header.has-normal-width-logo #logo img {
						width:'. tie_get_option( 'mobile_logo_width' ) .'px !important;
						max-width:100% !important;
						height: auto !important;
						max-height: 200px !important;
					}
				}
			';
		}

		// Instagram Plugin
		if( TIELABS_INSTAGRAM_FEED_IS_ACTIVE ){
			$out .='
				.tie-insta-header {
					margin-bottom: 15px;
				}
				
				.tie-insta-avatar a {
					width: 70px;
					height: 70px;
					display: block;
					position: relative;
					float: left;
					margin-right: 15px;
					margin-bottom: 15px;
				}
				
				.tie-insta-avatar a:before {
					content: "";
					position: absolute;
					width: calc(100% + 6px);
					height: calc(100% + 6px);
					left: -3px;
					top: -3px;
					border-radius: 50%;
					background: #d6249f;
					background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);
				}
				
				.tie-insta-avatar a:after {
					position: absolute;
					content: "";
					width: calc(100% + 3px);
					height: calc(100% + 3px);
					left: -2px;
					top: -2px;
					border-radius: 50%;
					background: #fff;
				}
				
				.dark-skin .tie-insta-avatar a:after {
					background: #27292d;
				}
				
				.tie-insta-avatar img {
					border-radius: 50%;
					position: relative;
					z-index: 2;
					transition: all 0.25s;
				}
				
				.tie-insta-avatar img:hover {
					box-shadow: 0px 0px 15px 0 #6b54c6;
				}
				
				.tie-insta-info {
					font-size: 1.3em;
					font-weight: bold;
					margin-bottom: 5px;
				}
			';
		}

		// TikTok Plugin
		if( defined( 'QLTTF_PLUGIN_NAME' ) ){
			$out .='
				.tie-tiktok-header{
					overflow: hidden;
					margin-bottom: 10px;
				}

				.tie-tiktok-avatar a {
					width: 70px;
					height: 70px;
					display: block;
					position: relative;
					float: left;
					margin-right: 15px;
				}

				.tie-tiktok-avatar img {
					border-radius: 50%;
				}

				.tie-tiktok-username {
					display: block;
					font-size: 1.4em;
				}

				.tie-tiktok-desc {
					margin-top: 8px;
				}
			';
		}

		// Web Stories Plugin
		if( TIELABS_WEBSTORIES_IS_ACTIVE ){
			$out .='
				.web-stories-list{
					position: relative;
					z-index: 1;
				}

				.mag-box .web-stories-list {
					margin-bottom: 10px;
					margin-top: 10px;
				}

				.web-stories-list__story-poster:after {
					transition: opacity 0.2s;
				}

				.web-stories-list__story:hover .web-stories-list__story-poster:after {
					opacity: 0.6;
				}
				
				.web-stories-list.is-view-type-carousel .web-stories-list__story,
				.web-stories-list.is-view-type-grid .web-stories-list__story{
					min-width: 0 !important;
				}

				.is-view-type-circles.is-carousel .web-stories-list__inner-wrapper .web-stories-list__story:not(.visible){
					height: var(--ws-circle-size);
					overflow: hidden;
				}

				.is-view-type-list .web-stories-list__inner-wrapper{
					display: flex;
					flex-wrap: wrap;
				}

				.is-view-type-list .web-stories-list__inner-wrapper > * {
					flex: 0 0 49%;
					margin: 0 0.5%;
				}
				
				@media (min-width: 676px) {
					.is-view-type-carousel .web-stories-list__carousel:not(.glider){
						height: 277px;
					}
				}
			';

			if( is_rtl() ){
				$out .='
					.web-stories-list__lightbox-wrapper,
					.i-amphtml-story-player-main-container{
						direction: ltr;
					}
				';
			}
		}


		// Website URL field disabled
		if( tie_get_option( 'comments_disable_url' ) ){
			$out .='
				#respond .comment-form-email {
					width: 100% !important;
					float: none !important;
				}
			';
		}


		return $out;

	}
}


/**
 * Rounded Layout
 */
if( ! function_exists( 'jannah_rounded_blocks_css' ) ) {

	add_filter( 'TieLabs/CSS/after_theme_color', 'jannah_rounded_blocks_css' );
	function jannah_rounded_blocks_css( $out = '' ){

		if( tie_get_option( 'boxes_style' ) == 3 ){

			$rounded = apply_filters( 'TieLabs/Blocks_Layout/Rounded', '15' );

			$right = ! is_rtl() ? 'right' : 'left';
			$left  = ! is_rtl() ? 'left'  : 'right';

			if( ! empty( $rounded ) ){
				$rounded .= 'px';

				$out .= "
					/*body input:not([type='checkbox']):not([type='radio']),*/
					body .mag-box .breaking,
					body .social-icons-widget .social-icons-item .social-link,
					body .widget_product_tag_cloud a,
					body .widget_tag_cloud a,
					body .post-tags a,
					body .widget_layered_nav_filters a,
					body .post-bottom-meta-title,
					body .post-bottom-meta a,
					body .post-cat,
					body .show-more-button,
					body #instagram-link.is-expanded .follow-button,
					body .cat-counter a + span,
					body .mag-box-options .slider-arrow-nav a,
					body .main-menu .cats-horizontal li a,
					body #instagram-link.is-compact,
					body .pages-numbers a,
					body .pages-nav-item,
					body .bp-pagination-links .page-numbers,
					body .fullwidth-area .widget_tag_cloud .tagcloud a,
					body ul.breaking-news-nav li.jnt-prev,
					body ul.breaking-news-nav li.jnt-next,
					body #tie-popup-search-mobile table.gsc-search-box{
						border-radius: 35px;
					}

					body .mag-box ul.breaking-news-nav li{
						border: 0 !important;
					}

					body #instagram-link.is-compact{
						padding-right: 40px;
						padding-left: 40px;
					}

					body .post-bottom-meta-title,
					body .post-bottom-meta a,
					body .more-link{
						padding-right: 15px;
						padding-left: 15px;
					}

					body #masonry-grid .container-wrapper .post-thumb img{
						border-radius: 0px;
					}

					body .video-thumbnail,
					body .review-item,
					body .review-summary,
					body .user-rate-wrap,
					body textarea,
					body input,
					body select{
						border-radius: 5px;
					}

					body .post-content-slideshow,
					body #tie-read-next,
					body .prev-next-post-nav .post-thumb,
					body .post-thumb img,
					body .container-wrapper,
					body .tie-popup-container .container-wrapper,
					body .widget,
					body .tie-grid-slider .grid-item,
					body .slider-vertical-navigation .slide,
					body .boxed-slider:not(.tie-grid-slider) .slide,
					body .buddypress-wrap .activity-list .load-more a,
					body .buddypress-wrap .activity-list .load-newest a,
					body .woocommerce .products .product .product-img img,
					body .woocommerce .products .product .product-img,
					body .woocommerce .woocommerce-tabs,
					body .woocommerce div.product .related.products,
					body .woocommerce div.product .up-sells.products,
					body .woocommerce .cart_totals, .woocommerce .cross-sells,
					body .big-thumb-left-box-inner,
					body .miscellaneous-box .posts-items li:first-child,
					body .single-big-img,
					body .masonry-with-spaces .container-wrapper .slide,
					body .news-gallery-items li .post-thumb,
					body .scroll-2-box .slide,
					.magazine1.archive:not(.bbpress) .entry-header-outer,
					.magazine1.search .entry-header-outer,
					.magazine1.archive:not(.bbpress) .mag-box .container-wrapper,
					.magazine1.search .mag-box .container-wrapper,
					body.magazine1 .entry-header-outer + .mag-box,
					body .digital-rating-static,
					body .entry q,
					body .entry blockquote,
					body #instagram-link.is-expanded,
					body.single-post .featured-area,
					body.post-layout-8 #content,
					body .footer-boxed-widget-area,
					body .tie-video-main-slider,
					body .post-thumb-overlay,
					body .widget_media_image img,
					body .stream-item-mag img,
					body .media-page-layout .post-element{
						border-radius: {$rounded};
					}

					#subcategories-section .container-wrapper{
						border-radius: {$rounded} !important;
						margin-top: 15px !important;
						border-top-width: 1px !important;
					}

					@media (max-width: 767px) {
						.tie-video-main-slider iframe{
							border-top-right-radius: {$rounded};
							border-top-left-radius: {$rounded};
						}
					}

					.magazine1.archive:not(.bbpress) .mag-box .container-wrapper,
					.magazine1.search .mag-box .container-wrapper{
						margin-top: 15px;
						border-top-width: 1px;
					}

					body .section-wrapper:not(.container-full) .wide-slider-wrapper .slider-main-container,
					body .section-wrapper:not(.container-full) .wide-slider-three-slids-wrapper{
						border-radius: {$rounded};
						overflow: hidden;
					}

					body .wide-slider-nav-wrapper,
					body .share-buttons-bottom,
					body .first-post-gradient li:first-child .post-thumb:after,
					body .scroll-2-box .post-thumb:after{
						border-bottom-left-radius: {$rounded};
						border-bottom-right-radius: {$rounded};
					}

					body .main-menu .menu-sub-content,
					body .comp-sub-menu{
						border-bottom-left-radius: 10px;
						border-bottom-right-radius: 10px;
					}

					body.single-post .featured-area{
						overflow: hidden;
					}

					body #check-also-box.check-also-left{
						border-top-right-radius: {$rounded};
						border-bottom-right-radius: {$rounded};
					}

					body #check-also-box.check-also-right{
						border-top-left-radius: {$rounded};
						border-bottom-left-radius: {$rounded};
					}

					body .mag-box .breaking-news-nav li:last-child{
						border-top-right-radius: 35px;
						border-bottom-right-radius: 35px;
					}

					body .mag-box .breaking-title:before{
						border-top-$left-radius: 35px;
						border-bottom-$left-radius: 35px;
					}

					body .tabs li:last-child a,
					body .full-overlay-title li:not(.no-post-thumb) .block-title-overlay{
						border-top-$right-radius: {$rounded};
					}

					body .center-overlay-title li:not(.no-post-thumb) .block-title-overlay,
					body .tabs li:first-child a{
						border-top-$left-radius: {$rounded};
					}
				";

			}
		}

		return $out;
	}
}


/**
 * Custom Theme Color
 */
if( ! function_exists( 'jannah_theme_color_css' ) ) {

	add_filter( 'TieLabs/CSS/custom_theme_color', 'jannah_theme_color_css', 1, 5 );
	function jannah_theme_color_css( $css_code, $color, $dark_color, $bright, $rgb_color ){

		$css_code .="
			:root:root{
				--brand-color: $color;
				--dark-brand-color: $dark_color;
				--bright-color: $bright;
				--base-color: #2c2f34;
			}
		";

		/**
		 * Footer Border Top
		 */
		if( tie_get_option( 'footer_border_top' ) ) {
			$css_code .="
				#footer-widgets-container{
					border-top: 8px solid $color;
					-webkit-box-shadow: 0 -5px 0 rgba(0,0,0,0.07);
					   -moz-box-shadow: 0 -8px 0 rgba(0,0,0,0.07);
								  box-shadow: 0 -8px 0 rgba(0,0,0,0.07);
				}
			";
		}

		/**
		 * Misc
		 */
		$css_code .="
			#reading-position-indicator{
				box-shadow: 0 0 10px rgba( $rgb_color, 0.7);
			}
		";

		return $css_code;
	}
}





/*
 * Check if the Main or Top Nav
 * have the same color of the Primary Menu
 * And add some color fixes
*/
if( ! function_exists( 'jannah_theme_color_fix_menus_colors' ) ) {

	add_filter( 'TieLabs/CSS/custom_theme_color', 'jannah_theme_color_fix_menus_colors', 7, 5 );
	function jannah_theme_color_fix_menus_colors( $css_code, $color, $dark_color, $bright, $rgb_color ){


		// Main Nav
		if( ( $color == tie_get_option( 'main_nav_background' ) ) && ! tie_get_option( 'main_nav_links_color' ) ){

			$hover_and_active = tie_get_option( 'main_nav_links_color_hover' );

			$css_code .="
				#main-nav ul.menu > li.tie-current-menu > a,
				#main-nav ul.menu > li:hover > a,
				#main-nav .spinner > div,
				.main-menu .mega-links-head:after{
					background-color: $hover_and_active !important;
				}

				#main-nav a,
				#main-nav .dropdown-social-icons li a span,
				#autocomplete-suggestions.search-in-main-nav a {
					color: $bright !important;
				}

				#main-nav .main-menu ul.menu > li.tie-current-menu,
				#theme-header nav .menu > li > .menu-sub-content{
					border-color: $hover_and_active;
				}

				#main-nav .spinner-circle:after{
					color: $hover_and_active !important;
				}
			";
		}

		// Top Nav
		if( ( $color == tie_get_option( 'secondry_nav_background' ) ) && ! tie_get_option( 'topbar_links_color' ) ){

			$css_code .="
				#top-nav a{
					color: $bright;
				}
			";
		}

		return $css_code;
	}
}



/**
 * Set Sections Custom Styles
 */
if( ! function_exists( 'jannah_section_custom_styles' ) ) {

	add_filter( 'TieLabs/CSS/Builder/section_style', 'jannah_section_custom_styles', 10, 3 );
	function jannah_section_custom_styles( $section_css, $section_id, $section_settings ){

		// Section Head Styles
		if( ! empty( $section_settings['section_title'] ) && ! empty( $section_settings['title'] ) && ! empty( $section_settings['title_color'] ) ) {

			$block_style = tie_get_option( 'blocks_style', 1 );

			$color    = $section_settings['title_color'];
			$darker   = TIELABS_STYLES::color_brightness( $color );
			$bright   = TIELABS_STYLES::light_or_dark( $color );
			$selector = "#$section_id .section-title";

			// Centered Style
			if( ! empty( $section_settings['title_style'] ) && $section_settings['title_style'] == 'centered' ){

				$section_css .= "

					$selector,
					$selector a{
						color: $color;
					}

					$selector a:hover{
						color: $darker;
					}

					#$section_id .section-title-centered:before,
					#$section_id .section-title-centered:after{
						background-color: $color;
					}
				";
			}

			// Big Style
			elseif( ! empty( $section_settings['title_style'] ) && $section_settings['title_style'] == 'big' ){

				$section_css .= "

					$selector,
					$selector a{
						color: $color;
					}

					$selector a:hover{
						color: $darker;
					}
				";
			}

			// Default Style
			elseif( empty( $section_settings['title_style'] ) ){

				$selector = "#$section_id .section-title-default";

				/* Style #1 */
				if( $block_style == 1 ){

					$section_css .= "
						$selector,
						$selector a{
							color: $color;
						}

						$selector a:hover{
							color: $darker;
						}

						$selector:before{
							border-top-color: $color;
						}

						$selector:after{
							background-color: $color;
						}
					";
				}

				/* Style #2 */
				if( $block_style == 2 ){

					$section_css .= "
						$selector,
						$selector a{
							border-color: $color;
							color: $color;
						}

						$selector a:hover{
							color: $darker;
						}
					";
				}

				/* Style #3 */
				elseif( $block_style == 3 ){

					$section_css .= "
						$selector,
						$selector a{
							color: $color;
						}

						$selector a:hover{
							color: $darker;
						}

						$selector:after {
							background: $color;
						}
					";
				}

				/* Style #4 || #5 || #6 */
				elseif( $block_style == 4 || $block_style == 5 || $block_style == 6 ){

					$section_css .= "
						$selector,
						$selector a{
							color: $bright;
						}

						$selector:before{
							background-color: $color;
						}
					";

					/* Style #6 */
					if( $block_style == 6 ){

						$section_css .= "
							$selector:after{
								background-color: $color;
							}
						";
					}
				}

				/* Style #7 */
				elseif( $block_style == 7 ){

					$section_css .= "
						$selector{
							background-color: $color;
							color: $bright;
						}

						$selector a{
							color: $bright;
						}

						$selector:after{
							background-color: $bright;
						}
					";
				}

				/* Style #8 */
				elseif( $block_style == 8 ){

					$section_css .= "
						$selector:before{
							background-color: $color;
						}

						$selector a:hover{
							color: $color;
						}
					";
				}

			}
		}

		// Block 16 and 12 title section color
		if( tie_get_option( 'boxes_style' ) == 2 && ! empty( $section_settings['background_color'] ) ){

			$color  = $section_settings['background_color'];
			$bright = TIELABS_STYLES::light_or_dark( $color );

			$section_css .= "
				#$section_id .full-overlay-title li:not(.no-post-thumb) .block-title-overlay{
					background-color: $color;
				}

				#$section_id .full-overlay-title li:not(.no-post-thumb) .block-title-overlay .post-meta,
				#$section_id .full-overlay-title li:not(.no-post-thumb) .block-title-overlay a:not(:hover){
					color: $bright;
				}

				#$section_id .full-overlay-title li:not(.no-post-thumb) .block-title-overlay .post-meta{
					opacity: 0.80;
				}
			";
		}

		return $section_css;
	}
}


/*
 * Set Custom color for the blocks
 */
if( ! function_exists( 'jannah_block_custom_bg' ) ) {

	add_filter( 'TieLabs/CSS/Builder/block_bg', 'jannah_block_custom_bg', 10, 6 );
	function jannah_block_custom_bg( $block_css, $id_css, $block, $color, $bright, $darker ){

		if( $color == $darker ){
			$darker = TIELABS_STYLES::color_brightness( $color, 30 );
		}

		/*
		$id_css .trending-post.tie-icon-bolt{
			color: $bright;
		}
		*/

		// Default Blocks Head Style
		$block_style = tie_get_option( 'blocks_style', 1 );

		$block_css = "
			$id_css{
				color: $bright;
			}

			$id_css .container-wrapper,
			$id_css .flexMenu-popup,
			$id_css.full-overlay-title li:not(.no-post-thumb) .block-title-overlay{
				background-color: $color;
			}

			$id_css .slider-arrow-nav a:not(:hover),
			$id_css .pagination-disabled,
			$id_css .pagination-disabled:hover{
				color: $bright !important;
			}

			$id_css a:not(:hover):not(.button){
				color: $bright;
			}

			$id_css .entry,
			$id_css .post-excerpt,
			$id_css .post-meta,
			$id_css .day-month,
			$id_css .post-meta a:not(:hover){
				color: $bright !important;
				opacity: 0.9;
			}

			$id_css.first-post-gradient .posts-items li:first-child a:not(:hover),
			$id_css.first-post-gradient li:first-child .post-meta{
				color: #ffffff !important;
			}

			$id_css .slider-arrow-nav a,
			$id_css .pages-nav .pages-numbers a,
			$id_css .show-more-button{
				border-color: $darker;
			}

		";

		// Block Style 1
		if( $block_style == 1 ){
			$block_css .= "
				.block-head-1 $id_css .the-global-title{
					border-color: $darker;
				}
			";
		}


		// Tabs
		if( $block['style'] == 'tabs' ){
			$block_css .= "
				$id_css.tabs-box,
				$id_css.tabs-box .tabs .active > a{
					background-color: $color;
				}

				$id_css.tabs-box .tabs a{
					background-color: $darker;
				}

				$id_css.tabs-box .tabs{
					border-color: $darker;
				}

				$id_css.tabs-box .tabs a,
				$id_css.tabs-box .flexMenu-popup,
				$id_css.tabs-box .flexMenu-popup li a{
					border-color: rgba(0,0,0,0.1);
				}
			";

			if( tie_get_option( 'boxes_style' ) == 2 ){
				$block_css .= "
					$id_css .tab-content{
						padding: 0;
					}
				";
			}
		}

		/* Breaking */
		elseif( $block['style'] == 'breaking' ){
			$block_css .= "
				$id_css .breaking,
				$id_css .ticker-content,
				$id_css .ticker-swipe{
					background-color: $color;
				}
			";
		}

		/* Timeline */
		elseif( $block['style'] == 'timeline' ){
			$block_css .= "
				$id_css.timeline-box .posts-items:last-of-type:after{
					background-image: linear-gradient(to bottom, $darker 0%, $color 80%);
				}

				$id_css .year-month,
				$id_css .day-month:before,
				$id_css.timeline-box .posts-items:before{
					background-color: $darker;
				}

				$id_css .year-month{
					color: $bright;
				}

				$id_css .day-month:before{
					border-color: $color;
				}
			";
		}

		/* Custom Contents */
		elseif( $block['style'] == 'code' || $block['style'] == 'code_50' ){
			$block_css .= "
				$id_css .tabs.tabs .active > a{
					background-color: $darker;
					border-color: rgba(0,0,0,0.1);
				}
			";
		}

		/* Scrolling */
		elseif( $block['style'] == 'scroll' || $block['style'] == 'scroll_2' ){
			$block_css .= "
				$id_css .tie-slick-dots li:not(.slick-active) button{
					background-color: $darker;
				}
			";
		}

		return $block_css;
	}
}


/*
 * Set Custom color for the blocks
 */
if( ! function_exists( 'jannah_block_custom_color' ) ) {

	add_filter( 'TieLabs/CSS/Builder/block_style', 'jannah_block_custom_color', 10, 6 );
	function jannah_block_custom_color( $block_css, $id_css, $block, $color, $bright, $darker ){

		$block_css .= "
			$id_css{
				--brand-color: $color;
				--dark-brand-color: $darker;
				--bright-color: $bright;
			}
		";

		if( $block['style'] == 'breaking' ){
			$block_css .= "
				$id_css .breaking-title {
					color: $bright;
				}
				$id_css .breaking-title::before {
					background: $color;
				}
			";
		}

		return $block_css;
	}
}


/**
 * Default Theme fonts sections
 */
if( ! function_exists( 'jannah_fonts_sections' ) ) {

	add_filter( 'TieLabs/fonts_sections_array', 'jannah_fonts_sections' );
	function jannah_fonts_sections(){

		$fonts_sections = array(
			'body'         => 'body',
			'headings'     => '.logo-text, h1, h2, h3, h4, h5, h6, .the-subtitle',
			'menu'         => '#main-nav .main-menu > ul > li > a',
			'blockquote'   => 'blockquote p',
		);

		return apply_filters( 'Jannah/fonts_default_sections_array', $fonts_sections );
	}
}


/**
 * Default Theme Typography Elements
 */
if( ! function_exists( 'jannah_typography_elements' ) ) {

	add_filter( 'TieLabs/typography_elements', 'jannah_typography_elements' );
	function jannah_typography_elements(){

		# Custom size, line height, weight, captelization
		$text_sections = array(
			'body'                  => 'body',
			'site_title'            => '#logo.text-logo .logo-text',
			'top_menu'              => '#top-nav .top-menu > ul > li > a',
			'top_menu_sub'          => '#top-nav .top-menu > ul ul li a',
			'main_nav'              => '#main-nav .main-menu > ul > li > a',
			'main_nav_sub'          => '#main-nav .main-menu > ul ul li a',
			'mobile_menu'           => '#mobile-menu li a',
			'breaking_news'         => '.breaking .breaking-title',
			'breaking_news_posts'   => '.ticker-wrapper .ticker-content',
			'buttons'               => 'body .button,body [type="submit"]', // body > override Sari3
			'breadcrumbs'           => '#breadcrumb',
			'post_cat_label'        => '.post-cat',
			'single_post_title'     => '.entry-header h1.entry-title',
			'single_post_sec_title' => '.entry-header .entry-sub-title',
			'single_archive_title'  => 'h1.page-title',
			'post_entry'            => '#the-post .entry-content, #the-post .entry-content p',
			'comment_text'          => '.comment-list .comment-body p',
			'blockquote'            => '#the-post .entry-content blockquote, #the-post .entry-content blockquote p',
			'boxes_title'           => '#tie-wrapper .mag-box-title h3',

			'page_404_main_title'  => array( 'min-width: 992px' => '.container-404 h2' ),
			'page_404_sec_title'   => array( 'min-width: 992px' => '.container-404 h3' ),
			'page_404_description' => array( 'min-width: 992px' => '.container-404 h4' ),

			'sections_title_default' => array(
				'min-width: 768px' => '.section-title.section-title-default, .section-title-centered',
			),
			'sections_title_big' => array(
				'min-width: 768px' => '.section-title-big',
			),

			'copyright'            => '#tie-wrapper .copyright-text',
			'footer_widgets_title' => '#footer .widget-title .the-subtitle',
			'post_heading_h1'      => '.entry h1',
			'post_heading_h2'      => '.entry h2',
			'post_heading_h3'      => '.entry h3',
			'post_heading_h4'      => '.entry h4',
			'post_heading_h5'      => '.entry h5',
			'post_heading_h6'      => '.entry h6',

			'widgets_title'        => '
				#tie-wrapper .widget-title .the-subtitle,
				#tie-wrapper #comments-title,
				#tie-wrapper .comment-reply-title,
				#tie-wrapper .woocommerce-tabs .panel h2,
				#tie-wrapper .related.products h2,
				#tie-wrapper #bbpress-forums #new-post > fieldset.bbp-form > legend,
				#tie-wrapper .entry-content .review-box-header',

			'widgets_post_title'   => '
				.post-widget-body .post-title,
				.timeline-widget ul li h3,
				.posts-list-half-posts li .post-title
			',

			// Blocks Typography Options
			'post_title_blocks' => '
				#tie-wrapper .media-page-layout .thumb-title,
				#tie-wrapper .mag-box.full-width-img-news-box .posts-items>li .post-title,
				#tie-wrapper .miscellaneous-box .posts-items>li:first-child .post-title,
				#tie-wrapper .big-thumb-left-box .posts-items li:first-child .post-title',
			'post_medium_title_blocks' => '
				#tie-wrapper .mag-box.wide-post-box .posts-items>li:nth-child(n) .post-title,
				#tie-wrapper .mag-box.big-post-left-box li:first-child .post-title,
				#tie-wrapper .mag-box.big-post-top-box li:first-child .post-title,
				#tie-wrapper .mag-box.half-box li:first-child .post-title,
				#tie-wrapper .mag-box.big-posts-box .posts-items>li:nth-child(n) .post-title,
				#tie-wrapper .mag-box.mini-posts-box .posts-items>li:nth-child(n) .post-title,
				#tie-wrapper .mag-box.latest-poroducts-box .products .product h2',
			'post_small_title_blocks' => '
				#tie-wrapper .mag-box.big-post-left-box li:not(:first-child) .post-title,
				#tie-wrapper .mag-box.big-post-top-box li:not(:first-child) .post-title,
				#tie-wrapper .mag-box.half-box li:not(:first-child) .post-title,
				#tie-wrapper .mag-box.big-thumb-left-box li:not(:first-child) .post-title,
				#tie-wrapper .mag-box.scrolling-box .slide .post-title,
				#tie-wrapper .mag-box.miscellaneous-box li:not(:first-child) .post-title',

			// Sliders Typography Options
			'post_title_sliders' => array(
				'min-width: 992px' => '
					.full-width .fullwidth-slider-wrapper .thumb-overlay .thumb-content .thumb-title,
					.full-width .wide-next-prev-slider-wrapper .thumb-overlay .thumb-content .thumb-title,
					.full-width .wide-slider-with-navfor-wrapper .thumb-overlay .thumb-content .thumb-title,
					.full-width .boxed-slider-wrapper .thumb-overlay .thumb-title',
			),
			'post_medium_title_sliders' => array(
				'min-width: 992px' => '
					.has-sidebar .fullwidth-slider-wrapper .thumb-overlay .thumb-content .thumb-title,
					.has-sidebar .wide-next-prev-slider-wrapper .thumb-overlay .thumb-content .thumb-title,
					.has-sidebar .wide-slider-with-navfor-wrapper .thumb-overlay .thumb-content .thumb-title,
					.has-sidebar .boxed-slider-wrapper .thumb-overlay .thumb-title',
				'min-width: 768px' => '
					#tie-wrapper .main-slider.tie-slider-10 .slide .grid-item:nth-child(1) .thumb-title,
					#tie-wrapper .main-slider.tie-slider-17 .slide .grid-item:nth-child(1) .thumb-title,
					#tie-wrapper .main-slider.tie-slider-14 .slide .grid-item:nth-child(1) .thumb-title,
					#tie-wrapper .main-slider.tie-slider-13 .slide .grid-item:nth-child(1) .thumb-title,
					#tie-wrapper .main-slider.tie-slider-16 .slide .grid-item:nth-child(1) .thumb-title,
					#tie-wrapper .main-slider.tie-slider-9 .thumb-overlay .thumb-title,
					#tie-wrapper .wide-slider-three-slids-wrapper .thumb-title',
			),
			'post_small_title_sliders' => array(
				'min-width: 768px' => '
					#tie-wrapper .boxed-slider-three-slides-wrapper .slide .thumb-title,
					#tie-wrapper .tie-slider-10 .slide .grid-item:nth-child(n+2) .thumb-title,
					#tie-wrapper .tie-slider-17 .slide .grid-item:nth-child(n+2) .thumb-title,
					#tie-wrapper .tie-slider-14 .slide .grid-item:nth-child(n+2) .thumb-title,
					#tie-wrapper .tie-slider-13 .slide .grid-item:nth-child(n+2) .thumb-title,
					#tie-wrapper .tie-slider-16 .slide .grid-item:nth-child(n+2) .thumb-title,
					#tie-wrapper .tie-slider-12 .grid-item:nth-child(n) .thumb-overlay .thumb-title,
					#tie-wrapper .main-slider.tie-slider-11 .thumb-overlay .thumb-title,
					#tie-wrapper .tie-slider-15 .thumb-overlay .thumb-title,
					#tie-wrapper .tie-slider-7 .slide .thumb-title',
			),
		);

		return apply_filters( 'Jannah/typography_default_elements_array', $text_sections );
	}
}

