<?php

if( ! class_exists( 'TIE_CATEGORIES_GRID_WIDGET' ) ) {

	/**
	 * Widget API: TIE_CATEGORIES_GRID_WIDGET class
	 */
	 class TIE_CATEGORIES_GRID_WIDGET extends WP_Widget {


		public function __construct(){
			$widget_ops = array( 'classname' => 'tie-widget-categories-grid'  );
			parent::__construct( 'tie-widget-categories-grid', apply_filters( 'TieLabs/theme_name', 'TieLabs' ) .' - '.esc_html__( 'Categories Grid', TIELABS_TEXTDOMAIN), $widget_ops );
		}

		/**
		 * Outputs the content for the widget instance.
		 */
		public function widget( $args, $instance ){

			/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
			$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

			echo ( $args['before_widget'] );

			if ( ! empty($instance['title']) ){
				echo ( $args['before_title'] . $instance['title'] . $args['after_title'] );
			}

			$style  = ! empty( $instance['style'] )  ? $instance['style']  : '';
			$layout = ! empty( $instance['layout'] ) ? $instance['layout'] : '';

			$show_bgs   = ! empty( $instance['show_bgs'] )   ? 'true' : false;
			$show_icon  = ! empty( $instance['show_icon'] )  ? 'true' : false;
			$show_count = ! empty( $instance['show_count'] ) ? 'true' : false;


			$categories_exclude = ! empty( $instance['categories_exclude'] ) ? explode( ',', $instance['categories_exclude'] ) : array();

			tie_category_brand_block( array(
				'style'   => $style,
				'layout'  => $layout,
				'count'   => $show_count,
				'icon'    => $show_icon,
				'bgs'     => $show_bgs,
				'exclude' => $categories_exclude
			));
		
			echo ( $args['after_widget'] );
		}

		/**
		 * Handles updating settings for widget instance.
		 */
		public function update( $new_instance, $old_instance ){
			$instance          = $old_instance;
			$instance['title'] = sanitize_text_field( $new_instance['title'] );

			$instance['style']      = ! empty( $new_instance['style'] )      ? $new_instance['style']  : false;
			$instance['layout']     = ! empty( $new_instance['layout'] )     ? $new_instance['layout'] : false;
			$instance['show_bgs']   = ! empty( $new_instance['show_bgs'] )   ? 'true' : false;
			$instance['show_icon']  = ! empty( $new_instance['show_icon'] )  ? 'true' : false;
			$instance['show_count'] = ! empty( $new_instance['show_count'] ) ? 'true' : false;

			$instance['categories_exclude'] = $new_instance['categories_exclude'];

			return $instance;
		}

		/**
		 * Outputs the settings form for the widget.
		 */
		public function form( $instance ){
			$defaults = array( 'title' => esc_html__('Categories', TIELABS_TEXTDOMAIN), 'style' => 'vertical'  );
			$instance = wp_parse_args( (array) $instance, $defaults );

			$title  = ! empty( $instance['title'] )  ? $instance['title']  : '';
			$style  = ! empty( $instance['style'] )  ? $instance['style']  : '';
			$layout = ! empty( $instance['layout'] ) ? $instance['layout'] : '';

			$show_bgs   = ! empty( $instance['show_bgs'] )   ? 'true' : false;
			$show_icon  = ! empty( $instance['show_icon'] )  ? 'true' : false;
			$show_count = ! empty( $instance['show_count'] ) ? 'true' : false;

			$categories_exclude = ! empty( $instance['categories_exclude'] ) ? $instance['categories_exclude'] : '';
			
			// Layout Options
			$layouts_list =  array(
				''           => esc_html__( 'Horizontal', TIELABS_TEXTDOMAIN ),
				'vertical-1' => esc_html__( 'Vertical',   TIELABS_TEXTDOMAIN ),

				//'vertical'   => esc_html__( 'Vertical - Auto',     TIELABS_TEXTDOMAIN ),
				//'vertical-1' => esc_html__( 'Vertical - 1 Column', TIELABS_TEXTDOMAIN ),
				//'vertical-2' => esc_html__( 'Vertical - 2 Column', TIELABS_TEXTDOMAIN ),
			);

			
			?>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat" type="text" />
			</p>

			<label><?php esc_html_e( 'Style', TIELABS_TEXTDOMAIN) ?></label>

			<div class="tie-styles-list-widget tie-posts-list-widget">
				<p>
					<label class="tie-widget-options">
						<input name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>" type="radio" value="vertical" <?php echo checked( $style, 'vertical' ) ?>> <img src="<?php echo TIELABS_TEMPLATE_URL .'/framework/admin/assets/images/blocks/categories-vertical.png'; ?>" />
					</label>
					<label class="tie-widget-options">
						<input name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>" type="radio" value="horizontal" <?php echo checked( $style, 'horizontal' ) ?>> <img src="<?php echo TIELABS_TEMPLATE_URL .'/framework/admin/assets/images/blocks/categories-horizontal.png'; ?>" />
					</label>
				</p>
			</div>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>"><?php esc_html_e( 'Layout:', TIELABS_TEXTDOMAIN) ?></label>
				<select id="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>" class="widefat">
					<?php
						foreach( $layouts_list as $layout_id => $layout_text ){ ?>
								<option value="<?php echo esc_attr( $layout_id ) ?>" <?php selected( $layout, $layout_id ); ?>><?php echo esc_attr( $layout_text ) ?></option>
							<?php
						}
					?>
				</select>
			</p>

			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>" value="true" <?php checked( $show_count, 'true' ); ?> type="checkbox" />
				<label for="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"><?php esc_html_e( 'Show Number of Posts', TIELABS_TEXTDOMAIN) ?></label>
			</p>

			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'show_icon' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_icon' ) ); ?>" value="true" <?php checked( $show_icon, 'true' ); ?> type="checkbox" />
				<label for="<?php echo esc_attr( $this->get_field_id( 'show_icon' ) ); ?>"><?php esc_html_e( 'Show Icons', TIELABS_TEXTDOMAIN) ?></label>
			</p>

			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'show_bgs' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_bgs' ) ); ?>" value="true" <?php checked( $show_bgs, 'true' ); ?> type="checkbox" />
				<label for="<?php echo esc_attr( $this->get_field_id( 'show_bgs' ) ); ?>"><?php esc_html_e( 'Show Backgrounds', TIELABS_TEXTDOMAIN) ?></label>
			</p>


			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'categories_exclude' ) ); ?>"><?php esc_html_e( 'Exclude Categories', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'categories_exclude' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'categories_exclude' ) ); ?>" value="<?php echo esc_attr( $categories_exclude ); ?>" class="widefat" type="text" />
				<br />
				<?php esc_html_e( 'Enter a category ID, or IDs separated by comma.', TIELABS_TEXTDOMAIN ); ?>
			</p>


		<?php
		}
	}



	/**
	 * Register the widget.
	 */
	add_action( 'widgets_init', 'tie_categories_grid_widget_register' );
	function tie_categories_grid_widget_register(){
		register_widget( 'TIE_CATEGORIES_GRID_WIDGET' );
	}

}
