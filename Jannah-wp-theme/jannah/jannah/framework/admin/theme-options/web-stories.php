<?php
		
tie_build_theme_option(
	array(
		'title' => esc_html__( 'Web Stories Settings', TIELABS_TEXTDOMAIN ),
		'id'    => 'webstories-tab',
		'type'  => 'tab-title',
	));

if ( ! TIELABS_WEBSTORIES_IS_ACTIVE ){

	tie_build_theme_option(
		array(
			'text' => sprintf( esc_html__( 'You need to install the %s Plugin first.', TIELABS_TEXTDOMAIN ), '<a target="_blank" href="https://wordpress.org/plugins/web-stories/">Web Stories</a>' ),
			'type' => 'error',
		));

}
else{

	tie_build_theme_option(
		array(
			'title' => esc_html__( 'Stories Archives', TIELABS_TEXTDOMAIN ),
			'type'  => 'header',
		));


	if ( get_option( 'web_stories_archive' ) == 'disabled' ){

		tie_build_theme_option(
			array(
				'text' => esc_html__( 'Stories Archives is disabled, you can enable them from Go to Stories > Settings > Stories Archives', TIELABS_TEXTDOMAIN ),
				'type' => 'error',
			));
	}
	elseif ( get_option( 'web_stories_archive' ) == 'custom' ){

		$stories_page_id    = get_option( 'web_stories_archive_page_id' );
		$stories_page_tecxt = '';

		if( ! empty( $stories_page_id ) ){
			$get_the_page = get_post( $stories_page_id );

			if( ! is_null( $get_the_page ) ){
				$stories_page_tecxt = sprintf( esc_html__( 'Visit the archive page at %s', TIELABS_TEXTDOMAIN ), '<a href="'. get_permalink( $stories_page_id ) .'" target="_blank">'. $get_the_page->post_title .'</a>' );
			}
		}

		tie_build_theme_option(
			array(
				'text' => esc_html__( 'Default Stories Archives is disabled, Stories Archives option is set to a custom page.', TIELABS_TEXTDOMAIN ) .' '. $stories_page_tecxt,
				'type' => 'message',
			));

	}
	else{

		tie_build_theme_option(
			array(
				'text' => sprintf( esc_html__( 'Visit the archive page at %s', TIELABS_TEXTDOMAIN ), '<a href="'. get_post_type_archive_link( 'web-story' ) .'" target="_blank">'. get_post_type_archive_link( 'web-story' ) .'</a>' ),
				'type' => 'info',
			));

		tie_build_theme_option(
			array(
				'name'    => esc_html__( 'Layout', TIELABS_TEXTDOMAIN ),
				'id'      => 'web_stories_layout',
				'type'    => 'visual',
				'options' => array(
					'grid'  => array( esc_html__( 'Grid', TIELABS_TEXTDOMAIN ) => 'blocks/block-web_stories_grid.png' ),
					'list'	=> array( esc_html__( 'List', TIELABS_TEXTDOMAIN ) => 'blocks/block-web_stories_list.png' ),
				)));

		tie_build_theme_option(
			array(
				'name'    => esc_html__( 'Number of stories per page', TIELABS_TEXTDOMAIN ),
				'id'      => 'web_stories_number',
				'type'    => 'number',
				'default' => 10,
			));

		tie_build_theme_option(
			array(
				'name'    => esc_html__( 'Pagination', TIELABS_TEXTDOMAIN ),
				'id'      => 'web_stories_pagination',
				'type'    => 'radio',
				'options' => array(
					'next-prev' => esc_html__( 'Next and Previous', TIELABS_TEXTDOMAIN ),
					'numeric'   => esc_html__( 'Numeric',           TIELABS_TEXTDOMAIN ),
				)
			));

		tie_build_theme_option(
			array(
				'name'    => esc_html__( 'Number of Columns', TIELABS_TEXTDOMAIN ),
				'id'      => 'web_stories_columns',
				'type'    => 'select',
				'default' => 2,
				'options' => array( 
					1 => 1,
					2 => 2,
					3 => 3,
					4 => 4,
					5 => 5,
				) 
			));


		// Box, Grid, List
		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Display Author', TIELABS_TEXTDOMAIN ),
				'id'   => 'web_stories_author',
				'type' => 'checkbox',
			));
			
		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Display Date', TIELABS_TEXTDOMAIN ),
				'id'   => 'web_stories_date',
				'type' => 'checkbox',
			));
				
		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Sidebar Position', TIELABS_TEXTDOMAIN ),

				'id'      => 'web_stories_sidebar_pos',
				'type'    => 'visual',
				'options' => array(
					''      => array( esc_html__( 'Default', TIELABS_TEXTDOMAIN ) => 'default.png' ),
					'right'	=> array( esc_html__( 'Sidebar Right', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-right.png' ),
					'left'	=> array( esc_html__( 'Sidebar Left', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-left.png' ),
					'full'	=> array( esc_html__( 'Without Sidebar', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-full-width.png' ),
				)));

		tie_build_theme_option(
			array(
				'name'    => esc_html__( 'Custom Sidebar', TIELABS_TEXTDOMAIN ),
				'id'      => 'sidebar_web_stories',
				'type'    => 'select',
				'options' => TIELABS_ADMIN_HELPER::get_sidebars(),
			));
			
	}

	//

	$web_stories_position = array(
		'after_header'  => esc_html__( 'Below Header Stories', TIELABS_TEXTDOMAIN ),
		'before_footer' => esc_html__( 'Above Footer Stories', TIELABS_TEXTDOMAIN ),
	);

	foreach ( $web_stories_position as $position => $title ) {

		$option_prefix = 'web_stories_'. $position;

		tie_build_theme_option(
			array(
				'title' => $title,
				'type'  => 'header',
			));
		
		tie_build_theme_option(
			array(
				'name'   => esc_html__( 'Enable', TIELABS_TEXTDOMAIN ),
				'id'     => 'web_stories_'. $position,
				'type'   => 'checkbox',
				'toggle' => '#web_stories_'. $position .'-options',
			));

		echo '<div id="web_stories_'. $position .'-options">';

			tie_build_theme_option(
				array(
					'id'      => $option_prefix.'_layout',
					'type'    => 'visual',
					'toggle'  => array(
					//	'carousel' => '#'.$option_prefix.'_author-item, #'.$option_prefix.'_date-item',
						'circles'  => '#'.$option_prefix.'_title-item, #'.$option_prefix.'_circle_size-item',
						'grid'     => '#'.$option_prefix.'_columns-item, #'.$option_prefix.'_author-item, #'.$option_prefix.'_date-item',
						'list'     => '#'.$option_prefix.'_columns-item, #'.$option_prefix.'_author-item, #'.$option_prefix.'_date-item',
					),
					'options' => array(
						// 'carousel'  => array( esc_html__( 'Box Carousel', TIELABS_TEXTDOMAIN )    => 'blocks/block-web_stories_carousel.png' ),
						'circles'   => array( esc_html__( 'Circle Carousel', TIELABS_TEXTDOMAIN ) => 'blocks/block-web_stories_circles.png' ),
						'grid'      => array( esc_html__( 'Grid', TIELABS_TEXTDOMAIN )            => 'blocks/block-web_stories_grid.png' ),
						'list'	    => array( esc_html__( 'List', TIELABS_TEXTDOMAIN )            => 'blocks/block-web_stories_list.png' ),
					)));


				$locations = array(
					'all'    => esc_html__( 'All', TIELABS_TEXTDOMAIN ),
					'home'   => esc_html__( 'Home', TIELABS_TEXTDOMAIN ),
					'post'   => esc_html__( 'Posts', TIELABS_TEXTDOMAIN ),
					'page'   => esc_html__( 'Pages', TIELABS_TEXTDOMAIN ),
					'search' => esc_html__( 'Search Results', TIELABS_TEXTDOMAIN ),
					'404'    => esc_html__( '404 pages', TIELABS_TEXTDOMAIN ),
					'author_archive'    => esc_html__( 'Author Archives', TIELABS_TEXTDOMAIN ),
				);

				$tax = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );

				if( ! empty( $tax ) && is_array( $tax ) ){
					foreach ( $tax as $key => $data ) {
						$locations[ $key ] = $data->labels->name;
					}
				}

				$locations['archive'] = esc_html__( 'Other Archive Pages', TIELABS_TEXTDOMAIN );


			tie_build_theme_option(
				array(
					'name'    => esc_html__( 'Show on', TIELABS_TEXTDOMAIN ),
					'id'      => $option_prefix.'_include',
					'type'    => 'checkbox-multiple',
					'options' => $locations,
				));
					

			tie_build_theme_option(
				array(
					'name'    => esc_html__( 'Categories', TIELABS_TEXTDOMAIN ),
					'id'      => $option_prefix.'_cat',
					'type'    => 'checkbox-multiple',
					'options' => TIELABS_ADMIN_HELPER::get_web_stories_categories(),
				));

			tie_build_theme_option(
				array(
					'name' => esc_html__( 'Number of Stories to show', TIELABS_TEXTDOMAIN ),
					'id'   => $option_prefix.'_number',
					'type' => 'number',
				));
				
			// Grid, List
			tie_build_theme_option(
				array(
					'name'    => esc_html__( 'Number of Columns', TIELABS_TEXTDOMAIN ),
					'id'      => $option_prefix.'_columns',
					'type'    => 'select',
					'class'   => $option_prefix.'_layout',
					'default' => 2,
					'options' => array( 
						1 => 1,
						2 => 2,
						3 => 3,
						4 => 4,
						5 => 5,
					) 
				));

			// Circles
			tie_build_theme_option(
				array(
					'name'  => esc_html__( 'Display Title', TIELABS_TEXTDOMAIN ),
					'id'    => $option_prefix.'_title',
					'class' => $option_prefix.'_layout',
					'type'  => 'checkbox',
				));

			tie_build_theme_option(
				array(
					'name'  => esc_html__( 'Circle Size (px)', TIELABS_TEXTDOMAIN ),
					'id'    => $option_prefix.'_circle_size',
					'class' => $option_prefix.'_layout',
					'type'  => 'number',
				));

			// Box, Grid, List
			tie_build_theme_option(
				array(
					'name'  => esc_html__( 'Display Author', TIELABS_TEXTDOMAIN ),
					'id'    => $option_prefix.'_author',
					'class' => $option_prefix.'_layout',
					'type'  => 'checkbox',
				));
				
			tie_build_theme_option(
				array(
					'name'  => esc_html__( 'Display Date', TIELABS_TEXTDOMAIN ),
					'id'    => $option_prefix.'_date',
					'class' => $option_prefix.'_layout',
					'type'  => 'checkbox',
				));

		echo '</div>';
	}


}


		