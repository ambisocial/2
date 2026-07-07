<?php

tie_build_theme_option(
	array(
		'title' => esc_html__( 'Notification Bar Settings', TIELABS_TEXTDOMAIN ),
		'id'    => 'notification-bar-settings-tab',
		'type'  => 'tab-title',
	));

tie_build_theme_option(
	array(
		'title'   => esc_html__( 'Notification Bar Settings', TIELABS_TEXTDOMAIN ),
		'id'    => 'notification-bar-head',
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'name'   => esc_html__( 'Enable', TIELABS_TEXTDOMAIN ),
		'id'     => 'notification_bar',
		'toggle' => '#notification_bar_content-item, #notification_bar_centered-item, #notification_bar_button_text-item, #notification_bar_button_url-item, #notification_bar_button_nofollow-item, #notification_bar_button_tab-item',
		'type'   => 'checkbox',
		'hint'   => esc_html__( 'Enable a top notification bar above the header.', TIELABS_TEXTDOMAIN ),
	));


tie_build_theme_option(
	array(
		'name' => esc_html__( 'Content', TIELABS_TEXTDOMAIN ),
		'id'   => 'notification_bar_content',
		'type' => 'editor',
	));

tie_build_theme_option(
	array(
		'name'  => esc_html__( '	Center the content?', TIELABS_TEXTDOMAIN ),
		'id'    => 'notification_bar_centered',
		'type'  => 'checkbox',
	));
	
tie_build_theme_option(
	array(
		'name'  => esc_html__( 'Button Text', TIELABS_TEXTDOMAIN ),
		'id'    => 'notification_bar_button_text',
		'type'  => 'text',
	));

tie_build_theme_option(
	array(
		'name'  => esc_html__( 'Button URL', TIELABS_TEXTDOMAIN ),
		'id'    => 'notification_bar_button_url',
		'type'  => 'text',
	));

tie_build_theme_option(
	array(
		'name'  => esc_html__( 'Nofollow?', TIELABS_TEXTDOMAIN ),
		'id'    => 'notification_bar_button_nofollow',
		'type'  => 'checkbox',
	));

tie_build_theme_option(
	array(
		'name'  => esc_html__( 'Open The Link In a new Tab', TIELABS_TEXTDOMAIN ),
		'id'    => 'notification_bar_button_tab',
		'type'  => 'checkbox',
	));
	
