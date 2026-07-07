<?php

tie_build_theme_option(
	array(
		'title' => esc_html__( 'BuddyPress', TIELABS_TEXTDOMAIN ),
		'id'    => 'buddypress-tab',
		'type'  => 'tab-title',
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Use the BuddyPress Member Profile link', TIELABS_TEXTDOMAIN ),
		'id'   => 'bp_use_member_profile',
		'type' => 'checkbox',
		'hint' => esc_html__( 'Use the BuddyPress Member Profile link instead of the default author page link in the post meta, author box and the login sections.', TIELABS_TEXTDOMAIN ),
	));



tie_build_theme_option(
	array(
		'title' => esc_html__( 'Members', TIELABS_TEXTDOMAIN ) .' - '.  esc_html__( 'Sidebar Position', TIELABS_TEXTDOMAIN ),
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'id'      => 'bp_members_sidebar_pos',
		'type'    => 'visual',
		'options' => array(
			''      => array( esc_html__( 'Default', TIELABS_TEXTDOMAIN )         => 'default.png' ),
			'right'	=> array( esc_html__( 'Sidebar Right', TIELABS_TEXTDOMAIN )   => 'sidebars/sidebar-right.png' ),
			'left'	=> array( esc_html__( 'Sidebar Left', TIELABS_TEXTDOMAIN )    => 'sidebars/sidebar-left.png' ),
			'full'	=> array( esc_html__( 'Without Sidebar', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-full-width.png' ),
		)));

tie_build_theme_option(
	array(
		'name'    => esc_html__( 'Custom Sidebar', TIELABS_TEXTDOMAIN ),
		'id'      => 'bp_members_custom_sidebar',
		'type'    => 'select',
		'options' => TIELABS_ADMIN_HELPER::get_sidebars(),
	));


tie_build_theme_option(
	array(
		'title' => esc_html__( 'Groups', TIELABS_TEXTDOMAIN ) .' - '.  esc_html__( 'Sidebar Position', TIELABS_TEXTDOMAIN ),
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'id'      => 'bp_groups_sidebar_pos',
		'type'    => 'visual',
		'options' => array(
			''      => array( esc_html__( 'Default', TIELABS_TEXTDOMAIN )         => 'default.png' ),
			'right'	=> array( esc_html__( 'Sidebar Right', TIELABS_TEXTDOMAIN )   => 'sidebars/sidebar-right.png' ),
			'left'	=> array( esc_html__( 'Sidebar Left', TIELABS_TEXTDOMAIN )    => 'sidebars/sidebar-left.png' ),
			'full'	=> array( esc_html__( 'Without Sidebar', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-full-width.png' ),
		)));

tie_build_theme_option(
	array(
		'name'    => esc_html__( 'Custom Sidebar', TIELABS_TEXTDOMAIN ),
		'id'      => 'bp_groups_custom_sidebar',
		'type'    => 'select',
		'options' => TIELABS_ADMIN_HELPER::get_sidebars(),
	));


tie_build_theme_option(
	array(
		'title' => esc_html__( 'Activity', TIELABS_TEXTDOMAIN ) .' - '.  esc_html__( 'Sidebar Position', TIELABS_TEXTDOMAIN ),
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'id'      => 'bp_activity_sidebar_pos',
		'type'    => 'visual',
		'options' => array(
			''      => array( esc_html__( 'Default', TIELABS_TEXTDOMAIN )         => 'default.png' ),
			'right'	=> array( esc_html__( 'Sidebar Right', TIELABS_TEXTDOMAIN )   => 'sidebars/sidebar-right.png' ),
			'left'	=> array( esc_html__( 'Sidebar Left', TIELABS_TEXTDOMAIN )    => 'sidebars/sidebar-left.png' ),
			'full'	=> array( esc_html__( 'Without Sidebar', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-full-width.png' ),
		)));

tie_build_theme_option(
	array(
		'name'    => esc_html__( 'Custom Sidebar', TIELABS_TEXTDOMAIN ),
		'id'      => 'bp_activity_custom_sidebar',
		'type'    => 'select',
		'options' => TIELABS_ADMIN_HELPER::get_sidebars(),
	));


tie_build_theme_option(
	array(
		'title' => esc_html__( 'Registration', TIELABS_TEXTDOMAIN ) .' - '.  esc_html__( 'Sidebar Position', TIELABS_TEXTDOMAIN ),
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'id'      => 'bp_register_sidebar_pos',
		'type'    => 'visual',
		'options' => array(
			''      => array( esc_html__( 'Default', TIELABS_TEXTDOMAIN )         => 'default.png' ),
			'right'	=> array( esc_html__( 'Sidebar Right', TIELABS_TEXTDOMAIN )   => 'sidebars/sidebar-right.png' ),
			'left'	=> array( esc_html__( 'Sidebar Left', TIELABS_TEXTDOMAIN )    => 'sidebars/sidebar-left.png' ),
			'full'	=> array( esc_html__( 'Without Sidebar', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-full-width.png' ),
		)));

tie_build_theme_option(
	array(
		'name'    => esc_html__( 'Custom Sidebar', TIELABS_TEXTDOMAIN ),
		'id'      => 'bp_register_custom_sidebar',
		'type'    => 'select',
		'options' => TIELABS_ADMIN_HELPER::get_sidebars(),
	));
