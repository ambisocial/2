<?php
/**
 * Remove posts duplicados por título (mantém o melhor: mais palavras, thumb, mais antigo).
 *
 * Uso: ESTRATO_DRY_RUN=1 wp eval-file dedupe-posts-by-title.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dry_run = '1' === getenv( 'ESTRATO_DRY_RUN' );

/**
 * @param int $post_id
 * @return int
 */
function estrato_dedupe_score( $post_id ) {
	$score = 0;
	if ( has_post_thumbnail( $post_id ) ) {
		$score += 1000;
	}
	if ( function_exists( 'estrato_content_post_word_count' ) ) {
		$score += min( 500, estrato_content_post_word_count( $post_id ) );
	}
	$post = get_post( $post_id );
	if ( $post ) {
		$score += max( 0, 365 - (int) floor( ( time() - strtotime( $post->post_date ) ) / DAY_IN_SECONDS ) );
	}
	if ( get_post_meta( $post_id, '_estrato_rss_guid', true ) ) {
		$score += 50;
	}
	return $score;
}

global $wpdb;

$rows = $wpdb->get_results(
	"SELECT post_title, COUNT(*) AS c FROM {$wpdb->posts}
	 WHERE post_type='post' AND post_status='publish'
	 GROUP BY post_title HAVING c > 1
	 ORDER BY c DESC",
	ARRAY_A
);

$groups  = 0;
$removed = 0;

foreach ( $rows as $row ) {
	$title = $row['post_title'];
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'title'          => $title,
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);
	if ( count( $posts ) < 2 ) {
		continue;
	}
	++$groups;

	usort(
		$posts,
		function ( $a, $b ) {
			return estrato_dedupe_score( $b->ID ) <=> estrato_dedupe_score( $a->ID );
		}
	);

	$keep = array_shift( $posts );
	foreach ( $posts as $dup ) {
		if ( $dry_run ) {
			WP_CLI::log( "[dry-run] trash #{$dup->ID} dup of #{$keep->ID}: {$title}" );
		} else {
			wp_trash_post( (int) $dup->ID );
		}
		++$removed;
	}
}

// GUID duplicados (segunda passagem).
$guid_rows = $wpdb->get_results(
	"SELECT meta_value AS guid, COUNT(*) AS c FROM {$wpdb->postmeta}
	 WHERE meta_key='_estrato_rss_guid' AND meta_value!=''
	 GROUP BY meta_value HAVING c>1 LIMIT 100",
	ARRAY_A
);

$guid_removed = 0;
foreach ( $guid_rows as $row ) {
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID
			 WHERE pm.meta_key='_estrato_rss_guid' AND pm.meta_value=%s
			 AND p.post_status IN ('publish','draft','pending')
			 ORDER BY p.post_date ASC",
			$row['guid']
		)
	);
	if ( count( $ids ) < 2 ) {
		continue;
	}
	array_shift( $ids );
	foreach ( $ids as $dup_id ) {
		if ( $dry_run ) {
			WP_CLI::log( "[dry-run] trash guid dup #$dup_id" );
		} else {
			wp_trash_post( (int) $dup_id );
		}
		++$guid_removed;
	}
}

$mode = $dry_run ? 'DRY-RUN' : 'APPLIED';
WP_CLI::success(
	wp_json_encode(
		array(
			'mode'          => $mode,
			'title_groups'  => $groups,
			'title_removed' => $removed,
			'guid_removed'  => $guid_removed,
		)
	)
);
