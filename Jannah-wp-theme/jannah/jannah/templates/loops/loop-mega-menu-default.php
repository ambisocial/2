<?php
/**
 * Mega Menu Default Layout
 *
 * This template can be overridden by copying it to your-child-theme/templates/loops/loop-mega-menu-default.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author   TieLabs
 * @version  5.4.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

// Set custom class for the post without thumb
$no_thumb = ! has_post_thumbnail() ? ' no-small-thumbs' : ''; ?>

<div <?php tie_post_class( 'mega-menu-post '.$no_thumb ) ?>>
	<?php
		if ( has_post_thumbnail() ){ ?>
			<div class="post-thumbnail">
				<a class="post-thumb" href="<?php the_permalink(); ?>">
					<?php
						tie_post_format_icon( $media_icon );
						$thumbnail_size = apply_filters( 'TieLabs/loop_thumbnail_size', $thumbnail, 'mega-menu-default' );
						the_post_thumbnail( $thumbnail_size );
					?>
				</a>
			</div><!-- .post-thumbnail /-->
			<?php
		}
	?>
	<div class="post-details">
		<h3 class="post-box-title">
			<a class="mega-menu-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
		<?php tie_the_post_meta( array( 'trending' => true, 'author' => false, 'comments' => false, 'views' => false ) ); ?>
	</div>
</div>
