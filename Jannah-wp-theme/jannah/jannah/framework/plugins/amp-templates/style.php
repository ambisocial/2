<?php
// Get content width
$content_max_width       = absint( $this->get( 'content_max_width' ) );

// Get template colors
$default_color           = tie_primary_color();

$theme_color             = tie_get_option( 'amp_bg_color', '#ffffff' );
$text_color              = TIELABS_STYLES::light_or_dark( $theme_color );
$post_title              = tie_get_option( 'amp_title_color', $text_color );
$muted_text_color        = tie_get_option( 'amp_meta_color', '#888888' );
$border_color            = '#ccc';
$link_color              = tie_get_option( 'amp_links_color', $default_color );

$header_background_color = tie_get_option( 'amp_header_color', $default_color );
$header_color            = TIELABS_STYLES::light_or_dark( $header_background_color );

$footer_background_color = tie_get_option( 'amp_footer_color', '#222222' );
$footer_color            = TIELABS_STYLES::light_or_dark( $footer_background_color );

$text_decoration         = tie_get_option( 'amp_links_underline' ) ? 'underline' : 'none';

$slide_menu              = tie_get_option( 'amp_menu_dark' ) ? '#131313' : '#efefef';

$menu_position           = tie_get_option( 'amp_menu_position', 'left' ) == 'left' ? 'left' : 'right';

?>
/* Generic WP styling */

img{
	max-width: 100%;
	height: auto;
}

.alignright {
	float: right;
}

.alignleft {
	float: left;
}

.aligncenter {
	display: block;
	margin-left: auto;
	margin-right: auto;
}

.amp-wp-enforced-sizes {
	/** Our sizes fallback is 100vw, and we have a padding on the container; the max-width here prevents the element from overflowing. **/
	max-width: 100%;
	margin: 0 auto;
}

.amp-wp-unknown-size img {
	/** Worst case scenario when we can't figure out dimensions for an image. **/
	/** Force the image into a box of fixed dimensions and use object-fit to scale. **/
	object-fit: contain;
}

/* Template Styles */

.amp-wp-content,
.amp-wp-title-bar div {
	<?php if ( $content_max_width > 0 ) : ?>
	margin: 0 auto;
	max-width: <?php echo sprintf( '%dpx', $content_max_width ); ?>;
	<?php endif; ?>
}

html {
	background: <?php echo esc_attr( $header_background_color ); ?>;
}

body {
	background: <?php echo esc_attr( $theme_color ); ?>;
	color: <?php echo esc_attr( $text_color ); ?>;
	font-weight: 300;
	line-height: 1.75em;
	margin: 0;
	font-family: <?php echo ( ! empty( $GLOBALS['tie_fonts_family']['body'] ) ) ? $GLOBALS['tie_fonts_family']['body'] : '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", "Open Sans", sans-serif'; ?>;
	padding-bottom: 0;
}

p,
ol,
ul,
figure {
	margin: 0 0 1em;
	padding: 0;
}

a,
a:visited {
	color: <?php echo esc_attr( $link_color ); ?>;

	text-decoration: none;
}

a:hover,
a:active,
a:focus {
	color: <?php echo esc_attr( $text_color ); ?>;

	text-decoration: <?php echo esc_attr( $text_decoration ); ?>;
}

/* Quotes */

blockquote {
	color: <?php echo esc_attr( $text_color ); ?>;
	background: rgba(127,127,127,.125);
	margin: 8px 0 24px 0;
	padding: 16px;

	border: 0 solid <?php echo esc_attr( $link_color ); ?>;
	border-left-width: 4px;
}

blockquote p:last-child {
	margin-bottom: 0;
}

/* UI Fonts */

.amp-wp-meta,
.amp-wp-header .amp-logo,
.amp-wp-title,
.amp-wp-sub-title,
.wp-caption-text,
.amp-wp-tax-category,
.amp-wp-tax-tag,
.amp-wp-comments-link,
.amp-wp-footer p,
.back-to-top {
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen-Sans", "Ubuntu", "Cantarell", "Helvetica Neue", sans-serif;
}

/* Header */

.amp-wp-header {
	background-color: <?php echo esc_attr( $header_background_color ); ?>;

	box-shadow: 0 0 24px 0 rgba(0, 0, 0, 0.25);
}

.amp-wp-header .amp-logo {
	color: <?php echo esc_attr( $header_color ); ?>;
	font-size: 1em;
	font-weight: 400;
	margin: 0 auto;
	max-width: calc(700px - 32px);
	position: relative;

	padding: 1em 16px;
}

.amp-wp-header .amp-logo a {
	color: <?php echo esc_attr( $header_color ); ?>;
	text-decoration: none;
}

/* Site Icon */

.amp-wp-header .amp-wp-site-icon {
	/** site icon is 32px **/
	background-color: <?php echo esc_attr( $header_color ); ?>;
	border: 1px solid <?php echo esc_attr( $header_color ); ?>;
	position: absolute;
	right: 18px;
	top: 10px;
}

/* Article */

.amp-wp-article {
	color: <?php echo esc_attr( $text_color ); ?>;
	font-weight: 400;
	margin: 1.5em auto;
	max-width: 700px;
	overflow-wrap: break-word;
	word-wrap: break-word;
}

/* Article Header */

.amp-wp-article-header {
	align-items: center;
	align-content: stretch;
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	margin: 1.5em 16px 1.5em;
}

.amp-wp-title {
	color: <?php echo esc_attr( $post_title ); ?>;
	display: block;
	flex: 1 0 100%;
	font-weight: bold;
	margin: 0 0 .625em;
	width: 100%;
	font-size: 1.8em;
	line-height: 1.4;
}

.amp-wp-sub-title {
	color: #777777;
	display: block;
	flex: 1 0 100%;
	font-weight: normal;
	margin: 0 0 .625em;
	width: 100%;
	font-size: 1.5em;
	line-height: 1.2;
}

/* Article Meta */

.amp-wp-meta {
	color: <?php echo esc_attr( $muted_text_color ); ?>;
	display: inline-block;
	flex: 2 1 50%;
	font-size: .875em;
	line-height: 1.5em;
	margin: 0;
	padding: 0;
}

.amp-wp-article-header .amp-wp-meta:last-of-type {
	text-align: right;
}

.amp-wp-article-header .amp-wp-meta:first-of-type {
	text-align: left;
}

.amp-wp-byline amp-img,
.amp-wp-byline .amp-wp-author {
	display: inline-block;
	vertical-align: middle;
}

.amp-wp-byline amp-img {
	border-radius: 50%;
	position: relative;
	margin-<?php echo is_rtl() ? 'left' : 'right'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>: 6px;
}

.amp-wp-posted-on {
	text-align: right;
}

/* Featured image */

.amp-wp-article-featured-image {
	margin: 0 0 1em;
}
.amp-wp-article-featured-image amp-img {
	margin: 0 auto;
}
.amp-wp-article-featured-image.wp-caption .wp-caption-text {
	margin: 0 18px;
}

/* Article Content */

<?php
	$post_font_size = '1em';
	if( $post_entry = tie_get_option( 'typography_post_entry' ) ){

		if( ! empty( $post_entry['size'] ) ){
			$post_font_size = $post_entry['size'] .'px';
		}

	}
?>

.amp-wp-article-content {
	margin: 0 16px;
	font-size: <?php esc_html_e( $post_font_size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	line-height: 1.75;
}

.amp-wp-article-content ul,
.amp-wp-article-content ol {
	margin-<?php echo is_rtl() ? 'right' : 'left'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>: 1em;
}

.amp-wp-article-content amp-img {
	margin: 0 auto;
}

.amp-wp-article-content amp-img.alignright,
.amp-wp-article-content .alignright amp-img{
	margin: 0 0 1em 16px;
}

.amp-wp-article-content amp-img.alignleft,
.amp-wp-article-content .alignleft amp-img{
	margin: 0 16px 1em 0;
}


/* Captions */
.wp-block-image,
.wp-caption {
	padding: 0;
	max-width: 100%;
	margin-bottom: 1.2em;
}

.wp-caption.alignleft {
	margin-right: 16px;
}

.wp-caption.alignright {
	margin-left: 16px;
}

.wp-block-image figcaption,
.wp-caption .wp-caption-text {
	color: <?php echo esc_attr( $muted_text_color ); ?>;
	text-align: center;
	font-size: .875em;
	line-height: 1.5em;
	margin: 0;
	padding: .3em 0 .75em;
}

/* AMP Media */

amp-carousel {
	background: <?php echo esc_attr( $border_color ); ?>;
	margin: 0 -16px 1.5em;
}
amp-iframe,
amp-youtube,
amp-instagram,
amp-vine {
	background: <?php echo esc_attr( $border_color ); ?>;
	margin: 0 -16px 1.5em;
}

.amp-wp-article-content amp-carousel amp-img {
	border: none;
}

amp-carousel > amp-img > img {
	object-fit: contain;
}

.amp-wp-iframe-placeholder {
	background: <?php echo esc_attr( $border_color ); ?> url( <?php echo esc_url( $this->get( 'placeholder_image_url' ) ); ?> ) no-repeat center 40%;
	background-size: 48px 48px;
	min-height: 48px;
}

/* Article Footer Meta */

.amp-wp-article-footer .amp-wp-meta {
	display: block;
}

.amp-wp-tax-category,
.amp-wp-tax-tag {
	color: <?php echo esc_attr( $muted_text_color ); ?>;
	font-size: .875em;
	line-height: 1.5em;
	margin: 1em 16px;
}

.amp-wp-comments-link {
	color: <?php echo esc_attr( $muted_text_color ); ?>;
	font-size: .875em;
	line-height: 1.5em;
	text-align: center;
	margin: 2.25em 0 1.5em;
}

.amp-wp-comments-link a {
	border-style: solid;
	border-color: <?php echo esc_attr( $border_color ); ?>;
	border-width: 1px 1px 2px;
	border-radius: 4px;
	background-color: transparent;
	color: <?php echo ( $link_color ); ?>;
	cursor: pointer;
	display: block;
	font-size: 14px;
	font-weight: 600;
	line-height: 18px;
	margin: 0 auto;
	max-width: 200px;
	padding: 11px 16px;
	text-decoration: none;
	width: 50%;
	-webkit-transition: background-color 0.2s ease;
			transition: background-color 0.2s ease;
}


/** TIELABS CUSTOM STYLES AND ELEMENTS **/

<?php if( tie_get_option( 'amp_logo' ) ):?>
	/* Custom Logo */
	.amp-wp-header a {
		background-image: url( '<?php echo esc_attr( tie_get_option( 'amp_logo' ) ); ?>' );
		background-repeat: no-repeat;
		background-size: contain;
		background-position: center center;
		display: block;
		height: 35px;
		width: 215px;
		margin: 0 auto;
		text-indent: -9999px;
	}
<?php endif; ?>

img{
	max-width: 100%;
}

/* TieLabs AMP Footer */
.top a{
	background-color: <?php echo esc_attr( $footer_background_color ); ?>;
	padding: 5px;
	width: 30px;
	margin: 0 auto;
	display: block;
	text-align: center;
	text-decoration: none;
}
.top a:hover,
.top a:focus{
	text-decoration: none;
}
.footer {
	background-color: <?php echo esc_attr( $footer_background_color ); ?>;
	padding: 1.5em 1em;
	color: <?php echo esc_attr( $footer_color ); ?>;
	text-align: center;
}
.footer-links a,
.footer-links a:hover,
.footer-links a:active,
.footer-links a:visited,
.top a,
.top a:hover,
.top a:active,
.top a:visited {
	color: <?php echo esc_attr( $footer_color ); ?>;
}
.footer-logo {
	display: block;
	background-repeat: no-repeat;
	background-size: contain;
	background-position: center;
	height: 50px;
	width: 200px;
	margin: auto;
	margin-bottom: 1.5em;
}

<?php if( tie_get_option( 'amp_footer_logo' ) ):?>
	.footer-logo {
		background-image: url( '<?php echo esc_attr( tie_get_option( 'amp_footer_logo' ) ); ?>' );
	}
<?php endif; ?>


.footer-links {
	text-align: center;
	padding-bottom: 1em;
	line-height: 1;
}
.footer-links a {
	display: inline-block;
	padding: 0 10px;
	font-size: 12px;
}
.footer-colophon {
	font-size: 10px;
}

/* TieLabs Related Posts */
.amp-related-posts{
	margin-top: 50px;
	overflow: hidden;
}
.amp-related-posts span{
	display: block;
	font-weight: bold;
	font-size: 24px;
}
.amp-related-posts ul{
	margin: 10px 0 0;
}
.amp-related-posts li{
	list-style: none;
	width: 46%;
	float: left;
	margin-bottom: 5px;
	padding: 1%;
}
.amp-related-posts li:nth-child(2n+3){
	clear: left;
}
.amp-related-posts a{
	display: block;
	line-height: 1.5;
}

/* TieLabs ADS */
.amp-wp-content amp-ad {
	margin: 10px auto;
	display: block;
	text-align: center;
}

.amp-custom-ad{
	max-width: 700px;
	margin: 1.5em auto;
	padding: 0 16px;
	box-sizing: border-box;
	text-align: center;
}

/* TieLabs Share Buttons */
.social{
	margin: 10px 0;
	text-align: center;
}

amp-social-share {
	background-size: 80%;
	margin: 0 3px;
}

/* TieLabs carousel */
amp-carousel {
	background: transparent;
}

/* TieLabs Misc */
.amp-featured{
	margin-bottom: 10px;
	position: relative;
}

.amp-featured .single-caption-text{
	position: absolute;
  bottom: 8px;
  left: 0;
  padding: 5px 10px;
  background: rgba(0,0,0,0.6);
  color: #ffffff;
  line-height: 1.8;
  font-size: 90%;
}

.wp-audio-shortcode{
	min-width: 100%;
}

.review_wrap{
	display: none;
}

.wp-video,
.wp-audio{
	max-width: 100%;
}


.post-bottom-meta {
  margin:3px 0 0;
  font-size:11px;
  clear:both;
  line-height:20px
}
.post-bottom-meta:after {
  content:"";
  display:table;
  clear:both
}
.post-bottom-meta-title,
.post-bottom-meta a {
  float:left;
  padding:0 6px;
  margin:5px 5px 0 0;
  background:#2c2f34;
  border:1px solid rgba(0,0,0,0.1);
  border-radius:2px
}
.post-bottom-meta-title {
  color:#ffffff
}
.post-bottom-meta-title a {
  color:#ffffff !important;
  padding:0;
  margin:0
}
.post-bottom-meta-title a:hover {
  opacity:0.7
}
.post-bottom-meta a {
  background:rgba(0,0,0,0.03)
}


/* Slide Menu */
<?php if(  tie_get_option( 'amp_menu_dark' ) ): ?>
	amp-sidebar {
		background: #131313;
	}

	.close-nav {
		color: #eee;
		background: #222;
	}

	.toggle-navigationv2 ul li a {
		background: #222;
		border-bottom: 1px solid #111;
		color: #eee;
	}

	.toggle-navigationv2 ul li a:hover {
		background: #333;
		color: #fff
	}

	.amp-menu li.menu-item-has-children:after {
		color: #ccc;
	}

<?php else: ?>

	amp-sidebar {
		background: #efefef;
	}

	.close-nav {
		color: #ffffff;
		background: rgba(0,0,0,0.25);
	}

	.toggle-navigationv2 ul li a {
		background: #fafafa;
		border-bottom: 1px solid #efefef;
		color: #0a89c0;
	}

	.toggle-navigationv2 ul li a:hover {
		background: #fff;
	}

	.amp-menu li.menu-item-has-children:after {
		color: #333;
	}

<?php endif; ?>

.hamburgermenu {
	float: <?php esc_html_e( $menu_position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	position: relative;
	z-index: 9999;
	margin-top: 8px;
}

.toast {
	display: block;
	position: relative;
	height: 50px;
	width: 50px;
	background: none;
	border: 0;
}

.toast::after,
.toast::before,
.toast span {
	position: absolute;
	display: block;
	width: 25px;
	height: 2px;
	border-radius: 2px;
	background-color: <?php echo tie_get_option( 'amp_menu_icon_color', '#fff' ); ?>;
	right: 10px;
}

.toast span {
	opacity: 1;
	top: 24px;
}

.toast::after, .toast::before {
	content: '';
}

.toast::before {
	top: 17px;
}

.toast::after {
	top: 31px;
}


amp-sidebar {
	width: 280px;
}

.close-nav {
	font-size: 12px;
	letter-spacing: 1px;
	display: inline-block;
	padding: 10px;
	border-radius: 100px;
	line-height: 8px;
	margin: 14px;
	position: relative;
}

.toggle-navigationv2 ul {
	list-style-type: none;
	padding: 0
}

.toggle-navigationv2 ul li a {
	padding: 10px 25px;
	display: block;
	font-size: 14px;
	box-sizing: border-box;
}

.amp-menu li {
	position: relative
}

.amp-menu li.menu-item-has-children ul {
	display: none;
	margin: 0;
}

.amp-menu li.menu-item-has-children ul {
	display: none;
	margin: 0;
	padding-<?php esc_html_e( $menu_position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>: 15px;
}

.amp-menu li.menu-item-has-children:hover>ul {
	display: block
}

.amp-menu li.menu-item-has-children:after {
	content: '\25be';
	position: absolute;
	padding: 10px 25px;
	<?php echo is_rtl() ? 'left' : 'right'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>: 0;
	font-size: 18px;
	top: 0;
	z-index: 10000;
	line-height: 1
}

.toggle-navigationv2 .social_icons {
	margin-top: 25px;
	border-top: 1px solid #555;
	padding: 25px 0px;
	color: #fff;
	width: 100%
}

.menu-all-pages-container:after {
	content: "";
	clear: both
}


#wp-admin-bar-tie-adminbar-panel .ab-icon * {
	max-width: 17px;
	height: auto;
}

table {
	border-spacing: 0;
	width: 100%;
	border-collapse: separate;
	margin-bottom: 1.5em
}

table tr:nth-child(even) {
	background: rgba(0,0,0,0.03)
}

table td,
table th {
	padding: .4em;
	border-right: 1px dotted rgba(0,0,0,0.1)
}

table thead th {
	background: rgba(0,0,0,0.03);
	border: 1px solid rgba(0,0,0,0.1);
	padding: 0.7em .4em;
	vertical-align: middle
}

table tbody th {
	background: rgba(0,0,0,0.03)
}

table tbody td,
table tbody th {
	border: 1px solid rgba(0,0,0,0.1)
}

/** Shortcodes */
:root {
      --color-primary: #005AF0;
      --space-1: .5rem;  /* 8px */
      --space-4: 2rem;   /* 32px */
    }

    /* Styles for the flex layout based tabs */
    amp-selector[role=tablist].tabs-with-flex {
        display: flex;
        flex-wrap: wrap;
    }
    amp-selector[role=tablist].tabs-with-flex [role=tab] {
        flex-grow: 1;
        /* custom styling, feel free to change */
        text-align: center;
        padding: var(--space-1);
    }
    amp-selector[role=tablist].tabs-with-flex [role=tab][selected] {
        outline: none;
        /* custom styling, feel free to change */
        border-bottom: 2px solid var(--color-primary);
    }
    amp-selector[role=tablist].tabs-with-flex [role=tabpanel] {
        display: none;
        width: 100%;
        order: 1; /* must be greater than the order of the tab buttons to flex to the next line */
        /* custom styling, feel free to change */
        padding: var(--space-4);
    }
    amp-selector[role=tablist].tabs-with-flex [role=tab][selected] + [role=tabpanel] {
        display: block;
    }

    /* Styles for the selector based tabs */
    amp-selector[role=tablist].tabs-with-selector {
        display: flex;
    }
    amp-selector[role=tablist].tabs-with-selector [role=tab][selected] {
        outline: none;
        /* custom styling, feel free to change */
        border-bottom: 2px solid var(--color-primary);
    }
    amp-selector[role=tablist].tabs-with-selector {
        display: flex;
    }
    amp-selector[role=tablist].tabs-with-selector [role=tab] {
      /* custom styling, feel free to change */
      width: 100%;
      text-align: center;
      padding: var(--space-1);
    }
    amp-selector.tabpanels [role=tabpanel] {
      display: none;
      /* custom styling, feel free to change */
      padding: var(--space-4);
    }
    amp-selector.tabpanels [role=tabpanel][selected] {
      outline: none;
      display: block;
    }



		
.shortc-button {
  cursor: pointer;
  padding: 10px 15px;
  display: inline-block;
  margin: 10px 5px 0 0;
  font-weight: normal;
  outline: none;
  position: relative;
  text-decoration: none !important;
  font-size: 13px;
  min-width: 80px;
  line-height: 1.5;
  overflow: hidden;
  text-align: center;
  border-radius: 4px;
  opacity: 0.85;
  transition: all .5s;
}

.rtl .shortc-button {
  margin-right: 0;
  margin-left: 5px;
}

.shortc-button:not(.button) {
  color: #fff !important;
}

.shortc-button:hover {
  opacity: 1;
  box-shadow: 0px 5px 10px -5px rgba(0, 0, 0, 0.4);
}

.shortc-button.red {
  background-color: #B02B2C;
}

.shortc-button.red:hover {
  box-shadow: 0px 5px 10px -5px #B02B2C;
}

.shortc-button.green {
  background-color: #83a846;
}

.shortc-button.green:hover {
  box-shadow: 0px 5px 10px -5px #83a846;
}

.shortc-button.blue {
  background-color: #7bb0e7;
}

.shortc-button.blue:hover {
  box-shadow: 0px 5px 10px -5px #7bb0e7;
}

.shortc-button.orange {
  background-color: #edae44;
}

.shortc-button.orange:hover {
  box-shadow: 0px 5px 10px -5px #edae44;
}

.shortc-button.pink {
  background-color: #d65799;
}

.shortc-button.pink:hover {
  box-shadow: 0px 5px 10px -5px #d65799;
}

.shortc-button.purple {
  background-color: #745f7e;
}

.shortc-button.purple:hover {
  box-shadow: 0px 5px 10px -5px #745f7e;
}

.shortc-button.black {
  background-color: #2c2c2c;
}

.shortc-button.black:hover {
  box-shadow: 0px 5px 10px -5px #2c2c2c;
}

.shortc-button.white {
  background-color: #fcfcfc;
  color: #666666 !important;
}

.shortc-button.white:hover {
  box-shadow: 0px 5px 10px -5px #ccc;
}

.shortc-button.gray {
  background: #DADADA;
  color: #333333 !important;
}

.shortc-button.gray:hover {
  box-shadow: 0px 5px 10px -5px #ccc;
}

.shortc-button.medium {
  padding: 13px 25px;
  min-width: 90px;
  font-size: 15px;
}

.shortc-button.big {
  padding: 15px 30px;
  min-width: 140px;
  font-size: 17px;
}

.tie-columns {
  position: relative;
  margin-right: 4%;
  float: left;
}

.rtl .tie-columns {
  float: right;
  margin-left: 4%;
  margin-right: 0;
}

@media (max-width: 768px) {
  .tie-columns {
    width: 100% !important;
    margin-right: 0 !important;
    margin-left: 0 !important;
  }
}

.one_half {
  width: 48%;
}

.one_third {
  width: 30.66%;
}

.two_third {
  width: 65.33%;
}

.one_fourth {
  width: 22%;
}

.three_fourth {
  width: 74%;
}

.one_fifth {
  width: 16.8%;
}

.two_fifth {
  width: 37.6%;
}

.three_fifth {
  width: 58.4%;
}

.four_fifth {
  width: 67.2%;
}

.one_sixth {
  width: 13.33%;
}

.five_sixth {
  width: 82.67%;
}

.tie-columns.last {
  margin-right: 0;
  clear: right;
}

.rtl .tie-columns.last {
  clear: left;
  margin-left: 0;
}

.clearfix{
	clear: both;
}

.divider{
	width: 100%;
}
<?php

if( ! empty( $GLOBALS['tie_fonts_family']['headings'] ) ){
	echo '
		h1,
		h2,
		h3,
		h4,
		h5,
		h6,
		.amp-wp-header .amp-logo,
		.amp-wp-title{
			font-family: '. $GLOBALS['tie_fonts_family']['headings'] .'
		}
	';
}

?>

<?php if( is_rtl() ): ?>
body {
	direction: rtl;
	unicode-bidi: embed;
}

.amp-wp-article-header .amp-wp-meta:first-of-type {
	text-align: right;
}

.amp-wp-article-header .amp-wp-meta:last-of-type {
	text-align: left;
}

blockquote {
	border-left-width: 0;
	border-right-width: 4px;
}

.amp-related-posts li{
	float: right;
}

.amp-related-posts li:nth-child(2n+3){
	clear: right;
}
<?php endif; ?>


.has-black-background-color {
  background-color: #000000;
}
.has-cyan-bluish-gray-background-color {
  background-color: #abb8c3;
}
.has-white-background-color {
  background-color: #ffffff
}
.has-pale-pink-background-color {
  background-color: #f78da7
}
.has-vivid-red-background-color {
  background-color: #cf2e2e
}
.has-luminous-vivid-orange-background-color {
  background-color:  #ff6900;
}
.has-luminous-vivid-amber-background-color {
  background-color: #fcb900;
}
.has-light-green-cyan-background-color {
  background-color: #7bdcb5;
}
.has-vivid-green-cyan-background-color {
  background-color: #00d084;
}
.has-pale-cyan-blue-background-color {
  background-color: #8ed1fc;
}
.has-vivid-cyan-blue-background-color {
  background-color: #0693e3;
}
.has-vivid-purple-background-color {
  background-color: #9b51e0;
}
.has-black-border-color {
  border-color: #000000
}
.has-cyan-bluish-gray-border-color {
  border-color: #abb8c3;
}
.has-white-border-color {
  border-color: #ffffff
}
.has-pale-pink-border-color {
  border-color: #f78da7
}
.has-vivid-red-border-color {
  border-color: #cf2e2e
}
.has-luminous-vivid-orange-border-color {
  border-color:  #ff6900;
}
.has-luminous-vivid-amber-border-color {
  border-color: #fcb900;
}
.has-light-green-cyan-border-color {
  border-color: #7bdcb5;
}
.has-vivid-green-cyan-border-color {
  border-color: #00d084;
}
.has-pale-cyan-blue-border-color {
  border-color: #8ed1fc;
}
.has-vivid-cyan-blue-border-color {
  border-color: #0693e3;
}
.has-vivid-purple-border-color {
  border-color: #9b51e0;
}
.has-vivid-cyan-blue-to-vivid-purple-gradient-background {
  background: linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%);
}
.has-light-green-cyan-to-vivid-green-cyan-gradient-background {
  background: linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%);
}
.has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background {
  background: linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%);;
}
.has-luminous-vivid-orange-to-vivid-red-gradient-background {
  background: linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%);
}
.has-very-light-gray-to-cyan-bluish-gray-gradient-background {
  background: linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%);
}
.has-cool-to-warm-spectrum-gradient-background {
  background: linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%);
}
.has-blush-light-purple-gradient-background {
  background: linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%);
}
.has-blush-bordeaux-gradient-background {
  background: linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%);
}
.has-luminous-dusk-gradient-background {
  background: linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%);
}
.has-pale-ocean-gradient-background {
  background: linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%);
}
.has-electric-grass-gradient-background {
  background: linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%);
}
.has-midnight-gradient-background {
  background:  linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%);
}

.has-black-color {
  color: #000000
}
.has-cyan-bluish-gray-color {
  color: #abb8c3;
}
.has-white-color {
  color: #ffffff;
}
.has-pale-pink-color {
  color: #f78da7;
}
.has-vivid-red-color {
  color:  #cf2e2e;
}
.has-luminous-vivid-orange-color {
  color: #ff6900;
}
.has-luminous-vivid-amber-color {
  color: #fcb900;
}
.has-light-green-cyan-color {
  color:  #7bdcb5;
}
.has-vivid-green-cyan-color {
  color: #00d084
}
.has-pale-cyan-blue-color {
  color: #8ed1fc
}
.has-vivid-cyan-blue-color {
  color:  #0693e3;
}
.has-vivid-purple-color {
  color: #9b51e0;
}


<?php echo tie_get_option( 'css_amp' ); ?>
