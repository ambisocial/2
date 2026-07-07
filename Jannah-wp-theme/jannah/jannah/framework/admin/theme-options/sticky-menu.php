<?php

	tie_build_theme_option(
		array(
			'title' => esc_html__( 'Sticky Menu Settings', TIELABS_TEXTDOMAIN ),
			'id'    => 'sticky-menu-settings-tab',
			'type'  => 'tab-title',
		));


tie_build_theme_option(
	array(
		'title' => esc_html__( 'Sticky Menu', TIELABS_TEXTDOMAIN ),
		'id'    => 'sticky-menu',
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'name'   => esc_html__( 'Enable', TIELABS_TEXTDOMAIN ),
		'id'     => 'stick_nav',
		'toggle' => '#sticky-menu-items',
		'type'   => 'checkbox',
	));

echo '<div id="sticky-menu-items">';

	tie_build_theme_option(
		array(
			'name'    => esc_html__( 'Sticky Menu behavior', TIELABS_TEXTDOMAIN ),
			'id'      => 'sticky_behavior',
			'type'    => 'radio',
			'options' => array(
				'default' => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
				'upwards' => esc_html__( 'When scrolling upwards', TIELABS_TEXTDOMAIN ),
			)));

	tie_build_theme_option(
		array(
			'title' => esc_html__( 'Sticky Menu Logo', TIELABS_TEXTDOMAIN ),
			'id'    => 'sticky-menu-logo',
			'type'  => 'header',
		));

	tie_build_theme_option(
		array(
			'name'   => esc_html__( 'Sticky Menu Logo', TIELABS_TEXTDOMAIN ),
			'id'     => 'sticky_logo_type',
			'toggle' => '#sticky-logo-options, #single_sticky_menu_logo-item',
			'type'   => 'checkbox',
		));

		echo '<div id="sticky-logo-options">';

			tie_build_theme_option(
				array(
					'name'   => esc_html__( 'Custom Sticky Menu Logo', TIELABS_TEXTDOMAIN ),
					'hint'   => esc_html__( 'Use this option to set a custom logo in the sticky menu or Disable it to use the main logo.', TIELABS_TEXTDOMAIN ),
					'id'     => 'custom_logo_sticky',
					'toggle' => '#sticky-logo-custom-options',
					'type'   => 'checkbox',
				));

			echo '<div id="sticky-logo-custom-options">';

				tie_build_theme_option(
					array(
						'name'  => esc_html__( 'Logo Image', TIELABS_TEXTDOMAIN ),
						'id'    => 'logo_sticky',
						'type'  => 'upload',
					));

				tie_build_theme_option(
					array(
						'name'  => esc_html__( 'Logo Image (Retina Version @2x)', TIELABS_TEXTDOMAIN ),
						'id'    => 'logo_retina_sticky',
						'type'  => 'upload',
						'hint'	=> esc_html__( 'Please choose an image file for the retina version of the logo. It should be 2x the size of main logo.', TIELABS_TEXTDOMAIN ),
					));

			echo'</div><!-- #sticky-logo-custom-options -->';
		echo'</div><!-- #sticky-logo-options -->';




	tie_build_theme_option(
		array(
			'title' => esc_html__( 'Custom Sticky Menu in the Single Post Pages', TIELABS_TEXTDOMAIN ),
			'id'    => 'single-sticky-menu-logo',
			'type'  => 'header',
		));

	tie_build_theme_option(
		array(
			'name'   => esc_html__( 'Enable custom sticky menu', TIELABS_TEXTDOMAIN ),
			'id'     => 'single_sticky_menu',
			'toggle' => '#single_sticky_wrapper',
			'type'   => 'checkbox',
		));

	echo '<div id="single_sticky_wrapper">';

		echo '<div id="single_sticky_menu_logo-wrapper">';
		tie_build_theme_option(
			array(
				'name'   => esc_html__( 'Logo', TIELABS_TEXTDOMAIN ),
				'id'     => 'single_sticky_menu_logo',
				'type'   => 'checkbox',
			));
		echo '</div><!-- single_sticky_menu_logo-wrapper -->';

		tie_build_theme_option(
			array(
				'name'   => esc_html__( 'Post Title', TIELABS_TEXTDOMAIN ),
				'id'     => 'single_sticky_menu_post_title',
				'type'   => 'checkbox',
			));

		tie_build_theme_option(
			array(
				'name'   => esc_html__( 'Next/Prev posts', TIELABS_TEXTDOMAIN ),
				'id'     => 'single_sticky_menu_next_prev',
				'type'   => 'checkbox',
			));

		tie_build_theme_option(
			array(
				'name'   => esc_html__( 'Share Buttons', TIELABS_TEXTDOMAIN ),
				'id'     => 'share_post_sticky_menu',
				'type'   => 'checkbox',
				'toggle' => '#single-sticky-menu-share-buttons',

			));
			
		echo '<div id="single-sticky-menu-share-buttons">';	
			tie_build_theme_option(
				array(
					'title'  => esc_html__( 'Share Buttons', TIELABS_TEXTDOMAIN ),
					'id'     => 'single-sticky-menu-share-buttons-head',
					'type'   => 'header',
				));
				
			tie_get_share_buttons_options( 'sticky_menu' );
		echo'</div><!-- #single-sticky-menu-share-buttons -->';


	echo'</div><!-- #single_sticky_wrapper -->';


	echo'</div><!-- #sticky-menu-items -->';
