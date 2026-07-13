<?php

tie_build_theme_option(
	array(
		'title' => esc_html__( 'Advertisement Settings', TIELABS_TEXTDOMAIN ),
		'id'    => 'advertisements-settings-tab',
		'type'  => 'tab-title',
	));


if( ! defined( 'JANNAH_AUTOLOAD_POSTS_VERSION' ) ){
	echo '
		<style>
			#autoload_post_before-ad,
			#autoload_post_before-item{
				display: none;
			}
		</style>
	';
}

tie_build_theme_option(
	array(
		'text' => esc_html__( 'It is recommended to avoid using words like ad, ads, adv, advert, advertisement, banner, banners, sponsor, 300x250, 728x90, etc. in the image names or image path to avoid AdBlocks from blocking your Ad.', TIELABS_TEXTDOMAIN ),
		'type' => 'message',
	));

	
tie_build_theme_option(
	array(
		'title' =>	esc_html__( 'Background Image Ad', TIELABS_TEXTDOMAIN ),
		'id'    => 'background-image-ad',
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'name'   => esc_html__( 'Full Page Takeover', TIELABS_TEXTDOMAIN ),
		'id'     => 'banner_bg',
		'toggle' => '#banner_bg_url-item, #banner_bg_img-item, #banner_bg_site_margin-item',
		'type'   => 'checkbox',
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Link', TIELABS_TEXTDOMAIN ),
		'id'   => 'banner_bg_url',
		'type' => 'text',
	));

tie_build_theme_option(
	array(
		'name'  => esc_html__( 'Background Image', TIELABS_TEXTDOMAIN ),
		'id'    => 'banner_bg_img',
		'type'  => 'background',
	));

tie_build_theme_option(
	array(
		'name'  => esc_html__( 'Site margin top', TIELABS_TEXTDOMAIN ),
		'id'    => 'banner_bg_site_margin',
		'type'  => 'number',
	));


tie_build_theme_option(
	array(
		'title' =>	esc_html__( 'Float Left Right Ads', TIELABS_TEXTDOMAIN ),
		'id'    => 'side-e3lan-head',
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'name'   => esc_html__( 'Float Left Right Ads', TIELABS_TEXTDOMAIN ),
		'id'     => 'side_e3lan',
		'toggle' => '#side_e3lan_screen_width-item, #side_e3lan_margin_top-item, #side_e3lan_margin_top_scroll-item, #side-e3lan-content-left, #side-e3lan-content-right',
		'type'   => 'checkbox',
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Show ads if client screen width >= (px)', TIELABS_TEXTDOMAIN ),
		'id'   => 'side_e3lan_screen_width',
		'type' => 'number',
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Margin Top', TIELABS_TEXTDOMAIN ),
		'id'   => 'side_e3lan_margin_top',
		'type' => 'number',
	));

tie_build_theme_option(
	array(
		'name' => esc_html__( 'Margin Top - After scroll', TIELABS_TEXTDOMAIN ),
		'id'   => 'side_e3lan_margin_top_scroll',
		'type' => 'number',
	));


	$float_side_ads = array(
		'left'  => esc_html__( 'Banner Left',  TIELABS_TEXTDOMAIN ),
		'right' => esc_html__( 'Banner Right', TIELABS_TEXTDOMAIN ),
	);

	foreach( $float_side_ads as $ad_id => $name ){

	?>

	<div id="side-e3lan-content-<?php echo esc_attr( $ad_id ); ?>" class="option-item">
		<span class="tie-label" style="text-transform:capitalize"><?php esc_html_e( $name ); ?></span>

		<div class="option-contents" style="width: calc( 100% - 300px );">	

			<div id="side-e3lan-<?php echo esc_attr( $ad_id ); ?>-item" class="side-e3lan-options">	

				<div class="option-item" style="padding-top: 8px;">
					<label>
						<span class="tie-label" style="width: 120px;"><?php esc_html_e( 'Width (px)', TIELABS_TEXTDOMAIN ) ?></span>
						<input name="tie_options[side_e3lan_<?php esc_html_e( $ad_id ) ?>_width]" type="number" value="<?php echo esc_attr( tie_get_option( 'side_e3lan_'. $ad_id .'_width' ) ); ?>">
					</label>
					<div class="clear"></div>
				</div>


				<div class="option-item">
					<label>
						<span class="tie-label" style="width: 120px;"><?php esc_html_e( 'Height (px)', TIELABS_TEXTDOMAIN ) ?></span>
						<input name="tie_options[side_e3lan_<?php esc_html_e( $ad_id ) ?>_height]" type="number" value="<?php echo esc_attr( tie_get_option( 'side_e3lan_'. $ad_id .'_height' ) ); ?>">
					</label>
					<div class="clear"></div>
				</div>

					<div class="option-item">
						<label>
							<span class="tie-label" style="width: 120px;"><?php esc_html_e( 'Custom Ad Code', TIELABS_TEXTDOMAIN ) ?></span>
							<textarea id="side_e3lan_<?php esc_html_e( $ad_id ) ?>_code" name="tie_options[side_e3lan_<?php esc_html_e( $ad_id ) ?>_code]" rows="3"><?php echo esc_textarea( tie_get_option( 'side_e3lan_'. $ad_id .'_code', '' ) ) ?></textarea>
						</label>
						<div class="clear"></div>
					</div>
				</div><!-- .side-e3lan-content- -->

			</div><!-- option-contents -->
			<div class="clear"></div>

		</div>
<?php
}


$theme_ads = array(
	'banner_header'           => esc_html__( 'Above Header Ad', TIELABS_TEXTDOMAIN ),
	'banner_top'              => esc_html__( 'Header Ad', TIELABS_TEXTDOMAIN ),
	'banner_bottom'           => esc_html__( 'Above Footer Ad', TIELABS_TEXTDOMAIN ),
	'banner_below_header'     => esc_html__( 'Below the Header Ad', TIELABS_TEXTDOMAIN ),
	'banner_above'            => esc_html__( 'Above Article Ad', TIELABS_TEXTDOMAIN ),
	'banner_after_post_title' => esc_html__( 'Below Article Title Ad', TIELABS_TEXTDOMAIN ),
	'banner_above_content'    => esc_html__( 'Above Article Content Ad', TIELABS_TEXTDOMAIN ),
	'banner_below_content'    => esc_html__( 'Below Article Content Ad', TIELABS_TEXTDOMAIN ),
	'banner_below'            => esc_html__( 'Below Article Ad', TIELABS_TEXTDOMAIN ),
	'banner_comments'         => esc_html__( 'Below Comments Ad', TIELABS_TEXTDOMAIN ),

	'banner_category_below_slider' => esc_html__( 'Category Pages: Below the slider', TIELABS_TEXTDOMAIN ),
	'banner_category_above_title'  => esc_html__( 'Category Pages: Above the title', TIELABS_TEXTDOMAIN ),
	'banner_category_below_title'  => esc_html__( 'Category Pages: Below the title', TIELABS_TEXTDOMAIN ),

	'banner_category_below_posts'      => esc_html__( 'Category Pages: Below Posts', TIELABS_TEXTDOMAIN ),
	'banner_category_below_pagination' => esc_html__( 'Category Pages: Below Pagination', TIELABS_TEXTDOMAIN ),

	'between_posts_1' => sprintf( esc_html__( 'Between Posts in Archives #%s', TIELABS_TEXTDOMAIN ), 1 ),
	'between_posts_2' => sprintf( esc_html__( 'Between Posts in Archives #%s', TIELABS_TEXTDOMAIN ), 2 ),
	'between_posts_3' => sprintf( esc_html__( 'Between Posts in Archives #%s', TIELABS_TEXTDOMAIN ), 3 ),
	'between_posts_4' => sprintf( esc_html__( 'Between Posts in Archives #%s', TIELABS_TEXTDOMAIN ), 4 ),
	'between_posts_5' => sprintf( esc_html__( 'Between Posts in Archives #%s', TIELABS_TEXTDOMAIN ), 5 ),

	'article_inline_ad_1' => sprintf( esc_html__( 'Article inline ad #%s', TIELABS_TEXTDOMAIN ), 1 ),
	'article_inline_ad_2' => sprintf( esc_html__( 'Article inline ad #%s', TIELABS_TEXTDOMAIN ), 2 ),
	'article_inline_ad_3' => sprintf( esc_html__( 'Article inline ad #%s', TIELABS_TEXTDOMAIN ), 3 ),
	'article_inline_ad_4' => sprintf( esc_html__( 'Article inline ad #%s', TIELABS_TEXTDOMAIN ), 4 ),
	'article_inline_ad_5' => sprintf( esc_html__( 'Article inline ad #%s', TIELABS_TEXTDOMAIN ), 5 ),
	'article_inline_ad_6' => sprintf( esc_html__( 'Article inline ad #%s', TIELABS_TEXTDOMAIN ), 6 ),
	'article_inline_ad_7' => sprintf( esc_html__( 'Article inline ad #%s', TIELABS_TEXTDOMAIN ), 7 ),
	
	'autoload_post_before' => esc_html__( 'Ad before the Auto Load Post', TIELABS_TEXTDOMAIN ),
);

foreach( $theme_ads as $ad => $name ){

	tie_build_theme_option(
		array(
			'title' => $name,
			'type'  => 'header',
			'id'    => $ad . '-ad',
		));

	tie_build_theme_option(
		array(
			'name'   => $name,
			'id'     => $ad,
			'type'   => 'checkbox',
			'toggle' => '#'.$ad.'_title-item, #'.$ad.'_title_link-item, #'.$ad.'_img-item, #'.$ad.'_img_width-item, #'.$ad.'_img_height-item, #'.$ad.'_posts_number-item, #'.$ad.'_paragraphs_number-item, #'.$ad.'_align-item, #'.$ad.'_url-item, #'.$ad.'_alt-item, #'.$ad.'_tab-item, #'.$ad.'_nofollow-item, #' .$ad. '_adsense-item, #'.$ad.'-adrotate-options',
		));


	// Custom Ads Options
	if( strpos( $ad, 'between_posts' ) !== false ){

		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Number of posts before the Ad', TIELABS_TEXTDOMAIN ),
				'id'   => $ad.'_posts_number',
				'type' => 'number',
			));
	}
	elseif( strpos( $ad, 'article_inline_ad' ) !== false ){

		tie_build_theme_option(
			array(
				'name' => esc_html__( 'Number of paragraphs before the Ad', TIELABS_TEXTDOMAIN ),
				'id'   => $ad.'_paragraphs_number',
				'type' => 'number',
			));

		tie_build_theme_option(
			array(
				'name'    => esc_html__( 'Ad Alignment', TIELABS_TEXTDOMAIN ),
				'id'      => $ad.'_align',
				'type'    => 'radio',
				'options' => array(
					'center' => esc_html__( 'Center', TIELABS_TEXTDOMAIN ),
					'right'  => esc_html__( 'Right',  TIELABS_TEXTDOMAIN ),
					'left'   => esc_html__( 'Left',   TIELABS_TEXTDOMAIN ),
				)));
	}


	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Ad Title', TIELABS_TEXTDOMAIN ),
			'hint' => esc_html__( 'A title for the Ad, like Advertisement - leave this empty to disable.', TIELABS_TEXTDOMAIN ),
			'id'   => $ad.'_title',
			'type' => 'text',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Ad Title Link', TIELABS_TEXTDOMAIN ),
			'id'   => $ad.'_title_link',
			'type' => 'text',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Ad Image Width', TIELABS_TEXTDOMAIN ),
			'id'   => $ad.'_img_width',
			'type' => 'number',
			'hint' => '<a href="https://web.dev/cls/" target="_blank">'. esc_html__( 'Recommended to reduce Cumulative Layout Shift (CLS)', TIELABS_TEXTDOMAIN ) .'</a>',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Ad Image Height', TIELABS_TEXTDOMAIN ),
			'id'   => $ad.'_img_height',
			'type' => 'number',
			'hint' => '<a href="https://web.dev/cls/" target="_blank">'. esc_html__( 'Recommended to reduce Cumulative Layout Shift (CLS)', TIELABS_TEXTDOMAIN ) .'</a>',
		));
	
	tie_build_theme_option(
		array(
			'name'     => esc_html__( 'Ad Image', TIELABS_TEXTDOMAIN ),
			'id'       => $ad.'_img',
			'pre_text' => esc_html__( 'Ad Image', TIELABS_TEXTDOMAIN ),
			'type'     => 'upload',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Ad URL', TIELABS_TEXTDOMAIN ),
			'id'   => $ad.'_url',
			'type' => 'text',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Alternative Text For The image', TIELABS_TEXTDOMAIN ),
			'id'   => $ad.'_alt',
			'type' => 'text',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Open The Link In a new Tab', TIELABS_TEXTDOMAIN ),
			'id'   => $ad.'_tab',
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name' => esc_html__( 'Nofollow?', TIELABS_TEXTDOMAIN ),
			'id'   => $ad.'_nofollow',
			'type' => 'checkbox',
		));

	tie_build_theme_option(
		array(
			'name'     => esc_html__( 'Custom Ad Code', TIELABS_TEXTDOMAIN ),
			'id'       => $ad.'_adsense',
			'pre_text' => esc_html__( '- OR -', TIELABS_TEXTDOMAIN ) . ' ' . esc_html__( 'Custom Ad Code', TIELABS_TEXTDOMAIN ),
			'hint'     => esc_html__( 'Supports: Text, HTML and Shortcodes.', TIELABS_TEXTDOMAIN ),
			'type'     => 'textarea',
		));

	if( function_exists( 'adrotate_ad' ) ) {

		echo '<div id="'.$ad.'-adrotate-options">';

		tie_build_theme_option(
			array(
				'name'     => esc_html__( 'AdRotate', TIELABS_TEXTDOMAIN ),
				'id'       => $ad.'_adrotate',
				'pre_text' => esc_html__( '- OR -', TIELABS_TEXTDOMAIN ),
				'toggle'   => '#'.$ad.'_adrotate_type-item, #'.$ad.'_adrotate_id-item',
				'type'     => 'checkbox',
			));

		tie_build_theme_option(
			array(
				'name'    => esc_html__( 'Type', TIELABS_TEXTDOMAIN ),
				'id'      => $ad.'_adrotate_type',
				'type'    => 'radio',
				'options' => array(
					'single' => esc_html__( 'Advert - Use Advert ID', TIELABS_TEXTDOMAIN ),
					'group'  => esc_html__( 'Group - Use group ID', TIELABS_TEXTDOMAIN ),
				)));

		tie_build_theme_option(
			array(
				'name' => esc_html__( 'ID', TIELABS_TEXTDOMAIN ),
				'id'   => $ad.'_adrotate_id',
				'type' => 'number',
			));

		echo '</div>';
	}
}

tie_build_theme_option(
	array(
		'title' => esc_html__( 'Shortcodes Ads', TIELABS_TEXTDOMAIN ),
		'id'    => 'shortcodes-ads',
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'name' => '[ads1] '. esc_html__( 'Ad Shortcode', TIELABS_TEXTDOMAIN ),
		'id'   => 'ads1_shortcode',
		'type' => 'textarea',
	));

tie_build_theme_option(
	array(
		'name' => '[ads2] '. esc_html__( 'Ad Shortcode', TIELABS_TEXTDOMAIN ),
		'id'   => 'ads2_shortcode',
		'type' => 'textarea',
	));

tie_build_theme_option(
	array(
		'name' => '[ads3] '. esc_html__( 'Ad Shortcode', TIELABS_TEXTDOMAIN ),
		'id'   => 'ads3_shortcode',
		'type' => 'textarea',
	));

tie_build_theme_option(
	array(
		'name' => '[ads4] '. esc_html__( 'Ad Shortcode', TIELABS_TEXTDOMAIN ),
		'id'   => 'ads4_shortcode',
		'type' => 'textarea',
	));

tie_build_theme_option(
	array(
		'name' => '[ads5] '. esc_html__( 'Ad Shortcode', TIELABS_TEXTDOMAIN ),
		'id'   => 'ads5_shortcode',
		'type' => 'textarea',
	));
