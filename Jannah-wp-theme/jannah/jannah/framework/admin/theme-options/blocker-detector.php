<?php

tie_build_theme_option(
	array(
		'title' => esc_html__( 'Ad Blocker Detector Settings', TIELABS_TEXTDOMAIN ),
		'id'    => 'ad-blocker-detector-settings-tab',
		'type'  => 'tab-title',
	));


tie_build_theme_option(
	array(
		'title'   => esc_html__( 'Ad Blocker Detector Pop-up', TIELABS_TEXTDOMAIN ),
		'id'    => 'ad-blocker-detector',
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'name'   => esc_html__( 'Ad Blocker Detector Pop-up', TIELABS_TEXTDOMAIN ),
		'id'     => 'ad_blocker_detector',
		'toggle' => '#adblock_title-item, #adblock_message-item, #adblock_background-item, #ad_blocker_detector_delay-item, #ad_blocker_dismissable_group',
		'type'   => 'checkbox',
		'hint'   => esc_html__( 'Block the adblockers from browsing the site, till they turn off the Ad Blocker.', TIELABS_TEXTDOMAIN ),
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Title', TIELABS_TEXTDOMAIN ),
		'id'   => 'adblock_title',
		'type' => 'text',
		'placeholder' => esc_html__( 'Adblock Detected', TIELABS_TEXTDOMAIN ),
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Message', TIELABS_TEXTDOMAIN ),
		'id'   => 'adblock_message',
		'type' => 'editor',
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Number of seconds before displaying the message', TIELABS_TEXTDOMAIN ),
		'id'   => 'ad_blocker_detector_delay',
		'type' => 'number',
		'hint' => esc_html__( 'Leave this empty to display the message instantly.', TIELABS_TEXTDOMAIN ),
	));

echo '<div id="ad_blocker_dismissable_group">';
tie_build_theme_option(
	array(
		'name'   => esc_html__( 'Is dismissable?', TIELABS_TEXTDOMAIN ),
		'id'     => 'ad_blocker_dismissable',
		'type'   => 'checkbox',
		'hint'   => esc_html__( 'Allow visitors to dismiss the message.', TIELABS_TEXTDOMAIN ),
		'toggle' => '#ad_blocker_show_once-item',
	));
	
tie_build_theme_option(
	array(
		'name' => esc_html__( 'Don\'t show again after dismissing', TIELABS_TEXTDOMAIN ),
		'id'   => 'ad_blocker_show_once',
		'type' => 'checkbox',
	));
echo '</div>';

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Background Color', TIELABS_TEXTDOMAIN ),
		'id'   => 'adblock_background',
		'type' => 'color',
	));


tie_build_theme_option(
	array(
		'title'   => esc_html__( 'Disallow Images for adblockers', TIELABS_TEXTDOMAIN ),
		'id'    => 'ad-blocker-disallow-images',
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'name'   => esc_html__( 'Disallow Images for adblockers', TIELABS_TEXTDOMAIN ),
		'id'     => 'ad_blocker_disallow_images',
		'toggle' => '#ad_blocker_disallow_images_post-item, #ad_blocker_disallow_images_placeholder-item',
		'type'   => 'checkbox',
		'hint'   => esc_html__( 'Block the adblockers from viewing images, and display a placeholder image instead.', TIELABS_TEXTDOMAIN ),
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Disallow Images inside the post only?', TIELABS_TEXTDOMAIN ),
		'id'   => 'ad_blocker_disallow_images_post',
		'type' => 'checkbox',
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Custom placeholder image', TIELABS_TEXTDOMAIN ),
		'id'   => 'ad_blocker_disallow_images_placeholder',
		'type' => 'upload',
	));

