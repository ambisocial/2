<?php
/**
 * Block / Archives Layout - Overlay
 *
 * This template can be overridden by copying it to your-child-theme/templates/loops/loop-overlay.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author   TieLabs
 * @version  7.5.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly


$thumbnail_size = apply_filters( 'TieLabs/loop_thumbnail_size', TIELABS_THEME_SLUG.'-image-post', 'overlay' );
$background = tie_get_option( 'lazy_load' ) ? 'data-lazy-bg="'. esc_attr( tie_thumb_src( $thumbnail_size ) ) .'"' : 'style="'. esc_attr( tie_thumb_src_bg( $thumbnail_size ) ) .'"';
?>

<div <?php tie_post_class( 'container-wrapper post-element' ); ?>>
	<div class="slide" <?php echo ( $background ); ?>>
		<a href="<?php the_permalink() ?>" class="all-over-thumb-link"><span class="screen-reader-text"><?php the_title(); ?></span></a>
		<div class="thumb-overlay">
			<?php
				// Get the Post Category
				if( $block['category_meta'] ){
					tie_the_category();
				}
			?>
			<div class="thumb-content">
				<?php
					if( $block['post_meta'] ){
						tie_the_post_meta( array( 'author' => false, 'comments' => false, 'views' => false ), '<div class="thumb-meta">', '</div>' );
					}
				?>

				<?php do_action( 'TieLabs/loop/before_title', 'overlay', $block ); ?>
				<h2 class="thumb-title"><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h2>
				<?php do_action( 'TieLabs/loop/after_title', 'overlay', $block ); ?>

				<?php if( $block['excerpt'] ): ?>
					<div class="thumb-desc">
						<?php tie_the_excerpt( $block['excerpt_length'] ) ?>
					</div><!-- .thumb-desc -->
				<?php endif; ?>
			</div> <!-- .thumb-content /-->
		</div><!-- .thumb-overlay /-->
	</div><!-- .slide /-->
</div><!-- .container-wrapper /-->
