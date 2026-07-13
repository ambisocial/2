<?php

if( ! class_exists( 'TIE_PATREON_WIDGET' ) ) {

	/**
	 * Widget API: TIE_PATREON_WIDGET class
	 */
	 class TIE_PATREON_WIDGET extends WP_Widget {


		public function __construct(){
			parent::__construct( 'tie-patreon-widget', apply_filters( 'TieLabs/theme_name', 'TieLabs' ) .' - '.esc_html__( 'Patreon', TIELABS_TEXTDOMAIN ) );
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

			$button_text = ! empty( $instance['button_text'] ) ? $instance['button_text'] : esc_html__( 'Support us on Patreon', TIELABS_TEXTDOMAIN );
			$username    = ! empty( $instance['username'] )    ? $instance['username']    : '';

			?>

			<div class="tie-patreon-badge-wrap">

				<a href="https://www.patreon.com/<?php echo $username ?>" rel="external noopener nofollow" target="_blank">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 245.53 268.91"><title><?php echo esc_html( $button_text ) ?></title><path d="M506.76,330.33c0-34.34-26.79-62.48-58.16-72.63-39-12.61-90.35-10.78-127.55,6.77-45.09,21.28-59.26,67.89-59.78,114.37-.43,38.22,3.38,138.88,60.16,139.6,42.19.54,48.47-53.83,68-80,13.89-18.63,31.77-23.89,53.78-29.34C481,399.74,506.82,369.88,506.76,330.33Z" transform="translate(-261.24 -249.55)"/></svg>
				</a>


				<?php 
					if( ! empty( $instance['secondary_text'] ) ){
						echo '<h4>'. $instance['secondary_text'] .'</h4>';
					} 
				?>

				<a href="https://www.patreon.com/<?php echo $username ?>" rel="external noopener nofollow" target="_blank" class="button">
					<span><?php echo esc_html( $button_text ) ?></span>
				</a>
			</div>

			<?php
			echo ( $args['after_widget'] );
		}

		/**
		 * Handles updating settings for widget instance.
		 */
		public function update( $new_instance, $old_instance ){
			$instance                   = $old_instance;
			$instance['title']          = sanitize_text_field( $new_instance['title'] );
			$instance['button_text']    = sanitize_text_field( $new_instance['button_text'] );
			$instance['secondary_text'] = sanitize_text_field( $new_instance['secondary_text'] );
			$instance['username']       = sanitize_text_field( $new_instance['username'] );

			return $instance;
		}

		/**
		 * Outputs the settings form for the widget.
		 */
		public function form( $instance ){
			$defaults = array( 'title' => esc_html__( 'Support us on Patreon', TIELABS_TEXTDOMAIN ) );
			$instance = wp_parse_args( (array) $instance, $defaults );

			$title          = isset( $instance['title'] )          ? $instance['title']          : '';
			$button_text    = isset( $instance['button_text'] )    ? $instance['button_text']    : '';
			$secondary_text = isset( $instance['secondary_text'] ) ? $instance['secondary_text'] : '';
			$username       = isset( $instance['username'] )       ? $instance['username']       : '';

			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat" type="text" />
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'username' ) ); ?>"><?php esc_html_e( 'Username', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'username' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'username' ) ); ?>" value="<?php echo esc_attr( $username ); ?>" class="widefat" type="text" />
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>"><?php esc_html_e( 'Button Text', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'button_text' ) ); ?>" value="<?php echo esc_attr( $button_text ); ?>" class="widefat" type="text" />
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'secondary_text' ) ); ?>"><?php esc_html_e( 'Secondary Text', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'secondary_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'secondary_text' ) ); ?>" value="<?php echo esc_attr( $secondary_text ); ?>" class="widefat" type="text" />
			</p>
			
		<?php
		}
	}



	/**
	 * Register the widget.
	 */
	add_action( 'widgets_init', 'tie_patreon_widget_register' );
	function tie_patreon_widget_register(){
		register_widget( 'TIE_PATREON_WIDGET' );
	}

}