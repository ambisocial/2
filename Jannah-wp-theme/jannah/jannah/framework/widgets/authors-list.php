<?php

if( ! class_exists( 'TIE_AUTHORS_LIST_WIDGET' ) ) {

	/**
	 * Widget API: TIE_AUTHORS_LIST_WIDGET class
	 */
	 class TIE_AUTHORS_LIST_WIDGET extends WP_Widget {


		public function __construct(){
			$widget_ops 	= array( 'classname' => 'authors-list-widget' );
			$control_ops 	= array( 'id_base'   => 'authors-list-widget' );
			parent::__construct( 'authors-list-widget', apply_filters( 'TieLabs/theme_name', 'TieLabs' ) .' - '.esc_html__( 'Authors List', TIELABS_TEXTDOMAIN ), $widget_ops, $control_ops );
		}

		/**
		 * Outputs the content for the widget instance.
		 */
		public function widget( $args, $instance ){

			/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
			$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

			echo ( $args['before_widget'] );

			if ( ! empty( $instance['title'] ) ){
				echo ( $args['before_title'] . $instance['title'] . $args['after_title'] );
			}



			$users_args = array();

			if( ! empty( $instance['roles'] ) && is_array( $instance['roles'] ) ){
				$users_args['role__in'] = $instance['roles'];
			}
			
			if( ! empty( $instance['authors_exclude'] ) ){
				$users_args['exclude'] = explode( ',', $instance['authors_exclude'] );
			}
	
			$get_users = get_users( $users_args );
	
			if ( ! empty( $get_users ) ){
	
				echo'<ul class="authors-wrap">';
					foreach ( $get_users as $user ){
						echo '<li>';
							tie_author_box( $user );
						echo '</li>';
					}
				echo'</ul>';
			}

			echo ( $args['after_widget'] );
		}

		/**
		 * Handles updating settings for widget instance.
		 */
		public function update( $new_instance, $old_instance ){
			$instance = $old_instance;
			$instance['title'] = sanitize_text_field( $new_instance['title'] );
			$instance['authors_exclude'] = $new_instance['authors_exclude'];

			if( ! empty( $new_instance['roles'] ) && is_array( $new_instance['roles'] ) ){
				$instance['roles'] = $new_instance['roles'];
			}
			else{
				$instance['roles'] = false;
			}

			return $instance;
		}

		/**
		 * Outputs the settings form for the widget.
		 */
		public function form( $instance ){

			$defaults = array( 'title' => esc_html__( 'Authors', TIELABS_TEXTDOMAIN) );
			$instance = wp_parse_args( (array) $instance, $defaults );

			$title = isset( $instance['title'] ) ? $instance['title'] : '';
			$authors_exclude = isset( $instance['authors_exclude'] ) ? $instance['authors_exclude'] : '';

			$roles = array();
			if( ! empty( $instance['roles'] ) ) {
				$roles = is_array( $instance['roles'] ) ? $instance['roles'] : explode ( ',', $instance['roles'] );
			}

			$get_roles  = wp_roles();
			$user_roles = $get_roles->get_names();

			?>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat" type="text" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'roles' ) ); ?>"><?php esc_html_e( 'User Roles', TIELABS_TEXTDOMAIN) ?></label>
				<select multiple="multiple" id="<?php echo esc_attr( $this->get_field_id( 'roles' ) ); ?>[]" name="<?php echo esc_attr( $this->get_field_name( 'roles' ) ); ?>[]" class="widefat">
					<?php foreach ( $user_roles as $key => $option ){ ?>
					<option value="<?php echo esc_attr( $key ) ?>" <?php if ( in_array( $key, $roles ) ){ echo ' selected="selected"' ; } ?>><?php esc_html_e( $option ); ?></option>
					<?php } ?>
				</select>
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'authors_exclude' ) ); ?>"><?php esc_html_e( 'Exclude Authors', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'authors_exclude' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'authors_exclude' ) ); ?>" value="<?php echo esc_attr( $authors_exclude ); ?>" class="widefat" type="text" />
				<br />
				<?php esc_html_e( 'Enter an author ID, or IDs separated by comma.', TIELABS_TEXTDOMAIN ); ?>
			</p>

		<?php
		}
	}



	/**
	 * Register the widget.
	 */
	add_action( 'widgets_init', 'tie_authors_list_widget_register' );
	function tie_authors_list_widget_register(){
		register_widget( 'TIE_AUTHORS_LIST_WIDGET' );
	}

}
