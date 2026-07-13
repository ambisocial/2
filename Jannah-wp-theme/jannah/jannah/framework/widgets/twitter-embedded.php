<?php

if( ! class_exists( 'TIE_TWITTER_EMBEDDED_WIDGET' ) ) {

	/**
	 * Widget API: TIE_TWITTER_EMBEDDED_WIDGET class
	 */
	 class TIE_TWITTER_EMBEDDED_WIDGET extends WP_Widget {

		private $token;

		public function __construct(){
			$widget_ops = array( 'classname' => 'latest-tweets-widget' );
			parent::__construct( 'twitter_embedded_timeline_widget', apply_filters( 'TieLabs/theme_name', 'TieLabs' ) .' - '.esc_html__( 'X (formerly Twitter) Embedded Timeline', TIELABS_TEXTDOMAIN ), $widget_ops );
		}

		/**
		 * Outputs the content for the widget instance.
		 */
		public function widget( $args, $instance ){

			extract( $args );

			/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
			$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

			echo ( $before_widget );

			if ( ! empty($instance['title']) ){
				echo
					$before_title.
						'<a href="https://twitter.com/' .$instance['username']. '" rel="nofollow noopener">' .$instance['title']. '</a>'.
					$after_title;
			}


			# Get the tweets
			if( ! empty( $instance['username'] ) ){

				$twitter_username = str_replace( '@', '', TIELABS_HELPER::remove_spaces( $instance['username'] ) );
				$no_of_tweets     = ! empty( $instance['no_of_tweets'] ) ? $instance['no_of_tweets'] : 5;

					$timeline_args = '
						class="twitter-timeline"
						data-width="auto"
						data-height="400"
						data-tweet-limit="'. $no_of_tweets .'"
						data-chrome="noheader, nofooter, noborders"
						data-dnt="true"
						style="display: none"
						href="https://twitter.com/'. esc_html( $twitter_username ) .'?ref_src=twsrc%5Etfw"
					';
				?>

				<div class="twitter-embedded-timeline twitter-embedded-timeline-light">
					<a <?php echo $timeline_args ?> data-theme="light"><?php echo esc_html( $twitter_username ) ?></a>
				</div>

				<div class="twitter-embedded-timeline twitter-embedded-timeline-dark">
					<a <?php echo $timeline_args ?> data-theme="dark"><?php echo esc_html( $twitter_username ) ?></a>
				</div>

				<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
				<?php
			}
			else{
				TIELABS_HELPER::notice_message( esc_html__( 'Error Can not Get Tweets, Incorrect account info.', TIELABS_TEXTDOMAIN ) );
			}

			echo ( $after_widget );
		}

		/**
		 * Handles updating settings for widget instance.
		 */
		public function update( $new_instance, $old_instance ){
			$instance                 = $old_instance;
			$instance['title']        = sanitize_text_field( $new_instance['title'] );
			$instance['no_of_tweets'] = absint( $new_instance['no_of_tweets'] );
			$instance['username']     = $new_instance['username'];

			return $instance;
		}

		/**
		 * Outputs the settings form for the widget.
		 */
		public function form( $instance ){
			$defaults = array( 'title' => esc_html__( 'Follow Us', TIELABS_TEXTDOMAIN ) , 'no_of_tweets' => '5' );
			$instance = wp_parse_args( (array) $instance, $defaults );

			$title        = isset( $instance['title'] )        ? $instance['title'] : '';
			$username     = isset( $instance['username'] )     ? $instance['username'] : '';
			$no_of_tweets = isset( $instance['no_of_tweets'] ) ? $instance['no_of_tweets'] : 5;
			?>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ) ?>" class="widefat" type="text" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'username' ) ); ?>"><?php esc_html_e( 'X Username', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'username' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'username' ) ); ?>" value="<?php echo esc_attr( $username ) ?>" class="widefat" type="text" />
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'no_of_tweets' ) ); ?>"><?php esc_html_e( 'Number of Posts to show:', TIELABS_TEXTDOMAIN) ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'no_of_tweets' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'no_of_tweets' ) ); ?>" value="<?php echo esc_attr( $no_of_tweets ) ?>" type="number" step="1" min="1" size="3" class="tiny-text" />
			</p>
			<?php
		}
	}


	/**
	 * Register the widget.
	 */
	add_action( 'widgets_init', 'tie_twitter_embedded_widget_register' );
	function tie_twitter_embedded_widget_register(){
		register_widget( 'TIE_TWITTER_EMBEDDED_WIDGET' );
	}

}
