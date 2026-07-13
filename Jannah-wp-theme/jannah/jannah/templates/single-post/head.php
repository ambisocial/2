<?php
/**
 * Post Head Area
 *
 * This template can be overridden by copying it to your-child-theme/templates/single-post/head.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author   TieLabs
 * @version  6.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly


/**
 * TieLabs/before_post_head hook.
 *
 */
do_action( 'TieLabs/before_post_head' ); ?>

<header class="entry-header-outer">

	<?php do_action( 'TieLabs/before_entry_head' ); ?>

	<div class="entry-header">

		<?php

			// Categories
			if( ( tie_get_option( 'post_cats' ) && ! tie_get_postdata( 'tie_hide_categories' ) ) || tie_get_postdata( 'tie_hide_categories' ) == 'no' ){
				tie_the_category( '<span class="post-cat-wrap">', '</span>', false );
			}

			// Trending
			tie_the_trending_icon( '', '<div class="post-is-trending">', ' '. esc_html__( 'Trending', TIELABS_TEXTDOMAIN ) .'</div>');

		?>

		<h1 class="post-title entry-title">
			<?php

				$custom_title = apply_filters( 'TieLabs/Post/custom_title', tie_get_postdata( 'tie_post_custom_title' ) );

				echo ! empty( $custom_title ) ? $custom_title : the_title();
			?>
		</h1>

		<?php

		do_action( 'TieLabs/after_post_title' );

		$post_sub_title = apply_filters( 'TieLabs/Post/subtitle', tie_get_postdata( 'tie_post_sub_title' ) );

		if( ! empty( $post_sub_title ) ) { ?>
			<h2 class="entry-sub-title"><?php echo $post_sub_title ?></h2>
			<?php
		}

		do_action( 'TieLabs/after_post_sub_title' );

		// Post info section in the single post page
		tie_the_post_meta_single();

		?>
	</div><!-- .entry-header /-->

	<?php
		$post_layout = tie_get_object_option( 'post_layout', 'cat_post_layout', 'tie_post_layout' );

		if( ! empty( $post_layout ) && ( $post_layout == 4 || $post_layout == 5 || $post_layout == 8 ) ){ ?>

			<a id="go-to-content" href="#go-to-content"><span class="tie-icon-angle-down"></span></a>
			<?php
		}
	?>

	<?php do_action( 'TieLabs/after_entry_head' ); ?>

</header><!-- .entry-header-outer /-->

<?php
	/**
	 * TieLabs/after_post_head hook.
	 *
	 */
	do_action( 'TieLabs/after_post_head' );

