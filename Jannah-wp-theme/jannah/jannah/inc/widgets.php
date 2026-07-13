<?php

/**
 * Register Widgets
 */
add_action( 'widgets_init', 'tie_widgets_init' );
function tie_widgets_init(){

	// Remove recent comments style
	add_filter( 'show_recent_comments_widget_style', '__return_false' );

	// Widgets icon
	$widget_icon = tie_get_option( 'widgets_icon' ) ? '<span class="widget-title-icon tie-icon"></span>' : '';

	// Widget HTML markup
	$before_widget = apply_filters( 'TieLabs/Widgets/before_widget', '<div id="%1$s" class="container-wrapper widget %2$s">' );
	$after_widget  = apply_filters( 'TieLabs/Widgets/after_widget',  '<div class="clearfix"></div></div><!-- .widget /-->' );
	$before_title  = apply_filters( 'TieLabs/Widgets/before_title',  '<div '. tie_box_class( 'widget-title', false ) .'><div class="the-subtitle">' );
	$after_title   = apply_filters( 'TieLabs/Widgets/after_title',   $widget_icon .'</div></div>' );

	// Default Sidebar
	register_sidebar( array(
		'id'            => 'primary-widget-area',
		'name'          => esc_html__( 'Primary Widget Area', TIELABS_TEXTDOMAIN ),
		'before_widget' => $before_widget,
		'after_widget'  => $after_widget,
		'before_title'  => $before_title,
		'after_title'   => $after_title,
	));

	// Slide Sidebar
	register_sidebar( array(
		'id'            => 'slide-sidebar-area',
		'name'          => esc_html__( 'Slide Widget Area', TIELABS_TEXTDOMAIN ),
		'before_widget' => $before_widget,
		'after_widget'  => $after_widget,
		'before_title'  => $before_title,
		'after_title'   => $after_title,
	));

	// WooCommerce Sidebar
	if ( TIELABS_WOOCOMMERCE_IS_ACTIVE ){
		register_sidebar( array(
			'id'            => 'shop-widget-area',
			'name'          => esc_html__( 'Shop - For WooCommerce Pages', TIELABS_TEXTDOMAIN ),
			'description'   => esc_html__( 'This widget area uses in the WooCommerce pages.', TIELABS_TEXTDOMAIN ),
			'before_widget' => $before_widget,
			'after_widget'  => $after_widget,
			'before_title'  => $before_title,
			'after_title'   => $after_title,
		));
	}

	// Custom Sidebars
	$sidebars = tie_get_option( 'sidebars' );
	if( ! empty( $sidebars ) && is_array( $sidebars ) ) {
		foreach ($sidebars as $sidebar){
			register_sidebar( array(
				'id' 			      => sanitize_title($sidebar),
				'name'          => $sidebar,
				'before_widget' => $before_widget,
				'after_widget' 	=> $after_widget,
				'before_title' 	=> $before_title,
				'after_title' 	=> $after_title,
			));
		}
	}

	// Footer Widgets
	$footer_widgets_areas = array(
		'area_1' => esc_html__( 'First Footer',  TIELABS_TEXTDOMAIN ),
		'area_2' => esc_html__( 'Second Footer', TIELABS_TEXTDOMAIN )
	);

	foreach( $footer_widgets_areas as $name => $description ){

		if( tie_get_option( 'footer_widgets_'.$name ) ){

			$footer_widgets = tie_get_option( 'footer_widgets_layout_'.$name );

			# Footer Widgets Column 1
			register_sidebar( array(
				'id'            => 'first-footer-widget-'.$name,
				'name'          => $description. ' - '.esc_html__( '1st Column', TIELABS_TEXTDOMAIN ),
				'before_widget' => $before_widget,
				'after_widget'  => $after_widget,
				'before_title'  => $before_title,
				'after_title'   => $after_title,
			));


			# Footer Widgets Column 2
			if( $footer_widgets == 'footer-2c'      ||
				  $footer_widgets == 'narrow-wide-2c' ||
				  $footer_widgets == 'wide-narrow-2c' ||
				  $footer_widgets == 'footer-3c'      ||
				  $footer_widgets == 'wide-left-3c'   ||
				  $footer_widgets == 'wide-right-3c'  ||
				  $footer_widgets == 'footer-4c'      ){

						register_sidebar( array(
							'id' 			      => 'second-footer-widget-'.$name,
							'name'			    => $description. ' - '.esc_html__( '2d Column', TIELABS_TEXTDOMAIN ),
							'before_widget' => $before_widget,
							'after_widget'  => $after_widget,
							'before_title'  => $before_title,
							'after_title'   => $after_title,
						));
					}


			# Footer Widgets Column 3
			if( $footer_widgets == 'footer-3c'     ||
				  $footer_widgets == 'wide-left-3c'  ||
				  $footer_widgets == 'wide-right-3c' ||
				  $footer_widgets == 'footer-4c'     ){

						register_sidebar( array(
							'id'            => 'third-footer-widget-'.$name,
							'name'          => $description. ' - '.esc_html__( '3rd Column', TIELABS_TEXTDOMAIN ),
							'before_widget' => $before_widget,
							'after_widget'  => $after_widget,
							'before_title'  => $before_title,
							'after_title'   => $after_title,
						));
					}


			# Footer Widgets Column 4
			if( $footer_widgets == 'footer-4c' ){
				register_sidebar( array(
					'id'            => 'fourth-footer-widget-'.$name,
					'name'          => $description. ' - '.esc_html__( '4th Column', TIELABS_TEXTDOMAIN ),
					'before_widget' => $before_widget,
					'after_widget'  => $after_widget,
					'before_title'  => $before_title,
					'after_title'   => $after_title,
				));
			}

		}
	}


	$custom_widgets = get_option( 'tie_sidebars_widgets', array() );

	foreach ( $custom_widgets as $post_id => $sections ) {
		$i = 1;

		$name = 'Page: #' .$post_id;
		if( is_admin() && ! wp_doing_ajax() ){
			$name = get_the_title( $post_id ); // extra query each
		}

		if( ! empty( $sections ) && is_array( $sections ) ){
			foreach ( $sections as $section => $widgets ) {
				register_sidebar(array(
					'name'          => $name . ' - '. sprintf( esc_html__( 'Section #%s', TIELABS_TEXTDOMAIN ), $i ),
					'id'            => $section,
					'before_widget' => $before_widget,
					'after_widget'  => $after_widget,
					'before_title'  => $before_title,
					'after_title'   => $after_title,
				));

				$i++;
			}
		}
	}
}



/**
 * Import the Default Widgets
 */
$theme_widgets = array(
	'ads',
	'tabs',
	'posts',
	'login',
	'about',
	'flickr',
	'author',
	'authors-list',
	'social',
	'slider',
	'weather',
	'youtube',
	'twitter',
	'facebook',
	'text-html',
	'instagram',
	'newsletter',
	'soundcloud',
	'categories',
	'categories-grid',
	'comments-avatar',
	'social-counters',
	'snapchat',
	'tiktok',
	'patreon',
	'buymeacoffee',
	'twitter-embedded',
);

$theme_widgets = apply_filters( 'TieLabs/Widgets/default_widgets', $theme_widgets );

if( ! empty( $theme_widgets ) && is_array( $theme_widgets ) ){
	foreach ( $theme_widgets as $widget ){
		locate_template( "framework/widgets/$widget.php", true, true );
	}
}


/**
 * Number of tags option
 */
add_filter( 'in_widget_form', 'tie_widgets_tag_cloud_options', 9, 10 );
function tie_widgets_tag_cloud_options( $widget = '', $retur = '', $instance = '' ) {

	if( $widget->id_base != 'tag_cloud' ){
		return;
	}

	$number = isset( $instance['number'] ) ? $instance['number'] : '';
	?>
	<p>
		<label for="<?php echo esc_attr( $widget->get_field_id('number') ); ?>"><?php esc_html_e( 'Number of tags to show: ', TIELABS_TEXTDOMAIN ); ?></label>
		<input id="<?php echo esc_attr( $widget->get_field_id('number') ); ?>" name="<?php echo esc_attr( $widget->get_field_name('number') ); ?>" type="number" min="1" size="3" value="<?php echo esc_attr( $number ); ?>" />
	</p>
	
	<?php
}

// Number of tags option
add_filter( 'widget_tag_cloud_args', 'tie_widgets_tag_cloud_number', 10, 2 );
function tie_widgets_tag_cloud_number( $args = array(), $instance = array() ){

	if( ! empty( $instance['number'] ) ){
		$args['number'] = $instance['number'];
	};

	return $args;
}


/**
 * Custom Widget colors
 */
// Add the custom colors options in the widgets
add_filter( 'in_widget_form', 'tie_widgets_custom_colors_options', 10, 10 );
function tie_widgets_custom_colors_options( $widget = '', $retur = '', $instance = '' ) {

	if( ! apply_filters( 'TieLabs/Widgets/custom_colors', true ) ){
		return;
	}

	if( in_array( $widget->id_base, array( 'tie-weather-widget', 'twitter_embedded_timeline_widget', 'tie-widget-categories-grid' ) ) ){
		return;
	}

	$bg_color    = isset( $instance['bg_color'] )    ? $instance['bg_color']    : '';
	$bg_color_2  = isset( $instance['bg_color_2'] )  ? $instance['bg_color_2']  : '';
	$text_color  = isset( $instance['text_color'] )  ? $instance['text_color']  : '';
	$bg_image    = isset( $instance['bg_image'] )    ? $instance['bg_image']    : '';
	$links_color = isset( $instance['links_color'] ) ? $instance['links_color'] : '';
	$links_color_hover = isset( $instance['links_color_hover'] ) ? $instance['links_color_hover'] : '';

	?>
	<hr />
	<p style="float:left; width: 49%; margin-right: 0.5% !important; margin-bottom: 0; font-size: 95%;">
		<label for="<?php echo esc_attr( $widget->get_field_id('bg_color') ); ?>" style="display:block;"><?php esc_html_e( 'Background Color', TIELABS_TEXTDOMAIN ); ?></label>
		<input class="widefat" style="margin-top: 4px; width: 98%;" id="<?php echo esc_attr( $widget->get_field_id('bg_color') ); ?>" name="<?php echo esc_attr( $widget->get_field_name('bg_color') ); ?>" type="text" value="<?php echo esc_attr( $bg_color ); ?>" />
	</p>
	
	<p style="float:left; width: 49%; margin-right: 0.5% !important; margin-bottom: 0; font-size: 95%;">
		<label for="<?php echo esc_attr( $widget->get_field_id('bg_color_2') ); ?>" style="display:block;"><?php esc_html_e( 'Background Color 2', TIELABS_TEXTDOMAIN ); ?></label>
		<input class="widefat" style="margin-top: 4px; width: 98%;" id="<?php echo esc_attr( $widget->get_field_id('bg_color_2') ); ?>" name="<?php echo esc_attr( $widget->get_field_name('bg_color_2') ); ?>" type="text" value="<?php echo esc_attr( $bg_color_2 ); ?>" />
	</p>
	
	<div class="clear"></div>
	<p>
		<label for="<?php echo esc_attr( $widget->get_field_id('bg_image') ); ?>" style="display:block;"><?php esc_html_e( 'Background Image', TIELABS_TEXTDOMAIN ); ?></label>
		<input class="widefat" style="margin-top: 4px;" id="<?php echo esc_attr( $widget->get_field_id('bg_image') ); ?>" name="<?php echo esc_attr( $widget->get_field_name('bg_image') ); ?>" type="text" value="<?php echo esc_attr( $bg_image ); ?>" placeholder="https://" />
	</p>

	<div class="clear"></div>

	<p style="float:left; width: 33%; margin-bottom: 0; font-size: 95%;">
		<label for="<?php echo esc_attr( $widget->get_field_id('text_color') ); ?>" style="display:block;"><?php esc_html_e( 'Text Color', TIELABS_TEXTDOMAIN ); ?></label>
		<input class="widefat" style="margin-top: 4px; width: 98%;" id="<?php echo esc_attr( $widget->get_field_id('text_color') ); ?>" name="<?php echo esc_attr( $widget->get_field_name('text_color') ); ?>" type="text" value="<?php echo esc_attr( $text_color ); ?>" />
	</p>

	<p style="float:left; width: 33%; margin-bottom: 0; font-size: 95%;">
		<label for="<?php echo esc_attr( $widget->get_field_id('links_color') ); ?>" style="display:block;"><?php esc_html_e( 'Links Color', TIELABS_TEXTDOMAIN ); ?></label>
		<input class="widefat" style="margin-top: 4px; width: 98%;" id="<?php echo esc_attr( $widget->get_field_id('links_color') ); ?>" name="<?php echo esc_attr( $widget->get_field_name('links_color') ); ?>" type="text" value="<?php echo esc_attr( $links_color ); ?>" />
	</p>

	<p style="float:left; width: 33%; margin-bottom: 0; font-size: 95%;">
		<label for="<?php echo esc_attr( $widget->get_field_id('links_color_hover') ); ?>" style="display:block;"><?php esc_html_e( 'Active Links Color', TIELABS_TEXTDOMAIN ); ?></label>
		<input class="widefat" style="margin-top: 4px; width: 98%;" id="<?php echo esc_attr( $widget->get_field_id('links_color_hover') ); ?>" name="<?php echo esc_attr( $widget->get_field_name('links_color_hover') ); ?>" type="text" value="<?php echo esc_attr( $links_color_hover ); ?>" />
	</p>

	<div class="clear"></div>
	<br />
	<?php
}

// Save the custom colors
add_filter( 'widget_update_callback', 'tie_widgets_save_custom_options', 10, 2 );	
function tie_widgets_save_custom_options( $instance, $new_instance ) {
	return wp_parse_args( $new_instance, $instance );
}

// Append the styles in the frontend
add_action( 'dynamic_sidebar', 'tie_widgets_render_custom_colors' );
function tie_widgets_render_custom_colors( $widget = array() ){

	if( ! apply_filters( 'TieLabs/Widgets/custom_colors', true ) ){
		return;
	}
	
	if( is_admin() || empty( $widget['id'] ) || empty( $widget['params'][0]['number'] ) ){
		return;
	}

	$widget_id     = $widget['id'];
	$widget_number = $widget['params'][0]['number'];

	$id_base = str_replace( '-'.$widget_number, '', $widget_id );
	$instance = get_option( 'widget_' . $id_base );

	if( empty( $instance ) || ! is_array( $instance ) ){
		return;
	}

	$instance = $instance[ $widget_number ];

	# Colors
	$bg_color   = ! empty( $instance['bg_color'] )   ? '#'.str_replace( '#', '', $instance['bg_color']   ) : '';
	$bg_color_2 = ! empty( $instance['bg_color_2'] ) ? '#'.str_replace( '#', '', $instance['bg_color_2'] ) : '';
	$text_color = ! empty( $instance['text_color'] ) ? '#'.str_replace( '#', '', $instance['text_color'] ) : '';
	$bg_image   = ! empty( $instance['bg_image'] )   ? $instance['bg_image'] : '';
	$links_color = ! empty( $instance['links_color'] ) ? '#'.str_replace( '#', '', $instance['links_color'] ) : '';
	$links_color_hover = ! empty( $instance['links_color_hover'] ) ? '#'.str_replace( '#', '', $instance['links_color_hover'] ) : '';

	if ( ! empty( $bg_color ) || ! empty( $bg_color_2 ) || ! empty( $text_color ) || ! empty( $bg_image ) || ! empty( $links_color ) || ! empty( $links_color_hover ) ){
		$out = "<style scoped type=\"text/css\">";

		if ( ! empty( $text_color ) ){
			$out .= "
				#{$widget_id},
				#{$widget_id} .post-meta,
				#{$widget_id} .widget-title-icon,
				#{$widget_id} .widget-title .the-subtitle,
				#{$widget_id} .subscribe-widget-content h3{
					color: $text_color;
				}
			";
		}

		if ( ! empty( $links_color ) ){
			$out .= "
				#{$widget_id} a:not(:hover):not(.button),
				#{$widget_id} a.post-title:not(:hover),
				#{$widget_id} .post-title a:not(:hover){
					color: $links_color;
				}
			";
		}

		if ( ! empty( $links_color_hover ) ){
			$out .= "
				#{$widget_id} a:hover:not(.button),
				#{$widget_id} a.post-title:hover,
				#{$widget_id} .post-title a:hover{
					color: $links_color_hover;
				}
			";
		}

		if ( ! empty( $bg_color ) ){
			$out .= "
				#{$widget_id}{
					background-color: $bg_color !important;
					border: none;
				}

				#{$widget_id}.widget-content-only{
					padding: 20px;
				}
			";

			if( tie_get_option('boxes_style') == 2 && ! empty( $widget['callback'][0]->id_base ) && $widget['callback'][0]->id_base != 'tie-weather-widget' ){
				$out .= "
					.magazine2 #{$widget_id}{
						padding: 20px;
					}
				";
			}
		}

		if( ! empty( $bg_image ) ){
			$out .= "
				#{$widget_id}{
					background-image: url( $bg_image ) !important;
					background-repeat: no-repeat;
					background-size: cover;
				}
			";
		}
		elseif ( ! empty( $bg_color ) && ! empty( $bg_color_2 ) ){
			$out .= "
				#{$widget_id}{
					". TIELABS_STYLES::gradiant( $bg_color, $bg_color_2 ) ."
				}
			";
		}

		echo ( $out ) ."</style>";
	}

}

