<?php
/**
 * The template for displaying Web Stories pages
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

get_header(); ?>

	<div <?php tie_content_column_attr(); ?>>


		<header id="webstories-title-section" class="entry-header-outer container-wrapper archive-title-wrapper">
			<?php

				do_action( 'TieLabs/before_archive_title' );

				the_archive_title( '<h1 class="page-title">', '</h1>' );

				do_action( 'TieLabs/after_archive_title' );

			?>
		</header><!-- .entry-header-outer /-->

		<?php

			$args = array(
				'style'               => tie_get_option( 'web_stories_layout', 'grid' ),
				'web_stories_columns' => tie_get_option( 'web_stories_columns', 2 ),
				'web_stories_number'  => tie_get_option( 'web_stories_number', 10 ),
				'web_stories_author'  => tie_get_option( 'web_stories_author' ),
				'web_stories_date'    => tie_get_option( 'web_stories_date' ),
			);

			tie_get_web_stories( $args );

			// Pagination
			TIELABS_PAGINATION::show( array( 'type' => tie_get_option( 'web_stories_pagination' ) ) );
		?>

	</div><!-- .main-content /-->

<?php get_sidebar(); ?>
<?php get_footer();
