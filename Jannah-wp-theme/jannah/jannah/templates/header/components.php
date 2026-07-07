<?php
/**
 * Menus Components
 *
 * This template can be overridden by copying it to your-child-theme/templates/header/components.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author 		TieLabs
 * @version   7.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

echo '<ul class="components">';


if( tie_get_option( $components_id.'-components_button' ) && $text = tie_get_option( $components_id.'-components_button_text' ) ):

	$target   = tie_get_option( $components_id.'-components_button_tab' ) ? 'target="_blank"' : '';
	$nofollow = tie_get_option( $components_id.'-components_button_nofollow' ) ? 'rel="nofollow noopener"' : '';

	echo '
		<li class="custom-menu-button menu-item custom-menu-link">
			<a class="button" href="'. esc_url( tie_get_option( $components_id.'-components_button_url' ) ) .'" title="'. esc_attr( $text ).'" '. $target .' '. $nofollow .'>
			'. $text .'
			</a>
		</li>
	';
endif;



// Weather
if( tie_get_option( $components_id.'-components_weather' ) ):

	$location  = tie_get_option( $components_id.'-components_wz_location' );

	if( ! empty( $location ) ){

		$args = array(
			'location'      => $location,
			'units'         => tie_get_option( $components_id.'-components_wz_unit' ),
			'custom_name'   => tie_get_option( $components_id.'-components_wz_city_name' ),
			'animated'      => tie_get_option( $components_id.'-components_wz_animated' ),
			'compact'       => true,
			'forecast_days' => 'hide',
		);

		echo '<li class="weather-menu-item menu-item custom-menu-link">';
			new TIELABS_WEATHER( $args );
		echo '</li>';
	}
endif;


// Social
if( tie_get_option( $components_id.'-components_social' ) ):
	if( tie_get_option( "$components_id-components_social_layout" ) == 'list' ):?>
		<li class="list-social-icons menu-item custom-menu-link">
			<a href="#" class="follow-btn">
				<span class="tie-icon-plus" aria-hidden="true"></span>
				<span class="follow-text"><?php esc_html_e( 'Follow', TIELABS_TEXTDOMAIN ) ?></span>
			</a>
			<?php
				tie_get_social(
					array(
						'show_name' => true,
						'before'    => '<ul class="dropdown-social-icons comp-sub-menu">',
						'after'     => '</ul><!-- #dropdown-social-icons /-->'
					));
			?>
		</li><!-- #list-social-icons /-->
		<?php

	else:

		tie_get_social(
			array(
				'before'  => ' ',
				'after'   => ' '
			));

	endif;
endif;


// Login
if( tie_get_option( $components_id.'-components_login' ) ): ?>

	<?php if( is_user_logged_in() ){ ?>

		<li class="profile-icon menu-item custom-menu-link">
			<a href="#" class="profile-btn">
				<?php
					if( get_option( 'show_avatars' ) ){
						$current_user = wp_get_current_user();
						echo get_avatar( $current_user->ID, apply_filters( 'TieLabs/Login/avatar_size', 30 ) );
					}
					else{
						echo '<span class="tie-icon-author" aria-hidden="true"></span>';
					}
				?>
				<span class="screen-reader-text"><?php esc_html_e( 'Your Profile', TIELABS_TEXTDOMAIN ) ?></span>
			</a>

			<div class="components-sub-menu comp-sub-menu components-user-profile">
				<?php tie_login_form(); ?>
			</div><!-- .components-sub-menu /-->
		</li>

		<?php
		}
		else {
			$login_icon_class = tie_get_option( $components_id.'-components_login_text' ) ? 'has-title' : '';
		?>

		<li class="<?php echo esc_attr( $login_icon_class ) ?> popup-login-icon menu-item custom-menu-link">
			<a href="#" class="lgoin-btn tie-popup-trigger">
				<span class="tie-icon-author" aria-hidden="true"></span>
				<?php
					if( tie_get_option( $components_id.'-components_login_text' ) ){
						echo '<span class="login-title">'. tie_get_option( $components_id.'-components_login_text' ) .'</span>';
					}
					else{
						echo '<span class="screen-reader-text">'. esc_html__( 'Log In', TIELABS_TEXTDOMAIN ) .'</span>';
					}
				?>
			</a>
		</li>

		<?php } ?>
	<?php
endif;


// BuddyPress Notifications
if( tie_get_option( $components_id.'-components_bp_notifications' ) && is_user_logged_in() && TIELABS_BUDDYPRESS_IS_ACTIVE ): ?>

	<li class="notifications-icon menu-item custom-menu-link">

		<?php
			$notification = apply_filters( 'TieLabs/BuddyPress/notifications', '' );
			echo tie_component_button_bp_notifications();
		?>

		<div class="bp-notifications-menu components-sub-menu comp-sub-menu">
			<?php echo ( $notification['data'] ) ?>
		</div><!-- .components-sub-menu /-->

	</li><!-- .notifications-btn /-->
	<?php
endif;


// Cart
if( tie_get_option( $components_id.'-components_cart' ) && TIELABS_WOOCOMMERCE_IS_ACTIVE ):?>
	<li class="shopping-cart-icon menu-item custom-menu-link"><?php echo tie_component_button_cart(); ?></li><!-- .shopping-cart-btn /-->
	<?php
endif;


// Random
if( tie_get_option( $components_id.'-components_random' ) ): ?>
	<li class="random-post-icon menu-item custom-menu-link">
		<a href="<?php echo esc_url( add_query_arg( 'random-post', 1 ) ); ?>" class="random-post" title="<?php esc_html_e( 'Random Article', TIELABS_TEXTDOMAIN ) ?>" rel="nofollow">
			<span class="tie-icon-random" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Random Article', TIELABS_TEXTDOMAIN ) ?></span>
		</a>
	</li>
	<?php
endif;


// Slide sidebar
if( tie_get_option( $components_id.'-components_slide_area' ) ):?>
	<li class="side-aside-nav-icon menu-item custom-menu-link">
		<a href="#">
			<span class="tie-icon-navicon" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Sidebar', TIELABS_TEXTDOMAIN ) ?></span>
		</a>
	</li>
	<?php
endif;


// Skin Switcher
if( tie_get_option( $components_id.'-components_skin' ) ):
	?>
	<li class="skin-icon menu-item custom-menu-link">
		<a href="#" class="change-skin" title="<?php esc_html_e( 'Switch skin', TIELABS_TEXTDOMAIN ) ?>">
			<span class="tie-icon-moon change-skin-icon" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Switch skin', TIELABS_TEXTDOMAIN ) ?></span>
		</a>
	</li>
	<?php
endif;


// Search
if( tie_get_option( $components_id.'-components_search' ) ):

	if( tie_get_option( 'google_search_engine_id' ) ): ?>
		<li class="search-bar menu-item custom-menu-link" aria-label="<?php esc_html_e( 'Search', TIELABS_TEXTDOMAIN ); ?>">
			<?php tie_google_search(); ?>
		</li>
		<?php
	else:

		$live_search_class = tie_get_option( "$components_id-components_live_search" ) ? 'class="is-ajax-search" ' : '';

		if( tie_get_option( "$components_id-components_search_layout" ) == 'compact' ):?>
			<li class="search-compact-icon menu-item custom-menu-link">
				<a href="#" class="tie-search-trigger">
					<span class="tie-icon-search tie-search-icon" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Search for', TIELABS_TEXTDOMAIN ) ?></span>
				</a>
			</li>
			<?php

		else: ?>
			<li class="search-bar menu-item custom-menu-link" aria-label="<?php esc_html_e( 'Search', TIELABS_TEXTDOMAIN ); ?>">
				<form method="get" id="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<input id="search-input" <?php echo ( $live_search_class ); ?> inputmode="search" type="text" name="s" title="<?php esc_html_e( 'Search for', TIELABS_TEXTDOMAIN ) ?>" placeholder="<?php esc_html_e( 'Search for', TIELABS_TEXTDOMAIN ) ?>" />
					<button id="search-submit" type="submit">
						<span class="tie-icon-search tie-search-icon" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Search for', TIELABS_TEXTDOMAIN ) ?></span>
					</button>
				</form>
			</li>
			<?php
		endif;
	endif; // Google Search

endif;


// Featured Posts menu
if( $components_id == 'main-nav' && tie_get_option( 'featured_posts_menu' ) ):

	$query_args = array(
		'number'         => tie_get_option( 'featured_posts_menu_posts_number', 10 ),
		'tags'           => tie_get_option( 'featured_posts_menu_tags' ),
		'id'             => tie_get_option( 'featured_posts_menu_cat' ),
		'order'          => tie_get_option( 'featured_posts_menu_order' ),
		'asc_or_desc'    => tie_get_option( 'featured_posts_menu_asc_or_desc' ),
		'trending_posts' => tie_get_option( 'featured_posts_menu_trending_posts' )
	);

	$featured_query = tie_query( $query_args );

	if( $featured_query->have_posts() ){
			
		$title = tie_get_option( 'featured_posts_menu_title', esc_html__( 'Popular Articles', TIELABS_TEXTDOMAIN ) );

		if( tie_get_option( 'featured_posts_menu_title_break' ) ){
			$title = preg_replace('/\s+/', '</span><span>', trim( $title ) );
		}

		$class = tie_get_option( 'featured_posts_menu_counter' ) ? 'has-posts-counter' : '';

		?>

		<li id="menu-featured-posts" class="custom-menu-link menu mega-menu mega-links-<?php echo tie_get_option( 'featured_posts_menu_columns', 3 ); ?>col">
			<a class="menu-featured-posts-title" href="<?php echo tie_get_option( 'featured_posts_menu_url', '#' ); ?>">
				<?php echo tie_get_option( 'featured_posts_menu_title_number' ) ? '<strong>'. count( $featured_query->posts ) .'</strong>' : '<strong style="font-size:0;">.</strong>'; ?>
				<div class="menu-featured-posts-words">
					<span><?php echo ( $title ) ?></span>
				</div><!-- .menu-featured-posts-words -->
			</a><!-- .menu-featured-posts-title -->
			
			<div class="comp-sub-menu <?php echo esc_attr( $class ) ?>">
				<ul class="sub-menu-columns">
					<?php
					while ( $featured_query->have_posts() ){
						$featured_query->the_post();
						?>

					<li class="mega-link-column">
						<?php if ( has_post_thumbnail() && tie_get_option( 'featured_posts_menu_thumbnails' ) ): ?>
							<div class="post-widget-thumbnail">
								<?php tie_post_thumbnail();  ?>
							</div>
						<?php endif; ?>

						<h3 class="post-box-title">
							<a class="mega-menu-link" href="<?php the_permalink(); ?>"><?php tie_the_title(); ?></a>
						</h3>

						<?php if ( tie_get_option( 'featured_posts_menu_date' ) ): ?>
						<div class="post-meta clearfix">
							<?php tie_get_time(); ?>
						</div>
						<?php endif; ?>

					</li>
					<?php 
					}
					?>
				</ul>
			</div>
		</li>	

	<?php
		wp_reset_postdata();

	} // Have Posts
endif;


echo '</ul><!-- Components -->';