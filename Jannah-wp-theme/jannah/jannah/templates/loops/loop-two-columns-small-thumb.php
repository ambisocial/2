<?php
/**
 * Block Layout - Two Columns Small Thumb
 *
 * This template can be overridden by copying it to your-child-theme/templates/loops/loop-two-columns-small-thumb.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author   TieLabs
 * @version  7.5.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

	// minimize the CSS modifications
	if( $count == 1 ){
		echo '<li></li>';
	}
	// Set custom class for the post without thumb
	$no_thumb = ( ! has_post_thumbnail() || ! empty( $block['thumb_small'] ) ) ? 'no-small-thumbs' : '';
?>
<li <?php tie_post_class( 'post-item '.$no_thumb ); ?>>
	<?php
		// Get the post thumbnail
		if ( has_post_thumbnail() && empty( $block['thumb_small'] ) ){
			$thumbnail_size = apply_filters( 'TieLabs/loop_thumbnail_size', TIELABS_SMALL_IMAGE, 'two-columns-small-thumb', $count );
			tie_post_thumbnail( $thumbnail_size, 'small', false, true, $block['media_overlay']);
		}
	?>
	<div class="post-details">
		
		<?php do_action( 'TieLabs/loop/before_title', 'two-columns-small-thumb', $block ); ?>
		<h2 class="post-title"><a href="<?php the_permalink(); ?>"><?php tie_the_title( $block['title_length'] ); ?></a></h2>
		<?php do_action( 'TieLabs/loop/after_title', 'two-columns-small-thumb', $block ); ?>

		<?php
			// Get the Post Meta info
			if( ! empty( $block['post_meta'] ) ) {
				tie_the_post_meta( array( 'trending' => true, 'author' => false, 'comments' => false, 'views' => false, 'review' => true ) );
			}
		?>
	</div><!-- .post-details /-->
</li>
