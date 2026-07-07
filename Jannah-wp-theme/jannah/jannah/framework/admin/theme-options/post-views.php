<?php

	tie_build_theme_option(
		array(
			'title' => esc_html__( 'Post Views Settings', TIELABS_TEXTDOMAIN ),
			'id'    => 'post-views-tab',
			'type'  => 'tab-title',
		));

	tie_build_theme_option(
		array(
			'type'  => 'header',
			'id'    => 'post-views-settings',
			'title' => esc_html__( 'Post Views Source', TIELABS_TEXTDOMAIN ),
		));

	tie_build_theme_option(
		array(
			'name'    => esc_html__( 'Post Views Source', TIELABS_TEXTDOMAIN ),
			'id'      => 'tie_post_views',
			'type'    => 'select',
			'options' => array(
				''        => esc_html__( 'Disable', TIELABS_TEXTDOMAIN ),
				'theme'   => esc_html__( "Theme's module", TIELABS_TEXTDOMAIN ),
				'wp-statistics' => esc_html__( 'WP Statistics Plugin', TIELABS_TEXTDOMAIN ),
				'plugin'  => esc_html__( 'Third party post views plugin', TIELABS_TEXTDOMAIN ),
			),
			'toggle'  => array(
				'wp-statistics' => 'none',
				'theme'         => '#views_meta_field-item, #views_starter_number-item, #views_7_days-item',
				'plugin'        => '#views_meta_field-item',
			)
		));

	tie_build_theme_option(
		array(
			'name'    => esc_html__( 'Post meta field', TIELABS_TEXTDOMAIN ),
			'id'      => 'views_meta_field',
			'type'    => 'text',
			'class'   => 'tie_post_views',
			'default' => 'tie_views',
			'hint'    => esc_html__( 'Change this if you have used a post views plugin before.', TIELABS_TEXTDOMAIN ),
		));

	tie_build_theme_option(
		array(
			'name'    => esc_html__( 'Starter Post Views number', TIELABS_TEXTDOMAIN ),
			'id'      => 'views_starter_number',
			'type'    => 'number',
			'class'   => 'tie_post_views',
			'hint'    => esc_html__( 'This will applied on the new Posts only.', TIELABS_TEXTDOMAIN ),
		));

	tie_build_theme_option(
		array(
			'name'    => esc_html__( '7 Days post sorting', TIELABS_TEXTDOMAIN ),
			'id'      => 'views_7_days',
			'type'    => 'checkbox',
			'class'   => 'tie_post_views',
		));

	tie_build_theme_option(
		array(
			'type'  => 'header',
			'id'    => 'post-views-icon',
			'title' => esc_html__( 'Post Views icon', TIELABS_TEXTDOMAIN ),
		));

	tie_build_theme_option(
		array(
			'id'     => 'views_icon',
			'type'   => 'visual',
			'options' => array(
				''    => array( esc_html__( 'Fire Icon', TIELABS_TEXTDOMAIN ) => 'post-views-fire.png' ),
				'1' => array( esc_html__( 'Eye Icon', TIELABS_TEXTDOMAIN )  => 'post-views-eye.png' ),

		)));

	tie_build_theme_option(
		array(
			'type'  => 'header',
			'id'    => 'post-views-colored',
			'title' => esc_html__( 'Colored Post Views Numbers', TIELABS_TEXTDOMAIN ),
		));

	tie_build_theme_option(
		array(
			'name'   => esc_html__( 'Colored Post Views Numbers', TIELABS_TEXTDOMAIN ),
			'id'     => 'views_colored',
			'toggle' => '#views_warm_color-item, #views_hot_color-item, #views_veryhot_color-item',
			'type'   => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => sprintf( esc_html__( '%s Min. Views Number', TIELABS_TEXTDOMAIN ), '<span class="tie-number-views-badge" style="background: #f47512">'. esc_html__( 'WARM', TIELABS_TEXTDOMAIN ) .'</span>' ),
			'id'   => 'views_warm_color',
			'type' => 'number',
			'hint' => sprintf( esc_html__( 'Default is: %s', TIELABS_TEXTDOMAIN ), 500 ),
		));

	tie_build_theme_option(
		array(
			'name' => sprintf( esc_html__( '%s Min. Views Number', TIELABS_TEXTDOMAIN ), '<span class="tie-number-views-badge" style="background: #f3502a">'. esc_html__( 'HOT', TIELABS_TEXTDOMAIN ) .'</span>' ),
			'id'   => 'views_hot_color',
			'type' => 'number',
			'hint' => sprintf( esc_html__( 'Default is: %s', TIELABS_TEXTDOMAIN ), 2000 ),
		));

	tie_build_theme_option(
		array(
			'name' => sprintf( esc_html__( '%s Min. Views Number', TIELABS_TEXTDOMAIN ), '<span class="tie-number-views-badge" style="background: #f11e1e">'. esc_html__( 'VERY HOT', TIELABS_TEXTDOMAIN ) .'</span>' ),
			'id'   => 'views_veryhot_color',
			'type' => 'number',
			'hint' => sprintf( esc_html__( 'Default is: %s', TIELABS_TEXTDOMAIN ), 5000 ),
		));


