<?php
/**
 * Block / Archives Layout - Content
 *
 * This template can be overridden by copying it to your-child-theme/templates/loops/loop-content.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author   TieLabs
 * @version  7.5.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly
?>

<li <?php tie_post_class( 'post-item' ); ?>>
	<?php
		// Get the Post Meta info
		if( ! empty( $block['post_meta'] ) ) {
			tie_the_post_meta( array( 'trending' => true ) );
		}
	?>
	
	<?php do_action( 'TieLabs/loop/before_title', 'content', $block ); ?>
	<h2 class="post-title"><a href="<?php the_permalink(); ?>"><?php tie_the_title( $block['title_length'] ); ?></a></h2>
	<?php do_action( 'TieLabs/loop/after_title', 'content', $block ); ?>

	<div class="entry"><?php the_content( esc_html__( 'Read More &raquo;', TIELABS_TEXTDOMAIN ) ) ?></div>
</li>
