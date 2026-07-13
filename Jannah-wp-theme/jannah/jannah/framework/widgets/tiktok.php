<?php

if( ! class_exists( 'TIE_TIKTOK_WIDGET' ) ) {

	/**
	 * Widget API: TIE_TIKTOK_WIDGET class
	 */
	 class TIE_TIKTOK_WIDGET extends WP_Widget {


		public function __construct(){
			parent::__construct( 'tie-tiktok-theme', apply_filters( 'TieLabs/theme_name', 'TieLabs' ) .' - '.esc_html__( 'TikTok', TIELABS_TEXTDOMAIN ) );
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

			if( ! defined( 'QLTTF_PLUGIN_NAME' ) ){
				TIELABS_HELPER::notice_message( esc_html__( 'This section requries the TikTok Plugin.', TIELABS_TEXTDOMAIN ) );
			}
			
			else{

				$feeds = get_option( 'tiktok_feed_feeds' );

				if( empty( $feeds ) || ! is_array( $feeds ) ) {
					TIELABS_HELPER::notice_message( esc_html__( 'No accounts found, Go to TikTok Feed > Feeds to setup your account.', TIELABS_TEXTDOMAIN ) );
				}

				else{

					if( isset( $instance['source'] ) ){

						$source = str_replace( 'tiktok-', '', $instance['source'] );

						foreach ( $feeds as $data ) {

							if( ! empty( $data['id'] ) && $data['id'] == $source ){
									
								$account_info = get_transient( 'qlttf_cache_profile_'. $data['open_id'] .'_'. md5( 'profile_' . $data['open_id'] ) );
								if( ! empty( $account_info['response']['username'] ) ){
									$account_info = $account_info['response'];
									?>
										<div class="tie-tiktok-header">

											<div class="tie-tiktok-avatar">
												<a href="<?php echo esc_attr( $account_info['link'] ) ?>" target="_blank" rel="nofollow noopener">
													<img src="<?php echo $account_info['avatar'] ?>" alt="<?php echo esc_attr( $account_info['nickname'] ) ?>" width="120" height="120" loading="lazy" />
												</a>
											</div>

											<div class="tie-tiktok-info">
												<a href="<?php echo esc_attr( $account_info['link'] ) ?>" target="_blank" rel="nofollow noopener" class="tie-tiktok-username">
													<?php
														echo esc_attr( $account_info['username'] );
													?>
												</a>
												<span class="tie-tiktok-full-name">
													<?php echo esc_attr( $account_info['nickname'] ); ?>
												<span>
											</div>

											<?php if( ! empty( $account_info['biography'] ) ){ ?>
												<div class="tie-tiktok-desc">
													<?php echo $account_info['biography'] ?>
												</div>
											<?php } ?>

										</div>
									<?php
								}
							}						
						}

						echo do_shortcode( '[tiktok-feed id="'. $source .'"]' );

					}
				}
			}

			echo ( $args['after_widget'] );
		}


		/**
		 * Format the comments and links numbers
		 */
		private function format_number( $number ){

			if( ! is_numeric( $number ) ){
				$number = $number;
			}

			elseif( $number >= 1000000 ){
				$number = round( ($number/1000)/1000 , 1) . "M";
			}

			elseif( $number >= 100000 ){
				$number = round( $number/1000, 0) . "k";
			}

			else{
				$number = number_format( $number );
			}

			return apply_filters( 'TieLabs/number_format', $number );
		}


		/**
		 * Handles updating settings for widget instance.
		 */
		public function update( $new_instance, $old_instance ){
			$instance           = $old_instance;
			$instance['title']  = sanitize_text_field( $new_instance['title'] );
			$instance['source'] = $new_instance['source'];
			return $instance;
		}

		/**
		 * Outputs the settings form for the widget.
		 */
		public function form( $instance ){
			$defaults = array( 'title' => esc_html__( 'TikTok', TIELABS_TEXTDOMAIN ), );
			$instance = wp_parse_args( (array) $instance, $defaults );

			$title  = isset( $instance['title'] )  ? $instance['title']  : '';
			$source = isset( $instance['source'] ) ? $instance['source'] : '';

			$show_settings = 'none';

			if( ! defined( 'QLTTF_PLUGIN_NAME' ) ){
				tie_build_theme_option(
					array(
						'text' => sprintf( esc_html__( 'This section requries the TikTok Plugin, %1$sYou can install it from here.%2$s', TIELABS_TEXTDOMAIN ), '<a href="https://tielabs.com/go/tiktok-plugin" target="_blank">', '</a>' ),
						'type' => 'error',
					));
			}

			else{
				$show_settings = 'block';
			}

			?>
			<div style="display:<?php echo esc_attr( $show_settings ) ?>">
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', TIELABS_TEXTDOMAIN) ?></label>
					<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat" type="text" />
				</p>

				<?php

					$feeds = get_option( 'tiktok_feed_feeds' );

					if( empty( $feeds ) || ! is_array( $feeds ) ) {

						tie_build_theme_option(
							array(
								'text' => esc_html__( 'No accounts found, Go to TikTok Feed > Feeds to setup your account.', TIELABS_TEXTDOMAIN ),
								'type' => 'error',
							));
					}
					else{
					?>

					<p>
						<label for="<?php echo esc_attr( $this->get_field_id( 'source' ) ); ?>"><?php esc_html_e( 'Source', TIELABS_TEXTDOMAIN ) ?></label>
						<select id="<?php echo esc_attr( $this->get_field_id( 'source' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'source' ) ); ?>" class="widefat">
							<option value=""><?php esc_html_e( 'Choose', TIELABS_TEXTDOMAIN) ?></option>
							<?php

								foreach ( $feeds as $data ) {

									if( ! empty( $data['open_id'] ) ){

										$cached_data = get_transient( 'qlttf_cache_profile_'. $data['open_id'] .'_'. md5( 'profile_' . $data['open_id'] ) );

										if( ! empty( $cached_data['response']['username'] ) ){
											$label = $cached_data['response']['username'];

											$data = wp_parse_args( $data, array(
												'limit' => 12,
												'columns' => 3,
											));

											$label .= ' '.sprintf( esc_html__( '(Videos: %s)', TIELABS_TEXTDOMAIN ),  $data['limit'] );
											$label .= ' '.sprintf( esc_html__( '(Columns: %s)', TIELABS_TEXTDOMAIN ), $data['columns'] );
										
											?>
											<option value="<?php echo esc_attr( 'tiktok-'.$data['id'] ) ?>" <?php selected( $source, 'tiktok-'.$data['id'] ); ?>><?php esc_html_e( $label ) ?></option>
										<?php

										}
									}
								}
							?>
						</select>
					</p>
					<?php

					}
				?>
			</div>
		<?php

		}
	}



	/**
	 * Register the widget.
	 */
	add_action( 'widgets_init', 'tie_tiktok_widget_register' );
	function tie_tiktok_widget_register(){
		register_widget( 'TIE_TIKTOK_WIDGET' );
	}

}
