<?php

tie_build_theme_option(
	array(
		'title' => esc_html__( 'Images Settings', TIELABS_TEXTDOMAIN ),
		'id'    => 'images-settings-tab',
		'type'  => 'tab-title',
	));

tie_build_theme_option(
	array(
		'title' => esc_html__( 'GIF Featured Image', TIELABS_TEXTDOMAIN ),
		'id'    => 'GIF-settings-section',
		'type'  => 'header',
	));


tie_build_theme_option(
	array(
		'name' => esc_html__( 'Disable GIF Featured Images', TIELABS_TEXTDOMAIN ),
		'id'   => 'disable_featured_gif',
		'type' => 'checkbox',
	));


tie_build_theme_option(
	array(
		'title' => esc_html__( 'Default Featured Image', TIELABS_TEXTDOMAIN ),
		'id'    => 'default-featured-image-settings-section',
		'type'  => 'header',
	));

tie_build_theme_option(
	array(
		'name'   => esc_html__( 'Enable', TIELABS_TEXTDOMAIN ),
		'id'     => 'default_featured_image',
		'type'   => 'checkbox',
		'toggle' => '#default_featured_image_id-item',
		'hint'   => esc_html__( 'This featured image will show up if no featured image is set.', TIELABS_TEXTDOMAIN ),
	));

tie_build_theme_option(
	array(
		'name'    => esc_html__( 'The Default Image', TIELABS_TEXTDOMAIN ),
		'id'      => 'default_featured_image_id',
		'type'    => 'select_image',
	));


	tie_build_theme_option(
		array(
			'title' => esc_html__( 'Image Sizes', TIELABS_TEXTDOMAIN ),
			'id'    => 'image-sizes-settings-section',
			'type'  => 'header',
		));
	
	tie_build_theme_option(
		array(
			'text' => sprintf( esc_html__( 'IMPORTANT: If you made any change you will need to run the %s plugin to regenerate thumbnails in the new sizes.', TIELABS_TEXTDOMAIN ), '<a href="https://wordpress.org/plugins/regenerate-thumbnails/" target="_blank">Regenerate Thumbnails</a>' ),
			'type' => 'message',
		));
		


	foreach( tie_default_image_sizes() as $name => $args ){

		$image_sizes = tie_get_option( 'image_size_'.$name );
		
		?>

		<div id="image-sizes" class="option-item disable_image_size_<?php echo esc_attr( $name ); ?>">
			<span class="tie-label" style="text-transform:capitalize"><?php echo str_replace( '-', ' ', $name ) ?></span>

			<div class="option-contents">	

				<?php
				tie_build_theme_option(
					array(
						'name'   => esc_html__( 'Disable', TIELABS_TEXTDOMAIN ),
						'id'     => 'disable_image_size_'.$name,
						'type'   => 'checkbox',
					));
				?>

				<div id="image-custom-size-<?php echo esc_attr( $name ); ?>-item" class="image-sizes-options" >	

					<div class="option-item" style="padding-top: 8px;">
						<label>
							<span class="tie-label"><?php esc_html_e( 'Width (px)', TIELABS_TEXTDOMAIN ) ?></span>
							<input name="tie_options[image_size_<?php esc_html_e( $name ) ?>][width]" type="number" value="<?php if( ! empty( $image_sizes['width'] ) ) echo esc_attr( $image_sizes['width'] ); ?>">
							<?php 
								echo '<span class="extra-text" style="display: inline-block; padding: 8px 0 0;">'. sprintf( esc_html__( 'Default is: %s', TIELABS_TEXTDOMAIN ), $args['width'] ) .'</span>';
							?>
						</label>
						<div class="clear"></div>
					</div>


					<div class="option-item">
						<label>
							<span class="tie-label"><?php esc_html_e( 'Height (px)', TIELABS_TEXTDOMAIN ) ?></span>
							<input name="tie_options[image_size_<?php esc_html_e( $name ) ?>][height]" type="number" value="<?php if( ! empty( $image_sizes['height'] ) ) echo esc_attr( $image_sizes['height'] ); ?>">
							<?php 
								echo '<span class="extra-text" style="display: inline-block; padding: 8px 0 0;">'. sprintf( esc_html__( 'Default is: %s', TIELABS_TEXTDOMAIN ), $args['height'] ) .'</span>';
							?>
						</label>
						<div class="clear"></div>
					</div>

					<div class="option-item">
						<label>
							<span class="tie-label"><?php esc_html_e( 'Crop thumbnail to exact dimensions?', TIELABS_TEXTDOMAIN ) ?></span>
							<select  name="tie_options[image_size_<?php esc_html_e( $name ) ?>][crop]">
								<option value=""><?php esc_html_e( 'Default', TIELABS_TEXTDOMAIN ) ?></option>
								<option value="yes" <?php if( ! empty( $image_sizes['crop'] ) && $image_sizes['crop'] == 'yes' ) echo 'selected="selected"' ?>><?php esc_html_e( 'Yes', TIELABS_TEXTDOMAIN ) ?></option>
								<option value="no" <?php if( ! empty( $image_sizes['crop'] ) && $image_sizes['crop'] == 'no' ) echo 'selected="selected"' ?>><?php esc_html_e( 'No', TIELABS_TEXTDOMAIN ) ?></option>
							</select>
						</label>
						<div class="clear"></div>
					</div>
				</div><!-- .image-sizes-options -->


			</div><!-- option-contents -->
			<div class="clear"></div>

		</div>
	<?php

	}
?>

	<script>
	jQuery(document).ready(function() {
		jQuery('#image-sizes input').each(function(){
			var $thisElement = jQuery(this),
			    elementType  = $thisElement.attr('type'),
					$toggleItems = $thisElement.closest('.option-contents').find( '.image-sizes-options' );
						
			if( elementType == 'checkbox' ){
				if( $thisElement.is(':checked')){
					$toggleItems.hide();
				};
				$thisElement.change(function(){
					$toggleItems.slideToggle('fast'); 
				});
			}
		});
	});
</script>



