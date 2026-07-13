<?php
/**
 * Sprint 7 — página /colunistas/ com grid de autores E-E-A-T.
 *
 * wp eval-file setup-s7-colunistas-page.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$users = get_users(
	array(
		'who'    => 'authors',
		'number' => 50,
		'orderby' => 'display_name',
		'order'   => 'ASC',
	)
);

if ( empty( $users ) ) {
	$users = get_users(
		array(
			'role__in' => array( 'author', 'editor', 'administrator' ),
			'number'   => 50,
			'orderby'  => 'display_name',
			'order'    => 'ASC',
		)
	);
}

$cards = '';
foreach ( $users as $user ) {
	$count = count_user_posts( (int) $user->ID, 'post', true );
	if ( $count < 1 ) {
		continue;
	}
	$bio = get_user_meta( $user->ID, 'description', true );
	$job = get_user_meta( $user->ID, 'estrato_job_title', true );
	$link = get_author_posts_url( $user->ID );
	$cards .= '<article class="estrato-colunista-card">';
	$cards .= '<a href="' . esc_url( $link ) . '">' . get_avatar( $user->ID, 96 ) . '</a>';
	$cards .= '<h2><a href="' . esc_url( $link ) . '">' . esc_html( $user->display_name ) . '</a></h2>';
	if ( $job ) {
		$cards .= '<p class="estrato-colunista-job">' . esc_html( $job ) . '</p>';
	}
	if ( $bio ) {
		$cards .= '<p class="estrato-colunista-bio">' . esc_html( wp_trim_words( $bio, 28 ) ) . '</p>';
	}
	$cards .= '<p class="estrato-colunista-meta"><a href="' . esc_url( $link ) . '">' . (int) $count . ' publicações</a></p>';
	$cards .= '</article>';
}

if ( '' === $cards ) {
	WP_CLI::warning( 'Nenhum autor com posts publicados.' );
	return;
}

$html = '<style>
.estrato-colunistas{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem;margin:1.5rem 0}
.estrato-colunista-card{border:1px solid #e5e5e5;border-radius:8px;padding:1rem}
.estrato-colunista-card img{border-radius:50%;display:block;margin-bottom:.75rem}
.estrato-colunista-card h2{font-size:1.1rem;margin:0 0 .35rem}
.estrato-colunista-job{font-weight:600;margin:0 0 .5rem;opacity:.85}
.estrato-colunista-bio{margin:0 0 .5rem;line-height:1.45}
.estrato-colunista-meta{margin:0;font-size:.9rem}
</style>
<div class="estrato-colunistas">' . $cards . '</div>';

$page = get_page_by_path( 'colunistas' );
if ( $page ) {
	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => $html,
			'post_status'  => 'publish',
		)
	);
	$page_id = (int) $page->ID;
} else {
	$page_id = (int) wp_insert_post(
		array(
			'post_title'   => 'Colunistas',
			'post_name'    => 'colunistas',
			'post_content' => $html,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		)
	);
}

WP_CLI::success(
	wp_json_encode(
		array(
			'page_id'  => $page_id,
			'url'      => get_permalink( $page_id ),
			'authors'  => substr_count( $cards, 'estrato-colunista-card' ),
		),
		JSON_UNESCAPED_UNICODE
	)
);
