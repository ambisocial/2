<?php

if( ! class_exists( 'TIE_CATEGORIES_WIDGET' ) ) {

	/**
	 * Widget API: TIE_CATEGORIES_WIDGET class
	 */
	 class TIE_CATEGORIES_WIDGET extends WP_Widget {


		public function __construct(){
			$widget_ops = array( 'classname' => 'widget_categories tie-widget-categories'  );
			parent::__construct( 'tie-widget-categories', apply_filters( 'TieLabs/theme_name', 'TieLabs' ) .' - '.esc_html__( 'Categories List', TIELABS_TEXTDOMAIN), $widget_ops );
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

			$depth = empty( $instance['depth'] ) ? 1 : 0;

			$categories_exclude = ! empty( $instance['categories_exclude'] ) ? explode( ',', $instance['categories_exclude'] ) : array();

			$categories = wp_list_categories( apply_filters( 'TieLabs/Widgets/Categories/args', array(
				'echo'       => false,
				'title_li'   => 0,
				'show_count' => 1,
				'depth'      => $depth,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'exclude'    => $categories_exclude,
			)));

			$categories = str_replace( 'cat-item-', 'cat-counter tie-cat-item-', $categories );
			$categories = preg_replace( '~\((.*?)\)~', '<span>$1</span>', $categories );

			echo "<ul>$categories</ul>";

			echo ( $args['after_widget'] );
		}

		/**
		 * Handles updating settings for widget instance.
		 */
		public function update( $new_instance, $old_instance ){
			$instance          = $old_instance;
			$instance['title'] = sanitize_text_field( $new_instance['title'] );
			$instance['depth'] = ! empty( $new_instance['depth'] ) ? 'true' : 0;
			$instance['categories_exclude'] = $new_instance['categories_exclude'];
			return $instance;
		}

		/**
		 * Outputs the settings form for the widget.
		 */
		public function form( $instance ){
			$defaults = array( 'title' => esc_html__('Categories', TIELABS_TEXTDOMAIN)  );
			$instance = wp_parse_args( (array) $instance, $defaults );
			$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
			$depth = ! empty( $instance['depth'] ) ? $instance['depth'] : '';
			$categories_exclude = ! empty( $instance['categories_exclude'] ) ? $instance['categories_exclude'] : ''; ?>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat" type="text" />
			</p>

			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( 'depth' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'depth' ) ); ?>" value="true" <?php checked( $depth, 'true' ); ?> type="checkbox" />
				<label for="<?php echo esc_attr( $this->get_field_id( 'depth' ) ); ?>"><?php esc_html_e( 'Show child categories?', TIELABS_TEXTDOMAIN) ?></label>
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
	add_action( 'widgets_init', 'tie_categories_widget_register' );
	function tie_categories_widget_register(){
		register_widget( 'TIE_CATEGORIES_WIDGET' );
	}

}
