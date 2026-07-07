<?php

if( ! class_exists( 'TIE_POSTS_LIST' ) ) {

	/**
	 * Widget API: TIE_Posts_List class
	 */
	 class TIE_POSTS_LIST extends WP_Widget {


		public function __construct(){
			$widget_ops = array( 'classname' => 'posts-list' );
			parent::__construct( 'posts-list-widget', apply_filters( 'TieLabs/theme_name', 'TieLabs' ) .' - '. esc_html__( 'Posts list', TIELABS_TEXTDOMAIN), $widget_ops );
		}

		/**
		 * Outputs the content for the widget instance.
		 */
		public function widget( $args, $instance ){

			/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
			$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

			// Query arguments
			$no_of_posts   = ! empty( $instance['no_of_posts'] ) ? $instance['no_of_posts'] : 5;
			$offset        = ! empty( $instance['offset'] )      ? $instance['offset']      : '';
			$pagination    = ! empty( $instance['pagination'] )  ? $instance['pagination']  : false;
			$posts_order   = ! empty( $instance['posts_order'] ) ? $instance['posts_order'] : 'latest';
			$tags          = ! empty( $instance['tags'] )        ? $instance['tags']        : '';
			$asc_or_desc   = ! empty( $instance['asc_or_desc'] ) ? $instance['asc_or_desc'] : '';
			$is_trending   = ! empty( $instance['trending_posts'] ) ? true : false;
			$limit_published_days = ! empty( $instance['limit_published_days'] ) ? $instance['limit_published_days'] : '';


			$cats_id = '';
			if( ! empty( $instance['cats_id'] ) ) {
				$cats_id = is_array( $instance['cats_id'] ) ? $instance['cats_id'] : explode ( ',', $instance['cats_id'] );
			}

			$before_posts  = '<ul class="posts-list-items widget-posts-wrapper">';
			$after_posts   = '</ul>';

			$class = 'widget-posts-list-container';

			$style_args = array();

			
			if ( strpos( $posts_order, 'related' ) !== false ){
				// Return If this is a related posts and we are not in the post single page
				if( ! is_single() ) {
					return;
				}

				// If the Post doesn't have any tags, return
				if( $posts_order == 'related-tag' ){
					$post_tags = get_the_terms( get_the_ID(), 'post_tag' );
					if( empty( $post_tags ) ){
						return;
					}
				}
			}

			$query_args = array(
				'number' => $no_of_posts,
				'id'     => $cats_id,
				'tags'   => $tags,
				'offset' => $offset,
				'order'  => $posts_order,
				'current_post'   => get_the_ID(),
				'asc_or_desc'    => $asc_or_desc,
				'trending_posts' => $is_trending,
				'limit_published_days' => $limit_published_days,
			);

			// Style
			$layouts = array(
				1  => '',
				2  => 'timeline-widget',
				3  => 'posts-list-big-first has-first-big-post',
				4  => 'posts-list-bigs',
				5  => 'posts-list-half-posts',
				6  => 'posts-pictures-widget',
				7  => 'posts-list-counter',
				8  => 'posts-authors',
				9  => 'posts-inverted',
				10  => 'posts-list-circle',
				11  => 'posts-list-circle posts-inverted',
			);

			if( ! empty( $instance['style'] ) && ! empty( $layouts[ $instance['style'] ] ) ) {
				$class .= ' '.$layouts[ $instance['style'] ];

				if( $instance['style'] == 2 ){
					$style_args['style'] = 'timeline';
				}

				elseif( $instance['style'] == 3 ){
					$style_args['thumbnail_first'] = TIELABS_THEME_SLUG.'-image-large';
					$style_args['review_first']    = 'large';
				}

				elseif( $instance['style'] == 4 ){
					$style_args['thumbnail']  = TIELABS_THEME_SLUG.'-image-large';
					$style_args['review']     = 'large';
					$style_args['show_score'] = false;
				}

				elseif( $instance['style'] == 5 ){
					$style_args['thumbnail'] = TIELABS_THEME_SLUG.'-image-large';
				}

				elseif( $instance['style'] == 6 ){
					$style_args['style'] = 'grid';
					$before_posts = '<div class="tie-row widget-posts-wrapper">';
					$after_posts  = '</div>';
				}

				elseif( $instance['style'] == 8 ){
					$style_args['style'] = 'authors';
					$before_posts  = '<ul class="posts-list-items recent-comments-widget widget-posts-wrapper">';
				}

				elseif( $instance['style'] == 10 || $instance['style'] == 11 ){
					$style_args['thumbnail'] = 'thumbnail';
				}
			}

			// Media Icon
			if( ! empty( $instance['media_overlay'] ) ){
				$style_args['media_icon'] = true;
				$class .= ' media-overlay';
			}

			// Title Length
			if( ! empty( $instance['title_length'] ) ){
				$style_args['title_length'] = $instance['title_length'];
			}

			// Exclude Current Post
			if( ! empty( $instance['exclude_current'] ) && is_single() ){
				$style_args['exclude_current'] = get_the_id();
			}

			// --
			$data_attr = '';

			if( $pagination ){

				$style_pagination_args = $style_args;

				if( $pagination == 'load-more' ){
					if( $instance['style'] == 3 ){
						$style_pagination_args['thumbnail_first'] = false;
						$style_pagination_args['review_first']    = false;
					}
				}

				$data_attr = ' data-current="1" data-query="'. str_replace( '"', '\'', wp_json_encode( $query_args ) ) .'" data-style="'. str_replace( '"', '\'', wp_json_encode( $style_pagination_args ) ) .'"';
			}

			// Print the widget
			echo ( $args['before_widget'] );

			if ( ! empty($instance['title']) ){
				echo ( $args['before_title'] . $instance['title'] . $args['after_title'] );
			}

			echo '<div class="widget-posts-list-wrapper">';

				echo '<div class="'. $class .'" '. $data_attr .'>';
					echo ( $before_posts );

						tie_widget_posts( $query_args, $style_args );

					echo ( $after_posts );
				echo "</div>";


				if( $pagination ){
					echo '<div class="widget-pagination-wrapper">';

					if( $pagination == 'show-more' ){
						echo '<a class="widget-pagination next-posts show-more-button" href="#" data-text="'. esc_html__( 'Show More', TIELABS_TEXTDOMAIN ) .'">'. esc_html__( 'Show More', TIELABS_TEXTDOMAIN ) .'</a>';
					}
					elseif( $pagination == 'load-more' ){
						echo '<a class="widget-pagination next-posts show-more-button load-more-button" href="#" data-text="'. esc_html__( 'Load More', TIELABS_TEXTDOMAIN ) .'">'. esc_html__( 'Load More', TIELABS_TEXTDOMAIN ) .'</a>';
					}
					elseif( $pagination == 'next-prev' ){
						echo '
							<ul class="slider-arrow-nav">
								<li>
									<a class="widget-pagination prev-posts pagination-disabled" href="#">
										<span class="tie-icon-angle-left" aria-hidden="true"></span>
										<span class="screen-reader-text">'. esc_html__( 'Previous page', TIELABS_TEXTDOMAIN ) .'</span>
									</a>
								</li>
								<li>
									<a class="widget-pagination next-posts" href="#">
										<span class="tie-icon-angle-right" aria-hidden="true"></span>
										<span class="screen-reader-text">'. esc_html__( 'Next page', TIELABS_TEXTDOMAIN ) .'</span>
									</a>
								</li>
							</ul>
						';
					}
					echo '</div>';
				}

			echo '</div>'; // .widget-posts-list-wrapper

			echo ( $args['after_widget'] );
		}


		/**
		 * Handles updating settings for widget instance.
		 */
		public function update( $new_instance, $old_instance ){

			$instance                    = $old_instance;
			$instance['title']           = sanitize_text_field( $new_instance['title'] );
			$instance['no_of_posts']     = $new_instance['no_of_posts'];
			$instance['title_length']    = $new_instance['title_length'];
			$instance['posts_order']     = $new_instance['posts_order'];
			$instance['pagination']      = $new_instance['pagination'];
			$instance['media_overlay']   = ! empty( $new_instance['media_overlay'] )   ? 'true' : false;
			$instance['exclude_current'] = ! empty( $new_instance['exclude_current'] ) ? 'true' : false;
			$instance['trending_posts']  = ! empty( $new_instance['trending_posts'] )  ? 'true' : false;
			$instance['style']           = $new_instance['style'];
			$instance['tags']            = $new_instance['tags'];
			$instance['offset']          = $new_instance['offset'];
			$instance['asc_or_desc']     = $new_instance['asc_or_desc'];
			$instance['limit_published_days'] = $new_instance['limit_published_days'];

			if( ! empty( $new_instance['cats_id'] ) && is_array( $new_instance['cats_id'] ) ){
				$instance['cats_id'] = $new_instance['cats_id'];
			}
			else{
				$instance['cats_id'] = false;
			}

			return $instance;
		}

		/**
		 * Outputs the settings form for the widget.
		 */
		public function form( $instance ){

			$defaults = array( 'title' => esc_html__('Recent Posts', TIELABS_TEXTDOMAIN) , 'no_of_posts' => '5', 'posts_order' => 'latest' );
			$instance = wp_parse_args( (array) $instance, $defaults );

			$title           = ! empty( $instance['title'] )           ? $instance['title'] : '';
			$no_of_posts     = ! empty( $instance['no_of_posts'] )     ? $instance['no_of_posts'] : 5;
			$title_length    = ! empty( $instance['title_length'] )    ? $instance['title_length'] : '';
			$offset          = ! empty( $instance['offset'] )          ? $instance['offset'] : '';
			$asc_or_desc     = ! empty( $instance['asc_or_desc'] )     ? $instance['asc_or_desc'] : '';
			$posts_order     = ! empty( $instance['posts_order'] )     ? $instance['posts_order'] : 'latest';
			$style           = ! empty( $instance['style'] )           ? $instance['style'] : 1;
			$pagination      = ! empty( $instance['pagination'] )      ? $instance['pagination'] : '';
			$tags            = ! empty( $instance['tags'] )            ? $instance['tags'] : '';
			$media_overlay   = ! empty( $instance['media_overlay'] )   ? 'true' : '';
			$exclude_current = ! empty( $instance['exclude_current'] ) ? 'true' : '';
			$trending_posts  = ! empty( $instance['trending_posts'] ) ? 'true' : '';
			$limit_published_days = ! empty( $instance['limit_published_days'] ) ? $instance['limit_published_days'] : '';
			$cats_id         = array();

			if( ! empty( $instance['cats_id'] ) ) {
				$cats_id = is_array( $instance['cats_id'] ) ? $instance['cats_id'] : explode ( ',', $instance['cats_id'] );
			}

			// Post Order : Default
			$post_order = array(
				'standard' => TIELABS_ADMIN_HELPER::get_orderby_list(),
			);

			// Related Posts options
			$post_order['related']['related-cat']    = esc_html__( 'Related Posts by Categories', TIELABS_TEXTDOMAIN );
			$post_order['related']['related-tag']    = esc_html__( 'Related Posts by Tags',       TIELABS_TEXTDOMAIN );
			$post_order['related']['related-author'] = esc_html__( 'Related Posts by Author',     TIELABS_TEXTDOMAIN );

			$post_order = apply_filters( 'TieLabs/Widget/Posts/post_order_args' ,$post_order );

			// Style the Custom options
			$style_visible   = 'style="display:block"';
			$related_options = $non_custom_options = $pagination_option = '';

			if( strpos( $posts_order, 'related' ) !== false ){
				$related_options   = $style_visible;
				$pagination_option = $style_visible;
			}
			else{
				$non_custom_options = $style_visible;
				$pagination_option  = $style_visible;
			}

			// Get the Categories List
			$categories = TIELABS_ADMIN_HELPER::get_categories();

			// Pagination Options
			$pagination_options =  array(
				''          => esc_html__( 'Disable', TIELABS_TEXTDOMAIN ),
				'show-more' => esc_html__( 'Show More', TIELABS_TEXTDOMAIN ),
				'load-more' => esc_html__( 'Load More', TIELABS_TEXTDOMAIN ),
				'next-prev' => esc_html__( 'Next/Previous Arrows', TIELABS_TEXTDOMAIN ),
			);

			?>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr(  $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat" type="text" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'posts_order' ) ); ?>"><?php esc_html_e( 'Posts order:', TIELABS_TEXTDOMAIN) ?></label>
				<select id="<?php echo esc_attr( $this->get_field_id( 'posts_order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'posts_order' ) ); ?>" class="widefat tie-posts-order-option">
					<?php
						foreach( $post_order as $order_groups => $options ){ ?>
							<optgroup>
								<?php

									foreach( $options as $order => $text ){ ?>
										<option value="<?php echo esc_attr( $order ) ?>" <?php selected( $posts_order, $order ); ?>><?php echo esc_attr( $text ) ?></option>
										<?php
									}

								?>
							</optgroup>
							<?php
						}
					?>
				</select>
			</p>

			<div class="tie-related-posts-order-option" <?php echo $related_options ?>>
				<?php
					echo '<p class="tie-message-hint">'. esc_html__( 'This Widget appears in the single post page only.', TIELABS_TEXTDOMAIN) .'</p>';
				?>
			</div>

			<p class="tie-non-custom-posts-order-option" <?php echo $non_custom_options ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'asc_or_desc' ) ); ?>"><?php esc_html_e( 'Order', TIELABS_TEXTDOMAIN) ?></label>
				<select id="<?php echo esc_attr( $this->get_field_id( 'asc_or_desc' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'asc_or_desc' ) ); ?>" class="widefat">
					<option value="" <?php selected( $asc_or_desc, '' ); ?>><?php esc_html_e( 'Descending', TIELABS_TEXTDOMAIN ) ?></option>
					<option value="ASC" <?php selected( $asc_or_desc, 'ASC' ); ?>><?php esc_html_e( 'Ascending', TIELABS_TEXTDOMAIN ) ?></option>
				</select>
			</p>

			<p class="tie-non-custom-posts-order-option" <?php echo $non_custom_options ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'cats_id' ) ); ?>"><?php esc_html_e( 'Categories', TIELABS_TEXTDOMAIN) ?></label>
				<select multiple="multiple" id="<?php echo esc_attr( $this->get_field_id( 'cats_id' ) ); ?>[]" name="<?php echo esc_attr( $this->get_field_name( 'cats_id' ) ); ?>[]" class="widefat">
					<?php foreach ($categories as $key => $option){ ?>
					<option value="<?php echo esc_attr( $key ) ?>" <?php if ( in_array( $key, $cats_id ) ){ echo ' selected="selected"' ; } ?>><?php esc_html_e( $option ); ?></option>
					<?php } ?>
				</select>
			</p>

			<p class="tie-non-custom-posts-order-option" <?php echo $non_custom_options ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'tags' ) ); ?>"><?php esc_html_e( 'Tags', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'tags' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'tags' ) ); ?>" value="<?php echo esc_attr( $tags ) ?>" type="text" class="widefat" />
				<i><?php esc_html_e( 'Enter a tag name, or names separated by comma.', TIELABS_TEXTDOMAIN ) ?></i>
			</p>

			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'trending_posts' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'trending_posts' ) ); ?>" value="true" <?php checked( $trending_posts, 'true' ); ?> type="checkbox" />
				<label for="<?php echo esc_attr( $this->get_field_id( 'trending_posts' ) ); ?>"><?php esc_html_e( 'Trending Posts', TIELABS_TEXTDOMAIN) ?></label>
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'no_of_posts' ) ); ?>"><?php esc_html_e( 'Number of posts to show', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'no_of_posts' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'no_of_posts' ) ); ?>" value="<?php echo esc_attr( $no_of_posts ) ?>" type="number" step="1" min="1" size="3" class="tiny-text" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title_length' ) ); ?>"><?php esc_html_e( 'Posts Title Length', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title_length' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title_length' ) ); ?>" value="<?php echo esc_attr( $title_length ) ?>" type="number" step="1" min="1" size="3" />
			</p>

			<p class="tie-non-custom-posts-order-option" <?php echo $non_custom_options ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'offset' ) ); ?>"><?php esc_html_e( 'Offset - number of posts to pass over', TIELABS_TEXTDOMAIN ) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'offset' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'offset' ) ); ?>" value="<?php echo esc_attr( $offset ) ?>" type="number" step="1" min="1" size="3" class="tiny-text" />
			</p>
			
			<p class="tie-non-custom-posts-order-option" <?php echo $non_custom_options ?>>
				<label for="<?php echo esc_attr( $this->get_field_id( 'limit_published_days' ) ); ?>"><?php esc_html_e( 'Published in the last (days)', TIELABS_TEXTDOMAIN ) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'limit_published_days' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit_published_days' ) ); ?>" value="<?php echo esc_attr( $limit_published_days ) ?>" type="number" step="1" min="1" size="3" class="tiny-text" />
			</p>

			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'media_overlay' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'media_overlay' ) ); ?>" value="true" <?php checked( $media_overlay, 'true' ); ?> type="checkbox" />
				<label for="<?php echo esc_attr( $this->get_field_id( 'media_overlay' ) ); ?>"><?php esc_html_e( 'Media Icon', TIELABS_TEXTDOMAIN) ?></label>
			</p>

			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'exclude_current' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'exclude_current' ) ); ?>" value="true" <?php checked( $exclude_current, 'true' ); ?> type="checkbox" />
				<label for="<?php echo esc_attr( $this->get_field_id( 'exclude_current' ) ); ?>"><?php esc_html_e( 'Exclude Current Post in the single post page.', TIELABS_TEXTDOMAIN) ?></label>
			</p>

			<div class="tie-posts-list-pagination-widget" <?php echo $pagination_option ?>>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'pagination' ) ); ?>"><?php esc_html_e( 'Pagination:', TIELABS_TEXTDOMAIN) ?></label>
					<select id="<?php echo esc_attr( $this->get_field_id( 'pagination' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pagination' ) ); ?>" class="widefat">
						<?php
							foreach( $pagination_options as $pagination_key => $pagination_text ){ ?>
									<option value="<?php echo esc_attr( $pagination_key ) ?>" <?php selected( $pagination, $pagination_key ); ?>><?php echo esc_attr( $pagination_text ) ?></option>
								<?php
							}
						?>
					</select>
				</p>
			</div>

			<label><?php esc_html_e( 'Style', TIELABS_TEXTDOMAIN) ?></label>

			<div class="tie-styles-list-widget tie-posts-list-widget">
				<p>
					<?php
						for ( $i=1; $i < 12; $i++ ){ ?>
							<label class="tie-widget-options">
								<input name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>" type="radio" value="<?php echo esc_attr( $i ) ?>" <?php echo checked( $style, $i ) ?>> <img src="<?php echo TIELABS_TEMPLATE_URL .'/framework/admin/assets/images/widgets/posts-'.$i.'.png'; ?>" />
							</label>
							<?php
						}
					?>
				</p>
			</div>
		<?php
		}
	}



	/**
	 * Register the widget.
	 */
	add_action( 'widgets_init', 'tie_posts_list_register' );
	function tie_posts_list_register(){
		register_widget( 'TIE_POSTS_LIST' );
	}

}
