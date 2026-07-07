<?php
/**
 * Main Navigation
 *
 * This template can be overridden by copying it to your-child-theme/templates/header/nav-main.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author 		TieLabs
 * @version   7.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

// Header Layout
$header_layout = tie_get_option( 'header_layout', 3 );

if( tie_get_option( 'main_nav' ) || $header_layout == 1 || $header_layout == 4 ):

	$main_menu_class = array( 'main-nav header-nav' );

	// Live Search skin
	$live_search_data_skin = '';
	if( tie_get_option( 'main-nav-components_search' ) && tie_get_option( 'main-nav-components_live_search' ) ){
		$main_menu_class[] = 'live-search-parent';
		$live_search_data_skin = 'data-skin="search-in-main-nav"';
	}

	if( is_singular( 'post' ) && tie_get_option( 'single_sticky_menu' ) ){
		$main_menu_class[] = 'has-custom-sticky-menu';
	}

	// Header Layout
	$logo_width  = '';
	$line_height = '';

	if( $header_layout == 1 || $header_layout == 4 ){

		$logo_args = tie_logo_args();

		extract( $logo_args );

		$logo_margin_top    = ! empty( $logo_margin_top )    ? $logo_margin_top    : 20; // Default value in the CSS file
		$logo_margin_bottom = ! empty( $logo_margin_bottom ) ? $logo_margin_bottom : 20; // Default value in the CSS file

		$logo_width  = ( $logo_type == 'logo' ) ? 'style="width:' . intval( $logo_width ). 'px"' : '';
		$logo_height = ( $logo_type == 'logo' ) ? $logo_height : 49;
		$line_height = 'style="line-height:' . intval( $logo_height + $logo_margin_top + $logo_margin_bottom ). 'px"';
	}

	
	// Main Navigation Layout
	switch ( tie_get_option( 'main_nav_style' ) ) {
		/*
		case '0':
			$main_menu_class[] = 'menu-style-default menu-style-solid-bg';
			break;
			*/

		case '1':
			$main_menu_class[] = 'menu-style-solid-bg menu-style-side-arrow';
			break;

		case '2':
			$main_menu_class[] = 'menu-style-solid-bg';
			break;
		
		case '3':
			$main_menu_class[] = 'menu-style-minimal';
			break;
		
		case '4':
			$main_menu_class[] = 'menu-style-border-bottom menu-style-minimal';
			break;
		
		case '5':
			$main_menu_class[] = 'menu-style-border-top menu-style-minimal';
			break;
		
		case '6':
			$main_menu_class[] = 'menu-style-border-top menu-style-border-bottom menu-style-minimal';
			break;
		
		case '7':
			$main_menu_class[] = 'menu-style-line menu-style-minimal';
			break;
		
		case '8':
			$main_menu_class[] = 'menu-style-arrow menu-style-minimal';
			break;
		
		case '9':
			$main_menu_class[] = 'menu-style-vertical-line menu-style-minimal';
			break;

		default:
			$main_menu_class[] = 'menu-style-default menu-style-solid-bg';
			break;
	}
?>

<div class="main-nav-wrapper">
	<nav id="main-nav" <?php echo ( $live_search_data_skin ); ?> class="<?php echo esc_attr( join( ' ', $main_menu_class ) ) ?>" <?php echo ( $line_height ) ?> aria-label="<?php esc_html_e( 'Primary Navigation', TIELABS_TEXTDOMAIN ); ?>">
		<div class="container">

			<div class="main-menu-wrapper">

				<?php
					if( $header_layout == 1 || $header_layout == 4 ){
						do_action( 'TieLabs/Logo/before' ); ?>

						<div class="header-layout-1-logo" <?php echo ( $logo_width ) ?>>
							<?php tie_logo(); ?>
						</div>

						<?php
						do_action( 'TieLabs/Logo/after' );
					}
				?>

				<div id="menu-components-wrap">

					<?php

						// Sticky Menu Logo
						tie_sticky_logo();

						if( tie_get_option( 'single_sticky_menu' ) && is_singular( 'post' ) ){ ?>

							<div id="single-sticky-menu-contents">

							<?php 
								if( tie_get_option( 'single_sticky_menu_post_title' ) ){
									echo '<div class="sticky-post-title">'. get_the_title() .'</div>';
								}
								
								TIELABS_HELPER::get_template_part( 'templates/single-post/share', '', array( 'share_position' => 'sticky_menu' ) );
								
								if( tie_get_option( 'single_sticky_menu_next_prev' ) ){

									$next_post = get_adjacent_post( false, '', false, 'category' );
									$next_post_link  = ! empty( $next_post->ID ) ? get_the_permalink( $next_post->ID ) : '#';
									$next_post_class = ! empty( $next_post->ID ) ? '' : 'pagination-disabled';
			
									$prev_post = get_adjacent_post( false, '', true, 'category' );
									$prev_post_link  = ! empty( $prev_post->ID ) ? get_the_permalink( $prev_post->ID ) : '#';
									$prev_post_class = ! empty( $prev_post->ID ) ? '' : 'pagination-disabled';
			
									?>

									<div id="sticky-next-prev-posts" class="widget-pagination-wrapper <?php if( tie_get_option( 'main_nav_dark' ) ) echo 'dark-skin'; ?>">
										<ul class="slider-arrow-nav">
											<li>
												<a class="prev-posts <?php echo esc_attr( $prev_post_class ) ?>" href="<?php echo esc_url( $prev_post_link ); ?>" title="<?php esc_html_e( 'Previous post', TIELABS_TEXTDOMAIN ); ?>">
													<span class="tie-icon-angle-left" aria-hidden="true"></span>
													<span class="screen-reader-text"><?php esc_html_e( 'Previous post', TIELABS_TEXTDOMAIN ); ?></span>
												</a>
											</li>
											<li>
												<a class="next-posts <?php echo esc_attr( $next_post_class ) ?>" href="<?php echo esc_url( $next_post_link ); ?> " title="<?php esc_html_e( 'Next post', TIELABS_TEXTDOMAIN ); ?>">
													<span class="tie-icon-angle-right" aria-hidden="true"></span>
													<span class="screen-reader-text"><?php esc_html_e( 'Next post', TIELABS_TEXTDOMAIN ); ?></span>
												</a>
											</li>
										</ul>
									</div>

									<?php
								}
								?>

							</div>
							<?php
						}
					?>

					<div class="main-menu main-menu-wrap">
						<?php

							$custom_menu = tie_get_object_option( false, 'cat_menu', 'tie_menu' );

							$menu_args   = array(
								'menu'            => $custom_menu,
								'container_id'    => 'main-nav-menu',
								'container_class' => 'main-menu header-menu',
								'theme_location'  => 'primary',
								'fallback_cb'     => false,
								'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
							);

							// Check if the Built-in theme mega menu is enabled
							if( ! tie_get_option( 'disable_advanced_menus' ) ){
								$menu_args['walker'] = new TIELABS_MEGA_MENU();
							}

							wp_nav_menu( $menu_args );

						?>
					</div><!-- .main-menu /-->

					<?php

						do_action( 'TieLabs/after_main_menu' );

						// Get components template
						TIELABS_HELPER::get_template_part( 'templates/header/components', '', array( 'components_id' => 'main-nav' ) );

						do_action( 'TieLabs/after_main_components' );

					?>

				</div><!-- #menu-components-wrap /-->
			</div><!-- .main-menu-wrapper /-->
		</div><!-- .container /-->

		<?php
			if( tie_get_option( 'reading_indicator' ) && is_single() && ! tie_is_loaded_posts_active() && tie_get_option( 'reading_indicator_pos' ) == 'top' && tie_get_option( 'stick_nav' ) ){
				echo '<div id="reading-position-indicator"></div>';
			}
		?>
	</nav><!-- #main-nav /-->
</div><!-- .main-nav-wrapper /-->

<?php endif; ?>
