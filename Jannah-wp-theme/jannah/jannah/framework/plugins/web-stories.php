<?php
/**
 * Web Stories
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly


/**
 *  
 */
function tie_get_web_stories( $args = array() ){

	if( ! TIELABS_WEBSTORIES_IS_ACTIVE || empty( $args ) ){
		return;
	}

	$style = str_replace( 'web_stories_', '', $args['style'] );
	
	$story_attrs = array(
		'view_type'          => $style,
		'show_title'         => false,
		'show_excerpt'       => false,
		'show_author'        => false,
		'show_date'          => false,
		'show_archive_link'  => false,
		'archive_link_label' => 'LINK',
		'circle_size'        => ! empty( $args['web_stories_circle_size'] ) ? (int) $args['web_stories_circle_size'] : 120,
		'sharp_corners'      => false,
		'image_alignment'    => is_rtl() ? 'right' : 'left',
		'number_of_columns'  => ! empty( $args['web_stories_columns'] ) ? (int) $args['web_stories_columns'] : 2,
		'class'              => 'web-stories-list-block',
	);
	
	if( $style == 'circles' ){
		if( ! empty( $args['web_stories_title'] ) ){
			$story_attrs['show_title'] = true;
		}
	}
	else{
		$story_attrs['show_title']   = true;
		$story_attrs['show_excerpt'] = true;

		if( ! empty( $args['web_stories_date'] ) ){
			$story_attrs['show_date'] = true;
		}
		if( ! empty( $args['web_stories_author'] ) ){
			$story_attrs['show_author'] = true;
		}
	}
	
	$story_args = array(
		'order'          => 'DESC',
		'posts_per_page' => ! empty( $args['web_stories_number'] ) ? (int) $args['web_stories_number'] : 10,
	);

	if( ! empty( $args['web_stories_cat'] ) && is_array( $args['web_stories_cat'] ) ){
		$story_args['tax_query'] = array(
			array(
				'taxonomy' => 'web_story_category',
				'field'    => 'term_id',
				'terms'    => $args['web_stories_cat'],
			),
		);
	}

	if( is_post_type_archive( 'web-story' ) && get_query_var( 'paged' ) ){
		$story_args['paged'] = get_query_var( 'paged' );
	}
	
	if( $style == 'list' ){
		echo '
		<style>
			@media (min-width: 992px) {
				.is-view-type-list .web-stories-list__inner-wrapper > * {
					flex: 0 0 '. ( 100 / $story_attrs['number_of_columns'] ) .'%;
					margin: 0;
				}
			}
		</style>
		';
	}
			
	$story_query = new \Google\Web_Stories\Story_Query( $story_attrs, $story_args );
	echo $story_query->render();
}


/**
 * 
 */
add_action( 'pre_get_posts', 'target_main_category_query_with_conditional_tags' );
function target_main_category_query_with_conditional_tags( $query ) {

	if ( ! is_admin() && $query->is_main_query() ) {
		if ( TIELABS_WEBSTORIES_IS_ACTIVE && is_post_type_archive( 'web-story' ) ) {
			$query->set( 'posts_per_page', tie_get_option( 'web_stories_number', 10 ) );
		}
	}
}


if( TIELABS_WEBSTORIES_IS_ACTIVE ){
	add_action( 'TieLabs/after_header',  'tie_web_stories_render' );
	add_action( 'TieLabs/before_footer', 'tie_web_stories_render' );
	function tie_web_stories_render(){

		global $wp_current_filter;
		$position = end( $wp_current_filter );

		if( ! empty( $position ) ){

			$position = str_replace( 'TieLabs/', 'web_stories_', $position );

			if( tie_get_option( $position ) ){

				$args = array(
					'style'                   => tie_get_option( $position.'_layout', 'carousel' ),
					'web_stories_columns'     => tie_get_option( $position.'_columns', 2 ),
					'web_stories_number'      => tie_get_option( $position.'_number', 10 ),
					'web_stories_cat'         => tie_get_option( $position.'_cat' ),
					'web_stories_author'      => tie_get_option( $position.'_author' ),
					'web_stories_date'        => tie_get_option( $position.'_date' ),
					'web_stories_title'       => tie_get_option( $position.'_title' ),
					'web_stories_title'       => tie_get_option( $position.'_title' ),
					'web_stories_circle_size' => tie_get_option( $position.'_circle_size' ),
				);

				if( TIELABS_HELPER::check_include_option( $position.'_include' ) ){

					echo '<div id="'. $position .'" class="container web-stories-section">';
						tie_get_web_stories( $args );
					echo '</div>';
				}

			}
		}
	}
}
