<?php
/**
 * Category Settings Class
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly



if( ! class_exists( 'TIELABS_SETTINGS_CATEGORY' ) ) {

	class TIELABS_SETTINGS_CATEGORY{


		/**
		 * __construct
		 *
		 * Class constructor where we will call our filter and action hooks.
		 */
		function __construct(){

			add_action( 'edited_category', array( $this, 'save' ) );
			add_action( 'category_edit_form_fields', array( $this, 'custom_options' ) );

			// Category Settings
			add_filter( 'TieLabs/Settings/Category/category-layout', array( $this, 'layout_settings' ),       10, 2 );
			add_filter( 'TieLabs/Settings/Category/posts-settings',  array( $this, 'posts_layout_settings' ), 10, 2 );
			add_filter( 'TieLabs/Settings/Category/slider',          array( $this, 'slider_settings' ),       10, 2 );
			add_filter( 'TieLabs/Settings/Category/logo',            array( $this, 'logo_settings' ),         10, 2 );
			add_filter( 'TieLabs/Settings/Category/menu',            array( $this, 'menu_settings' ),         10, 2 );
			add_filter( 'TieLabs/Settings/Category/sidebar',         array( $this, 'sidebar_settings'),       10, 2 );
			add_filter( 'TieLabs/Settings/Category/styles',          array( $this, 'styles_settings' ),       10, 2 );
		}


		/**
		 * Build The Category Option
		 */
		function build_option( $option, $category_id ){

			$id   = ! empty( $option['id'] ) ? $option['id'] : '';
			$data = tie_get_category_option( $id, $category_id );

			tie_build_option( $option, 'tie_cat['. $id .']', $data );
		}


		/**
		 * Save Category Options
		 */
		function save( $category_id ){

			if( empty( $_POST['tie_cat'] ) ){
				return;
			}

			// New Category Data
			$category_data    = apply_filters( 'TieLabs/save_category', $_POST['tie_cat'] );
			$category_data = TIELABS_ADMIN_HELPER::clean_settings( $category_data );
			$category_data = TIELABS_ADMIN_HELPER::array_filter( $category_data );

			// Get the stored categories settings
			$tie_cats_options = get_option( 'tie_cats_options' );
			if( empty( $tie_cats_options ) || ! is_array( $tie_cats_options ) ){
				$tie_cats_options = array();
			}

			// Store the new Settings
			$tie_cats_options[ $category_id ] = $category_data;
			update_option( 'tie_cats_options', $tie_cats_options, 'yes' );
		}


		/**
		 * Category Custom Options
		 */
		function custom_options( $category ){

			wp_enqueue_media();
			wp_enqueue_script( 'wp-color-picker' );
			?>

			<tr class="form-field">
				<td colspan="2">

					<?php

					$settings_tabs = apply_filters( 'TieLabs/Settings/Category', array(

						'category-layout' => array(
							'icon'  => 'admin-settings',
							'title' => esc_html__( 'Category Layout', TIELABS_TEXTDOMAIN ),
						),

						'posts-settings' => array(
							'icon'	=> 'schedule',
							'title'	=> esc_html__( 'Posts Settings', TIELABS_TEXTDOMAIN ),
						),

						'slider' => array(
							'icon'	=> 'format-gallery',
							'title'	=> esc_html__( 'Slider', TIELABS_TEXTDOMAIN ),
						),

						'logo' => array(
							'icon'	=> 'lightbulb',
							'title'	=> esc_html__( 'Logo', TIELABS_TEXTDOMAIN ),
						),

						'sidebar' => array(
							'icon'  => 'slides',
							'title' => esc_html__( 'Sidebar', TIELABS_TEXTDOMAIN ),
						),

						'styles' => array(
							'icon'  => 'art',
							'title' => esc_html__( 'Styling', TIELABS_TEXTDOMAIN ),
						),

						'menu' => array(
							'icon'  => 'menu',
							'title' => esc_html__( 'Custom Menus', TIELABS_TEXTDOMAIN ),
						),
					));

					?>

					<div id="poststuff">
						<div id="tie_post_options" class="postbox ">
							<h2 class="hndle ui-sortable-handle"><span><?php echo apply_filters( 'TieLabs/theme_name', 'TieLabs' ) .' - '. esc_html__( 'Category Options', TIELABS_TEXTDOMAIN ) ?></span></h2>
							<div class="inside">
								<div class="tie-panel">
									<div class="tie-panel-tabs">
										<ul>
											<?php
												foreach( $settings_tabs as $tab => $settings ){

													$icon  = $settings['icon'];
													$title = $settings['title'];
													?>

													<li class="tie-tabs tie-options-tab-<?php echo esc_attr( $tab ) ?>">
														<a href="#tie-options-tab-<?php echo esc_attr( $tab ) ?>">
															<span class="dashicons-before dashicons-<?php echo esc_attr( $icon ) ?> tie-icon-menu"></span>
															<?php esc_html_e( $title ); ?>
														</a>
													</li>

													<?php
												}
											?>
										</ul>
										<div class="clear"></div>
									</div> <!-- .tie-panel-tabs -->

									<div class="tie-panel-content">
										<?php
											foreach( $settings_tabs as $tab => $settings ){
												?>

												<div id="tie-options-tab-<?php echo esc_attr( $tab ) ?>" class="tabs-wrap">
													<?php

														do_action( 'TieLabs/Settings/Category/before_'.$tab );

														$tab_options = apply_filters( 'TieLabs/Settings/Category/'.$tab, array(), $category->term_id );

														if( ! empty( $tab_options ) && is_array( $tab_options ) ){
															foreach ( $tab_options as $option ){
																$this->build_option( $option, $category->term_id );
															}
														}

														do_action( 'TieLabs/Settings/Category/after_'.$tab );
													?>
												</div>

												<?php
											}
										?>
									</div><!-- .tie-panel-content -->

									<div class="clear"></div>
								</div><!-- .tie-panel -->
							</div><!-- .inside /-->
						</div><!-- #tie_post_options /-->
					</div><!-- #poststuff /-->

				</td>
			</tr>
			<?php
		}


		/**
		 * Category Layout Settings
		 */
		function layout_settings( $current_settings, $category_id ){

			// Post Order
			$post_order = array( '' => esc_html__( 'Default', TIELABS_TEXTDOMAIN ) ) + TIELABS_ADMIN_HELPER::get_orderby_list();
			unset( $post_order['views_7_days'] );

			// ---
			$settings = array(

				array(
					'title' => esc_html__( 'Category Layout', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'id'      => 'category_layout',
					'type'    => 'visual',
					'options' => array(
						''               => array( esc_html__( 'Default', TIELABS_TEXTDOMAIN )          => 'default.png' ),
						'excerpt'        => array( esc_html__( 'Classic', TIELABS_TEXTDOMAIN )          => 'archives/blog.png' ),
						'full_thumb'     => array( esc_html__( 'Large Thumbnail', TIELABS_TEXTDOMAIN )  => 'archives/full-thumb.png' ),
						'content'        => array( esc_html__( 'Content', TIELABS_TEXTDOMAIN )          => 'archives/content.png' ),
						'timeline'       => array( esc_html__( 'Timeline', TIELABS_TEXTDOMAIN )         => 'archives/timeline.png' ),
						'masonry'        => array( esc_html__( 'Masonry', TIELABS_TEXTDOMAIN ).' #1'    => 'archives/masonry.png' ),
						'overlay'        => array( esc_html__( 'Masonry', TIELABS_TEXTDOMAIN ).' #2'    => 'archives/overlay.png' ),
						'overlay-spaces' => array( esc_html__( 'Masonry', TIELABS_TEXTDOMAIN ).' #3'    => 'archives/overlay-spaces.png' ),
						'first_big'      => array( esc_html__( 'Large Post Above', TIELABS_TEXTDOMAIN ) => 'archives/first_big.png' ),
						'overlay-title'  => array( esc_html__( 'Overlay Title', TIELABS_TEXTDOMAIN )    => 'archives/overlay-title.png' ),
						'overlay-title-center' => array( esc_html__( 'Overlay Title Centered', TIELABS_TEXTDOMAIN ) => 'archives/overlay-title-center.png' ),
						'classic-small'  => array( esc_html__( 'Classic Small', TIELABS_TEXTDOMAIN )    => 'archives/blog-small.png' ),
				)),

				array(
					'name' => esc_html__( 'Posts Excerpt', TIELABS_TEXTDOMAIN ),
					'id'   => 'category_excerpt',
					'type' => 'select',
					'options' => array(
						''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
						'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
						'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
					)),

				array(
					'name' => esc_html__( 'Excerpt Length', TIELABS_TEXTDOMAIN ),
					'id'   => 'category_excerpt_length',
					'type' => 'number',
				),

				array(
					'name' => esc_html__( 'Read More Button', TIELABS_TEXTDOMAIN ),
					'id'   => 'category_read_more',
					'type' => 'select',
					'options' => array(
						''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
						'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
						'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
					)),

				array(
					'name' => esc_html__( 'Custom Read More Button text', TIELABS_TEXTDOMAIN ),
					'id'   => 'category_read_more_text',
					'type' => 'text',
					'hint' => esc_html__( 'Leave it empty to use the default text.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name'    => esc_html__( 'Pagination', TIELABS_TEXTDOMAIN ),
					'id'      => 'category_pagination',
					'type'    => 'radio',
					'options' => array(
						''          => esc_html__( 'Default',           TIELABS_TEXTDOMAIN ),
						'next-prev' => esc_html__( 'Next and Previous', TIELABS_TEXTDOMAIN ),
						'numeric'   => esc_html__( 'Numeric',           TIELABS_TEXTDOMAIN ),
						'load-more' => esc_html__( 'Load More',         TIELABS_TEXTDOMAIN ),
						'infinite'  => esc_html__( 'Infinite Scroll',   TIELABS_TEXTDOMAIN ),
				)),

				array(
					'name'  => esc_html__( 'Media Icon', TIELABS_TEXTDOMAIN ),
					'id'    => 'category_media_overlay',
					'type'  => 'checkbox',
				),

				array(
					'title' => esc_html__( 'Sort by', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'    => esc_html__( 'Sort by', TIELABS_TEXTDOMAIN ),
					'id'      => 'posts_order',
					'type'    => 'select',
					'options' => apply_filters( 'TieLabs/Settings/Category/posts_order', $post_order )
				),

				array(
					'name'    => esc_html__( 'Order', TIELABS_TEXTDOMAIN ),
					'id'      => 'asc_or_desc',
					'type'    => 'select',
					'options' => array(
						''    => esc_html__( 'Descending', TIELABS_TEXTDOMAIN ),
						'ASC' => esc_html__( 'Ascending', TIELABS_TEXTDOMAIN ),
					)
				),

				array(
					'title' => esc_html__( 'Don\'t duplicate posts', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name' => esc_html__( 'Don\'t duplicate posts', TIELABS_TEXTDOMAIN ),
					'id'   => 'do_not_dublicate',
					'type' => 'checkbox',
					'hint' => esc_html__( 'Note: This option doesn\'t work with the AJAX pagination.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'title' => esc_html__( 'Category Page Layout', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'id'      => 'cat_theme_layout',
					'type'    => 'visual',
					'options' => array(
						''       => array( esc_html__( 'Default',    TIELABS_TEXTDOMAIN ) => 'default.png' ),
						'full'   => array( esc_html__( 'Full-Width', TIELABS_TEXTDOMAIN ) => 'layouts/layout-full.png' ),
						'boxed'  => array( esc_html__( 'Boxed',      TIELABS_TEXTDOMAIN ) => 'layouts/layout-boxed.png' ),
						'framed' => array( esc_html__( 'Framed',     TIELABS_TEXTDOMAIN ) => 'layouts/layout-framed.png' ),
						'border' => array( esc_html__( 'Bordered',   TIELABS_TEXTDOMAIN ) => 'layouts/layout-border.png' ),
				)),

				array(
					'title' => esc_html__( 'Subcategories', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'  => esc_html__( 'Subcategories Section', TIELABS_TEXTDOMAIN ),
					'id'    => 'subcategories',
					'type'  => 'checkbox',
					'toggle' => '#subcategories_title-item, #subcategories_style-item, #subcategories_layout-item, #subcategories_bgs-item, #subcategories_icon-item, #subcategories_count-item',
				),

				array(
					'name'  => esc_html__( 'Subcategories Section Title', TIELABS_TEXTDOMAIN ),
					'id'    => 'subcategories_title',
					'type'	=> 'text',
				),

				array(
					'id'      => 'subcategories_style',
					'type'    => 'visual',
					'options' => array(
						''   => array( esc_html__( 'Vertical',   TIELABS_TEXTDOMAIN ) => 'blocks/categories-vertical.png' ),
						'horizontal' => array( esc_html__( 'Horizontal', TIELABS_TEXTDOMAIN ) => 'blocks/categories-horizontal.png' ),
				)),

				array(
					'name'    => esc_html__( 'Layout', TIELABS_TEXTDOMAIN ),
					'id'      => 'subcategories_layout',
					'type'    => 'radio',
					'options' => array(
						'' => esc_html__( 'Horizontal',          TIELABS_TEXTDOMAIN ),
						'vertical'   => esc_html__( 'Vertical - Auto',     TIELABS_TEXTDOMAIN ),
						'vertical-1' => esc_html__( 'Vertical - 1 Column', TIELABS_TEXTDOMAIN ),
						'vertical-2' => esc_html__( 'Vertical - 2 Column', TIELABS_TEXTDOMAIN ),
						'vertical-3' => esc_html__( 'Vertical - 3 Column', TIELABS_TEXTDOMAIN ),
				)),

				array(
					'name'  => esc_html__( 'Show Backgrounds', TIELABS_TEXTDOMAIN ),
					'id'    => 'subcategories_bgs',
					'type'  => 'checkbox',
				),

				array(
					'name'  => esc_html__( 'Show Icons', TIELABS_TEXTDOMAIN ),
					'id'    => 'subcategories_icon',
					'type'  => 'checkbox',
				),

				array(
					'name'  => esc_html__( 'Show Number of Posts', TIELABS_TEXTDOMAIN ),
					'id'    => 'subcategories_count',
					'type'  => 'checkbox',
				),

				array(
					'title' => esc_html__( 'Footer Description', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'   => esc_html__( 'Footer Description', TIELABS_TEXTDOMAIN ),
					'id'     => 'footer_description',
					'type'   => 'checkbox',
					'toggle' => '#secondary_description_title-item, #secondary_description-item',
				),

				array(
					'name'  => esc_html__( 'Footer Description Title', TIELABS_TEXTDOMAIN ),
					'id'    => 'secondary_description_title',
					'type'	=> 'text',
				),

				array(
					'name'   => esc_html__( 'Footer Description', TIELABS_TEXTDOMAIN ),
					'id'     => 'secondary_description',
					'type'	 => 'editor',
					'hint'   => esc_html__( 'This Description will appear at the end of the category page below the posts list.', TIELABS_TEXTDOMAIN ),
					'editor' => array(
						'media_buttons' => true,
						'quicktags'     => true,
						'textarea_rows' => '15',
						'editor_height' => '300px'
					),
					'kses'   => true,
				),


				array(
					'title' => esc_html__( 'Category', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'  => esc_html__( 'Category Icon', TIELABS_TEXTDOMAIN ),
					'id'    => 'brand_icon',
					'type'  => 'upload',
				),

				array(
					'name'  => esc_html__( 'Category Image', TIELABS_TEXTDOMAIN ),
					'id'    => 'brand_bg',
					'type'  => 'upload',
				),

			);


			if( ! empty( $current_settings ) && is_array( $current_settings ) ){
				$settings = array_merge( $current_settings, $settings );
			}

			return apply_filters( 'TieLabs/Settings/Category/category-layout/defaults', $settings );
		}


		/**
		 * Post Layout Settings
		 */
		function posts_layout_settings( $current_settings, $category_id ){

			$settings = array(

				array(
					'title' => esc_html__( 'Post Layout', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'id'      => 'cat_post_layout',
					'type'    => 'visual',
					'toggle'  => array(
						'' => '',
						'4' => '#cat_featured_bg_title, #cat_featured_use_fea-item, #cat_featured_custom_bg-item',
						'5' => '#cat_featured_bg_title, #cat_featured_use_fea-item, #cat_featured_custom_bg-item',
						'8' => '#cat_featured_bg_title, #cat_featured_use_fea-item, #cat_featured_custom_bg-item, #cat_featured_bg_color-item',),
					'options' => array(
						''  => array( esc_html__( 'Default', TIELABS_TEXTDOMAIN )       => 'default.png' ),
						'1' => array( esc_html__( 'Layout',  TIELABS_TEXTDOMAIN ).' #1' => 'post-layouts/1.png' ),
						'2' => array( esc_html__( 'Layout',  TIELABS_TEXTDOMAIN ).' #2' => 'post-layouts/2.png' ),
						'3' => array( esc_html__( 'Layout',  TIELABS_TEXTDOMAIN ).' #3' => 'post-layouts/3.png' ),
						'4' => array( esc_html__( 'Layout',  TIELABS_TEXTDOMAIN ).' #4' => 'post-layouts/4.png' ),
						'5' => array( esc_html__( 'Layout',  TIELABS_TEXTDOMAIN ).' #5' => 'post-layouts/5.png' ),
						'6' => array( esc_html__( 'Layout',  TIELABS_TEXTDOMAIN ).' #6' => 'post-layouts/6.png' ),
						'7' => array( esc_html__( 'Layout',  TIELABS_TEXTDOMAIN ).' #7' => 'post-layouts/7.png' ),
						'8' => array( esc_html__( 'Layout',  TIELABS_TEXTDOMAIN ).' #8' => 'post-layouts/8.png' ),
					)),

				array(
					'title' => esc_html__( 'Featured area background', TIELABS_TEXTDOMAIN ),
					'id'    => 'cat_featured_bg_title',
					'type'  => 'header',
					'class' => 'cat_post_layout',
				),

				array(
					'name'  => esc_html__( 'Use the featured image', TIELABS_TEXTDOMAIN ),
					'id'    => 'cat_featured_use_fea',
					'type'  => 'select',
					'class' => 'cat_post_layout',
					'options' => array(
						''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
						'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
						'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
					)),

				array(
					'name'     => esc_html__( 'Upload Custom Image', TIELABS_TEXTDOMAIN ),
					'id'       => 'cat_featured_custom_bg',
					'type'     => 'upload',
					'pre_text' => esc_html__( '- OR -', TIELABS_TEXTDOMAIN ),
					'class'    => 'cat_post_layout',
				),

				array(
					'name'  => esc_html__( 'Background Color', TIELABS_TEXTDOMAIN ),
					'id'    => 'cat_featured_bg_color',
					'type'  => 'color',
					'class' => 'cat_post_layout',
				),

				array(
					'title' => esc_html__( 'Post Format Settings', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'    => esc_html__( 'Standard Post Format:', TIELABS_TEXTDOMAIN ) .' '. esc_html__( 'Show the featured image', TIELABS_TEXTDOMAIN ),
					'id'      => 'cat_post_featured',
					'type'    => 'select',
					'options' => array(
						''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
						'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
						'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
				)),

				array(
					'name'    => esc_html__( 'Image Post Format:', TIELABS_TEXTDOMAIN ) .' '. esc_html__( 'Uncropped featured image', TIELABS_TEXTDOMAIN ),
					'id'      => 'cat_image_uncropped',
					'type'    => 'select',
					'options' => array(
						''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
						'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
						'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
				)),

				array(
					'name'    => esc_html__( 'Image Post Format:', TIELABS_TEXTDOMAIN ) .' '. esc_html__( 'Featured image lightbox', TIELABS_TEXTDOMAIN ),
					'id'      => 'cat_image_lightbox',
					'type'    => 'select',
					'options' => array(
						''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
						'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
						'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
				)),

				array(
					'title' =>	esc_html__( 'Structure Data', TIELABS_TEXTDOMAIN ),
					'id'    => 'structure-data',
					'type'  => 'header',
				),

				array(
					'name'    => esc_html__( 'Schema type', TIELABS_TEXTDOMAIN ),
					'id'      => 'cat_schema_type',
					'type'    => 'radio',
					'options' => array(
						''             => esc_html__( 'Default',      TIELABS_TEXTDOMAIN ),
						'Article'      => esc_html__( 'Article',      TIELABS_TEXTDOMAIN ),
						'NewsArticle'  => esc_html__( 'NewsArticle',  TIELABS_TEXTDOMAIN ),
						'BlogPosting'  => esc_html__( 'BlogPosting',  TIELABS_TEXTDOMAIN ),
					)),

					/*
					array(
						'title' => esc_html__( 'Post info Settings', TIELABS_TEXTDOMAIN ),
						'id'    => 'post-info-settings',
						'type'  => 'header',
					),

					array(
						'name'   => esc_html__( 'Post meta area', TIELABS_TEXTDOMAIN ),
						'id'     => 'post_meta',
						'toggle' => '#post_author-all-item, #post_meta_style-item, #post_date-item, #modified-item, #post_comments-item, #post_views-item, #reading_time-item, #words_per_min-item',
						'type'   => 'select',
						'options' => array(
							''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
							'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
							'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
						)),

					array(
						'id'       => 'post_meta_style',
						'name'     => esc_html__( 'Post meta area Layout', TIELABS_TEXTDOMAIN ),
						'type'     => 'visual',
						'options'  => array(
							''       => array( esc_html__( 'Default', TIELABS_TEXTDOMAIN ) => 'post-layouts/post-meta-1.png' ),
							'column' => array( esc_html__( 'Column', TIELABS_TEXTDOMAIN )  => 'post-layouts/post-meta-2.png' ),
						)),

					array(
						'content' => '<div id="post_author-all-item">',
						'type'    => 'html',
					),

					array(
						'name'   => esc_html__( 'Author', TIELABS_TEXTDOMAIN ),
						'id'     => 'post_author',
						'toggle' => '#post_author_wrap-item',
						'type'   => 'checkbox',
					),

					array(
						'content' => '<div id="post_author_wrap-item">',
						'type'    => 'html',
					),

					array(
						'name' => esc_html__( "Author's Avatar", TIELABS_TEXTDOMAIN ),
						'id'   => 'post_author_avatar',
						'type' => 'checkbox',
					),


					array(
						'name' => esc_html__( 'Twitter Icon', TIELABS_TEXTDOMAIN ),
						'id'   => 'post_author_twitter',
						'type' => 'checkbox',
					),

					array(
						'name' => esc_html__( 'Email Icon', TIELABS_TEXTDOMAIN ),
						'id'   => 'post_author_email',
						'type' => 'checkbox',
					),

					array(
						'content' => '</div></div>',
						'type'    => 'html',
					),

					array(
						'name'   => esc_html__( 'Date', TIELABS_TEXTDOMAIN ),
						'id'     => 'post_date',
						'type'   => 'checkbox',
						'toggle' => '#post_date_format-item',
					),

					array(
						'name' => esc_html__( 'Custom Date Format', TIELABS_TEXTDOMAIN ),
						'id'   => 'post_date_format',
						'type' => 'text',
					),

					array(
						'name' => esc_html__( 'Last Updated', TIELABS_TEXTDOMAIN ),
						'id'   => 'modified',
						'type' => 'checkbox',
					),

					array(
						'name' => esc_html__( 'Comments', TIELABS_TEXTDOMAIN ),
						'id'   => 'post_comments',
						'type' => 'checkbox',
					),

					array(
						'name' => esc_html__( 'Views', TIELABS_TEXTDOMAIN ),
						'id'   => 'post_views',
						'type' => 'checkbox',
					),

					array(
						'name'   => esc_html__( 'Estimated reading time', TIELABS_TEXTDOMAIN ),
						'id'     => 'reading_time',
						'type'   => 'checkbox',
						'toggle' => '#words_per_min-item',
					),
				*/
	
			);


			if( ! empty( $current_settings ) && is_array( $current_settings ) ){
				$settings = array_merge( $current_settings, $settings );
			}

			return apply_filters( 'TieLabs/Settings/Category/posts-settings/defaults', $settings );
		}


		/**
		 * Slider Settings
		 */
		function slider_settings( $current_settings, $category_id ){

			$sliders_list   = TIELABS_ADMIN_HELPER::get_sliders( true );
			$current_slider = tie_get_category_option( 'featured_posts_style', $category_id );
			$current_slider = ! empty( $current_slider ) ? $current_slider : 1 ;

			$slider_styles = array();

			$slider_path = 'blocks/block-';
			for( $slider = 1; $slider <= 17; $slider++ ){

				$slide_class 	= 'slider_'.$slider;
				$slide_img 		= $slider_path.'slider_'.$slider.'.png';

				$slider_styles[ $slider ] = array( sprintf( esc_html__( 'Slider #%s', TIELABS_TEXTDOMAIN ), $slider ) => array( $slide_class => $slide_img ) );
			}

			$slider_styles['50'] = array( sprintf( esc_html__( 'Slider #%s', TIELABS_TEXTDOMAIN ), 18 ) => array( 'slider_50 slider_4' => $slider_path.'slider_50.png' ) );

			$slider_styles['videos_list'] = array( esc_html__( 'Videos Playlist', TIELABS_TEXTDOMAIN ) => array( 'video_play_list' => $slider_path. 'videos_list.png' ) );

			$settings = array(

				array(
					'title' => esc_html__( 'TieLabs Slider', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'   => esc_html__( 'Enable', TIELABS_TEXTDOMAIN ),
					'id'     => 'featured_posts',
					'toggle' => '#main-slider-options',
					'type'   => 'checkbox',
				),

				array(
					'content' => '<div id="main-slider-options" style="display: none;" class="slider_'. esc_attr( $current_slider ) .'-container">',
					'type'    => 'html',
				),

				array(
					'id'      => 'featured_posts_style',
					'type'    => 'visual',
					'options' => $slider_styles,
				),

				array(
					'name'    =>  esc_html__( 'Number of posts to show', TIELABS_TEXTDOMAIN ),
					'id'      => 'featured_posts_number',
					'class'   => 'featured-posts',
					'default' => 10,
					'type'    => 'number',
				),

				array(
					'name'   => esc_html__( 'Query Type', TIELABS_TEXTDOMAIN ),
					'id'     => 'featured_posts_query',
					'class'  => 'featured-posts',
					'type'   => 'radio',
					'toggle' => array(
						'recent' => '',
						'random' => '',
						'custom' => '#featured_posts_custom-item' ),
					'options' => array(
						'recent' => esc_html__( 'Recent Posts',  TIELABS_TEXTDOMAIN ),
						'random' => esc_html__( 'Random Posts',  TIELABS_TEXTDOMAIN ),
						'custom' => esc_html__( 'Custom Slider', TIELABS_TEXTDOMAIN ),
				)),

				array(
					'content' => '<div class="featured-posts-options">',
					'type'    => 'html',
				),

				array(
					'name'    => esc_html__( 'Custom Slider', TIELABS_TEXTDOMAIN ),
					'id'      => 'featured_posts_custom',
					'class'   => 'featured_posts_query',
					'type'    => 'select',
					'options' => $sliders_list,
				),

				array(
					'content' => '</div>', // .featured-posts-options
					'type'    => 'html',
				),

				array(
					'name'  => esc_html__( 'Colored Mask', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_posts_colored_mask',
					'class' => 'featured-posts',
					'type'  => 'checkbox',
				),

				array(
					'name'  => esc_html__( 'Disable Gradient Overlay', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_posts_gradiant_overlay',
					'class' => 'featured-posts',
					'type'  => 'checkbox',
				),

				array(
					'name'  => esc_html__( 'Media Icon', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_posts_media_overlay',
					'class' => 'featured-posts',
					'type'  => 'checkbox',
				),

				array(
					'name'  =>  esc_html__( 'Animate Automatically', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_auto',
					'class' => 'featured-posts',
					'type'  => 'checkbox',
				),

				array(
					'name'  => esc_html__( 'Title Length', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_posts_title_length',
					'class'	=> 'featured-posts',
					'type'  => 'number',
				),

				array(
					'content' => '<div class="excerpt-options featured-posts-options">',
					'type'    => 'html',
				),

				array(
					'name'   => esc_html__( 'Posts Excerpt', TIELABS_TEXTDOMAIN ),
					'id'     => 'featured_posts_excerpt',
					'type'   => 'checkbox',
					'toggle' => '#featured_posts_excerpt_length-item',
				),

				array(
					'name'  => esc_html__( 'Posts Excerpt Length', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_posts_excerpt_length',
					'type'  => 'number',
				),

				array(
					'content' => '</div>', // excerpt-options featured-posts-options
					'type'    => 'html',
				),

				array(
					'name'  => esc_html__( 'Post Primary Category', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_posts_category',
					'class'	=> 'slider-category-option block-slider-categories-meta-options featured-posts',
					'type'  => 'checkbox',
				),

				array(
					'name'  => esc_html__( 'Review Rating', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_posts_review',
					'class'	=> 'slider-review-option block-slider-review-meta-options featured-posts',
					'type'  => 'checkbox',
				),

				array(
					'name'  => esc_html__( 'Post Meta', TIELABS_TEXTDOMAIN ),
					'id'    => 'featured_posts_date',
					'class'	=> 'featured-posts',
					'type'  => 'checkbox',
				),

				array(
					'name'    => esc_html__( 'Video Playlist', TIELABS_TEXTDOMAIN ),
					'id'      => 'wmvp_id',
					'type'    => 'select',
					'class'   => 'featured-videos',
					'options' => TIELABS_ADMIN_HELPER::get_playlists(),
				),

				array(
					'title' => esc_html__( 'Styling', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'   => esc_html__( 'Dark Mode', TIELABS_TEXTDOMAIN ),
					'id'    => 'dark_skin',
					'class' => 'featured-videos',
					'type'  => 'checkbox',
				),

				array(
					'name' => esc_html__( 'Background Color', TIELABS_TEXTDOMAIN ),
					'id'   => 'featured_posts_color',
					'type' => 'color',
				),

				array(
					'name' => esc_html__( 'Background Image', TIELABS_TEXTDOMAIN ),
					'id'   => 'featured_posts_bg',
					'type' => 'upload',
				),

				array(
					'name' => esc_html__( 'Background Video', TIELABS_TEXTDOMAIN ),
					'id'   => 'featured_posts_bg_video',
					'type' => 'text',
				),

				array(
					'name'   => esc_html__( 'Parallax', TIELABS_TEXTDOMAIN ),
					'id'     => 'featured_posts_parallax',
					'type'   => 'checkbox',
					'toggle' => '#featured_posts_parallax_effect-item',
				),

				array(
					'name' => esc_html__( 'Parallax Effect', TIELABS_TEXTDOMAIN ),
					'id'   => 'featured_posts_parallax_effect',
					'type' => 'select',
					'options' => array(
						'scroll'         => esc_html__( 'Scroll',           TIELABS_TEXTDOMAIN ),
						'scale'          => esc_html__( 'Scale',            TIELABS_TEXTDOMAIN ),
						'opacity'        => esc_html__( 'Opacity',          TIELABS_TEXTDOMAIN ),
						'scroll-opacity' => esc_html__( 'Scroll + Opacity', TIELABS_TEXTDOMAIN ),
						'scale-opacity'  => esc_html__( 'Scale + Opacity',  TIELABS_TEXTDOMAIN ),
				)),

				array(
					'content' => '</div>', // main-slider-options
					'type'    => 'html',
				),
			);

			// Revolution Slider
			if( TIELABS_REVSLIDER_IS_ACTIVE ){

				$settings[] =	array(
					'title' => esc_html__( 'Revolution Slider', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				);

				$rev_slider = new RevSlider();
				$rev_slider = $rev_slider->getArrSlidersShort();

				if( ! empty( $rev_slider ) && is_array( $rev_slider ) ) {

					$arrSliders = array( '' => esc_html__( 'Disable', TIELABS_TEXTDOMAIN ) );

					foreach( $rev_slider as $id => $item ){
						$name = empty( $item ) ? esc_html__( 'Unnamed', TIELABS_TEXTDOMAIN ) : $item;
						$arrSliders[ $id ] = $name . ' | #' .$id;
					}

					$settings[] = array(
						'text' => esc_html__( 'Will override the sliders above.', TIELABS_TEXTDOMAIN ),
						'type' => 'message',
					);

					$settings[] = array(
						'name'    => esc_html__( 'Choose Slider', TIELABS_TEXTDOMAIN ),
						'id'      => 'revslider',
						'type'    => 'select',
						'options' => $arrSliders,
					);
				}
				else{

					$settings[] = array(
						'text' => esc_html__( 'No sliders found, Please create a slider.', TIELABS_TEXTDOMAIN ),
						'type' => 'error',
					);
				}
			}

			// LayerSlider
			if( TIELABS_LS_Sliders_IS_ACTIVE ){

				$settings[] = array(
					'title' => esc_html__( 'LayerSlider', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				);

				$ls_sliders = LS_Sliders::find(array('limit' => 100));

				if( ! empty( $ls_sliders ) && is_array( $ls_sliders ) ){

					$settings[] =	array(
						'text' => esc_html__( 'Will override the sliders above.', TIELABS_TEXTDOMAIN ),
						'type' => 'message',
					);

					$arrSliders = array( '' => esc_html__( 'Disable', TIELABS_TEXTDOMAIN ) );

					foreach( $ls_sliders as $item ){
						$name = empty( $item['name'] ) ? esc_html__( 'Unnamed', TIELABS_TEXTDOMAIN ) : $item['name'];
						$arrSliders[ $item['id'] ] = $name . ' | #' .$item['id'];
					}

					$settings[] =	array(
						'name'    => esc_html__( 'Choose Slider', TIELABS_TEXTDOMAIN ),
						'id'      => 'lsslider',
						'type'    => 'select',
						'options' => $arrSliders,
					);
				}
				else{

					$settings[] =	array(
						'text' => esc_html__( 'No sliders found, Please create a slider.', TIELABS_TEXTDOMAIN ),
						'type' => 'error',
					);
				}
			}


			if( ! empty( $current_settings ) && is_array( $current_settings ) ){
				$settings = array_merge( $current_settings, $settings );
			}

			return apply_filters( 'TieLabs/Settings/Category/slider/defaults', $settings );
		}


		/**
		 * Logo Settings
		 */
		function logo_settings( $current_settings, $category_id ){

			$settings = array(

				array(
					'title' => esc_html__( 'Custom Logo', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'   => esc_html__( 'Custom Logo', TIELABS_TEXTDOMAIN ),
					'id'     => 'custom_logo',
					'toggle' => '#tie-post-logo-item',
					'type'   => 'checkbox',
				),

				array(
					'content' => '<div id="tie-post-logo-item">',
					'type'    => 'html',
				),

				array(
					'name'    => esc_html__( 'Logo Settings', TIELABS_TEXTDOMAIN ),
					'id'      => 'logo_setting',
					'type'    => 'radio',
					'toggle'  => array(
						'logo'  => '#logo-image-settings, #mobile-logo-image-settings',
						'title' => ''),
					'options'	=> array(
						'logo'  => esc_html__( 'Image', TIELABS_TEXTDOMAIN ),
						'title' => esc_html__( 'Site Title', TIELABS_TEXTDOMAIN ),
				)),

				array(
					'content' => '<div id="logo-image-settings" class="logo_setting-options">',
					'type'    => 'html',
				),

				array(
					'name'  => esc_html__( 'Logo Image', TIELABS_TEXTDOMAIN ),
					'id'    => 'logo',
					'type'  => 'upload',
				),

				array(
					'name'  => esc_html__( 'Logo Image (Retina Version @2x)', TIELABS_TEXTDOMAIN ),
					'id'    => 'logo_retina',
					'type'  => 'upload',
					'hint'	=> esc_html__( 'Please choose an image file for the retina version of the logo. It should be 2x the size of main logo.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name'  => esc_html__( 'Logo Inverted Image', TIELABS_TEXTDOMAIN ),
					'id'    => 'logo_inverted',
					'type'  => 'upload',
					'hint'	=> '<strong>'. esc_html__( 'Used if users are allowed to switch between Light and Dark Modes.', TIELABS_TEXTDOMAIN ) .'</strong>',
				),

				array(
					'name'  => esc_html__( 'Logo Inverted Image (Retina Version @2x)', TIELABS_TEXTDOMAIN ),
					'id'    => 'logo_inverted_retina',
					'type'  => 'upload',
					'hint'	=> '<strong>'. esc_html__( 'Used if users are allowed to switch between Light and Dark Modes.', TIELABS_TEXTDOMAIN ) .'</strong><br />'. esc_html__( 'Please choose an image file for the retina version of the logo. It should be 2x the size of main logo.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name'  => esc_html__( 'Logo width', TIELABS_TEXTDOMAIN ),
					'id'    => 'logo_retina_width',
					'type'  => 'number',
				),

				array(
					'name'  => esc_html__( 'Logo height', TIELABS_TEXTDOMAIN ),
					'id'    => 'logo_retina_height',
					'type'  => 'number',
				),

				array(
					'content' => '</div>',
					'type'    => 'html',
				),

				array(
					'name'  => esc_html__( 'Logo Text', TIELABS_TEXTDOMAIN ),
					'id'    => 'logo_text',
					'type'  => 'text',
					'hint'  => esc_html__( 'In the Logo Image type this will be used as the ALT text.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name' => esc_html__( 'Logo Margin Top', TIELABS_TEXTDOMAIN ),
					'id'   => 'logo_margin',
					'type' => 'number',
					'hint' => esc_html__( 'Leave it empty to use the default value.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name' => esc_html__( 'Logo Margin Bottom', TIELABS_TEXTDOMAIN ),
					'id'   => 'logo_margin_bottom',
					'type' => 'number',
					'hint' => esc_html__( 'Leave it empty to use the default value.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name'  => esc_html__( 'Custom Logo URL', TIELABS_TEXTDOMAIN ),
					'id'    => 'logo_url',
					'type'  => 'text',
					'hint'  => esc_html__( 'Leave it empty to use the Site URL.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'content' => '<div id="mobile-logo-image-settings" class="logo_setting-options">',
					'type'    => 'html',
				),

				array(
					'title' => esc_html__( 'Mobile Logo', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'  => esc_html__( 'Logo Image', TIELABS_TEXTDOMAIN ),
					'id'    => 'mobile_logo',
					'type'  => 'upload',
				),

				array(
					'name'  => esc_html__( 'Logo Image (Retina Version @2x)', TIELABS_TEXTDOMAIN ),
					'id'    => 'mobile_logo_retina',
					'type'  => 'upload',
					'hint'	=> esc_html__( 'Please choose an image file for the retina version of the logo. It should be 2x the size of main logo.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name'  => esc_html__( 'Logo Inverted Image', TIELABS_TEXTDOMAIN ),
					'id'    => 'mobile_logo_inverted',
					'type'  => 'upload',
					'hint'	=> '<strong>'. esc_html__( 'Used if users are allowed to switch between Light and Dark Modes.', TIELABS_TEXTDOMAIN ) .'</strong>',
				),

				array(
					'name'  => esc_html__( 'Logo Inverted Image (Retina Version @2x)', TIELABS_TEXTDOMAIN ),
					'id'    => 'mobile_logo_inverted_retina',
					'type'  => 'upload',
					'hint'	=> '<strong>'. esc_html__( 'Used if users are allowed to switch between Light and Dark Modes.', TIELABS_TEXTDOMAIN ) .'</strong><br />'. esc_html__( 'Please choose an image file for the retina version of the logo. It should be 2x the size of main logo.', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name'  => esc_html__( 'Logo width', TIELABS_TEXTDOMAIN ),
					'id'    => 'mobile_logo_retina_width',
					'type'  => 'number',
				),

				array(
					'name'  => esc_html__( 'Logo height', TIELABS_TEXTDOMAIN ),
					'id'    => 'mobile_logo_retina_height',
					'type'  => 'number',
				),

				array(
					'content' => '</div>',
					'type'    => 'html',
				),

				array(
					'content' => '</div>',
					'type'    => 'html',
				),
			);


			if( ! empty( $current_settings ) && is_array( $current_settings ) ){
				$settings = array_merge( $current_settings, $settings );
			}

			return apply_filters( 'TieLabs/Settings/Category/logo/defaults', $settings );
		}


		/**
		 * Menu Settings
		 */
		function menu_settings( $current_settings, $category_id ){

			$settings = array(
				array(
					'title' => esc_html__( 'Custom Menus', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name'    => esc_html__( 'Main Navigation Menu', TIELABS_TEXTDOMAIN ),
					'id'      => 'cat_menu',
					'type'    => 'select',
					'options' => TIELABS_ADMIN_HELPER::get_menus( true ),
				),

				array(
					'name'    => esc_html__( '​Secondary Navigation Menu', TIELABS_TEXTDOMAIN ),
					'id'      => '​secondary_cat_menu',
					'type'    => 'select',
					'options' => TIELABS_ADMIN_HELPER::get_menus( true ),
				),
			);

			if( ! empty( $current_settings ) && is_array( $current_settings ) ){
				$settings = array_merge( $current_settings, $settings );
			}

			return apply_filters( 'TieLabs/Settings/Category/menu/defaults', $settings );
		}



		/**
		 * Sidebar Settings
		 */
		function sidebar_settings( $current_settings, $category_id ){

			$settings = array(

				array(
					'title' => esc_html__( 'Category Sidebar', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'id'      => 'cat_sidebar_pos',
					'type'    => 'visual',
					'columns' => 5,
					'options' => array(
						''           => array( esc_html__( 'Default',         TIELABS_TEXTDOMAIN ) => 'default.png' ),
						'right'	     => array( esc_html__( 'Sidebar Right',   TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-right.png' ),
						'left'	     => array( esc_html__( 'Sidebar Left',    TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-left.png' ),
						'full'	     => array( esc_html__( 'Without Sidebar', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-full-width.png' ),
						'one-column' => array( esc_html__( 'One Column',      TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-one-column.png' ),
				)),

				array(
					'name'   => esc_html__( 'Sticky Sidebar', TIELABS_TEXTDOMAIN ),
					'id'     => 'cat_sticky_sidebar',
					'type'   => 'select',
					'options' => array(
						''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
						'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
						'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
				)),

				array(
					'name'    => esc_html__( 'Custom Sidebar', TIELABS_TEXTDOMAIN ),
					'id'      => 'cat_sidebar',
					'type'    => 'select',
					'options' => TIELABS_ADMIN_HELPER::get_sidebars(),
				),

				//--
				array(
					'title' => esc_html__( 'Global Sidebar Settings for Posts in this Category', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'id'      => 'cat_posts_sidebar_pos',
					'type'    => 'visual',
					'columns' => 5,
					'options' => array(
						''           => array( esc_html__( 'Default',         TIELABS_TEXTDOMAIN ) => 'default.png' ),
						'right'	     => array( esc_html__( 'Sidebar Right',   TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-right.png' ),
						'left'	     => array( esc_html__( 'Sidebar Left',    TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-left.png' ),
						'full'	     => array( esc_html__( 'Without Sidebar', TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-full-width.png' ),
						'one-column' => array( esc_html__( 'One Column',      TIELABS_TEXTDOMAIN ) => 'sidebars/sidebar-one-column.png' ),
				)),

				array(
					'name'   => esc_html__( 'Sticky Sidebar', TIELABS_TEXTDOMAIN ),
					'id'     => 'cat_posts_sticky_sidebar',
					'type'   => 'select',
					'options' => array(
						''    => esc_html__( 'Default', TIELABS_TEXTDOMAIN ),
						'yes' => esc_html__( 'Yes',     TIELABS_TEXTDOMAIN ),
						'no'  => esc_html__( 'No',      TIELABS_TEXTDOMAIN ),
				)),

				array(
					'name'    => esc_html__( 'Custom Sidebar', TIELABS_TEXTDOMAIN ),
					'id'      => 'cat_posts_sidebar',
					'type'    => 'select',
					'options' => TIELABS_ADMIN_HELPER::get_sidebars(),
				),
			);


			if( ! empty( $current_settings ) && is_array( $current_settings ) ){
				$settings = array_merge( $current_settings, $settings );
			}

			return apply_filters( 'TieLabs/Settings/Category/sidebar/defaults', $settings );
		}



		/**
		 * Styles Settings
		 */
		function styles_settings( $current_settings, $category_id ){

			$settings = array(

				array(
					'title' => esc_html__( 'Category Style', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'name' => esc_html__( 'Primary Color', TIELABS_TEXTDOMAIN ),
					'id'   => 'cat_color',
					'type' => 'color',
				),

				array(
					'title' =>	esc_html__( 'Background', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'text'  => esc_html__( 'Bordered Layout supports plain background color only.', TIELABS_TEXTDOMAIN ),
					'type'  => 'message',
				),

				array(
					'name'  => esc_html__( 'Background Color', TIELABS_TEXTDOMAIN ),
					'id'    => 'background_color',
					'type'  => 'color',
				),

				array(
					'name'  => esc_html__( 'Background Color 2', TIELABS_TEXTDOMAIN ),
					'id'    => 'background_color_2',
					'type'  => 'color',
				),

				array(
					'name'   => esc_html__( 'Background Image type', TIELABS_TEXTDOMAIN ),
					'id'     => 'background_type',
					'type'   => 'radio',
					'toggle' => array(
						''        => '',
						'pattern' => '#background_pattern-item',
						'image'   => '#background_image-item',),
					'options' => array(
						''        => esc_html__( 'None',    TIELABS_TEXTDOMAIN ),
						'pattern' => esc_html__( 'Pattern', TIELABS_TEXTDOMAIN ),
						'image'   => esc_html__( 'Image',   TIELABS_TEXTDOMAIN ),
				)),

				array(
					'name'    => esc_html__( 'Background Pattern', TIELABS_TEXTDOMAIN ),
					'id'      => 'background_pattern',
					'type'    => 'visual',
					'class'   => 'background_type',
					'options' => TIELABS_ADMIN_HELPER::get_patterns(),
				),

				array(
					'name'  => esc_html__( 'Background Image', TIELABS_TEXTDOMAIN ),
					'id'    => 'background_image',
					'class' => 'background_type',
					'type'  => 'background',
				),

				array(
					'type'  => 'header',
					'title' => esc_html__( 'Background Settings', TIELABS_TEXTDOMAIN ),
				),

				array(
					'name' => esc_html__( 'Dots overlay layer', TIELABS_TEXTDOMAIN ),
					'id'   => 'background_dots',
					'type' => 'checkbox',
				),

				array(
					'name'   => esc_html__( 'Background dimmer', TIELABS_TEXTDOMAIN ),
					'id'     => 'background_dimmer',
					'toggle' => '#background_dimmer_value-item, #background_dimmer_color-item',
					'type'   => 'checkbox',
				),

				array(
					'name' => esc_html__( 'Background dimmer', TIELABS_TEXTDOMAIN ),
					'id'   => 'background_dimmer_value',
					'hint' => esc_html__( 'Value between 0 and 100 to dim background image. 0 - no dim, 100 - maximum dim.', TIELABS_TEXTDOMAIN ),
					'type' => 'number',
				),

				array(
					'name'    => esc_html__( 'Background dimmer color', TIELABS_TEXTDOMAIN ),
					'id'      => 'background_dimmer_color',
					'type'    => 'radio',
					'options'	=> array(
						'black' => esc_html__( 'Black', TIELABS_TEXTDOMAIN ),
						'white' => esc_html__( 'White', TIELABS_TEXTDOMAIN ),
					)),

				array(
					'title' =>	esc_html__( 'Custom CSS', TIELABS_TEXTDOMAIN ),
					'type'  => 'header',
				),

				array(
					'text' => esc_html__( 'Paste your CSS code, do not include any tags or HTML in the field. Any custom CSS entered here will override the theme CSS. In some cases, the !important tag may be needed.', TIELABS_TEXTDOMAIN ),
					'type' => 'message',
				),

				array(
					'name'  => esc_html__( 'Custom CSS', TIELABS_TEXTDOMAIN ),
					'id'    => 'cat_custom_css',
					'class' => 'tie-css',
					'type'  => 'textarea',
					'hint'  => sprintf( esc_html__( 'Use %s and it will be replaced with the primary color.', TIELABS_TEXTDOMAIN ), '<code>primary-color</code>' ),
				),
			);


			if( ! empty( $current_settings ) && is_array( $current_settings ) ){
				$settings = array_merge( $current_settings, $settings );
			}

			return apply_filters( 'TieLabs/Settings/Category/styles/defaults', $settings );
		}

	}

	new TIELABS_SETTINGS_CATEGORY();
}
