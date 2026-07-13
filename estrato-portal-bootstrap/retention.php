<?php
/**
 * Retenção — autores, newsletter, dark mode, mais lidas (Sprint 6).
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESTRATO_NEWSLETTER_OPTION', 'estrato_newsletter_signups' );
define( 'ESTRATO_VIEW_META', 'estrato_views' );

/**
 * @param string $email
 * @return bool
 */
function estrato_retention_newsletter_is_confirmed( $email ) {
	$list = get_option( ESTRATO_NEWSLETTER_OPTION, array() );
	if ( ! is_array( $list ) ) {
		return false;
	}
	foreach ( $list as $row ) {
		if ( ! is_array( $row ) || empty( $row['email'] ) ) {
			continue;
		}
		if ( strtolower( $row['email'] ) === strtolower( $email ) && 'confirmed' === ( $row['status'] ?? '' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Confirma inscrição via token (?estrato_nl_confirm=...).
 */
function estrato_retention_newsletter_handle_confirm() {
	if ( empty( $_GET['estrato_nl_confirm'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$token = sanitize_text_field( wp_unslash( $_GET['estrato_nl_confirm'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$list  = get_option( ESTRATO_NEWSLETTER_OPTION, array() );
	if ( ! is_array( $list ) ) {
		return;
	}
	$changed = false;
	foreach ( $list as &$row ) {
		if ( ! is_array( $row ) || empty( $row['token'] ) ) {
			continue;
		}
		if ( ! hash_equals( (string) $row['token'], $token ) ) {
			continue;
		}
		$row['status']    = 'confirmed';
		$row['confirmed'] = current_time( 'mysql' );
		$changed          = true;
		break;
	}
	unset( $row );
	if ( $changed ) {
		update_option( ESTRATO_NEWSLETTER_OPTION, $list, false );
		foreach ( $list as $row ) {
			if ( is_array( $row ) && hash_equals( (string) $row['token'], $token ) && 'confirmed' === ( $row['status'] ?? '' ) ) {
				if ( function_exists( 'estrato_newsletter_esp_subscribe' ) && ! empty( $row['email'] ) ) {
					estrato_newsletter_esp_subscribe( $row['email'] );
				}
				break;
			}
		}
		set_transient( 'estrato_nl_confirmed_' . get_current_user_id(), 1, MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'estrato_nl', 'confirmed', home_url( '/' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'estrato_retention_newsletter_handle_confirm', 1 );

/**
 * @param string $email
 * @param string $token
 */
function estrato_retention_newsletter_send_confirm( $email, $token ) {
	$blog  = get_bloginfo( 'name' );
	$link  = add_query_arg( 'estrato_nl_confirm', rawurlencode( $token ), home_url( '/' ) );
	$body  = "Olá,\n\nConfirme sua inscrição na newsletter {$blog}:\n{$link}\n\nSe não foi você, ignore este e-mail.\n";
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	wp_mail( $email, "Confirme sua newsletter — {$blog}", $body, $headers );
}

/**
 * Newsletter com captura real (double opt-in).
 *
 * @return string
 */
function estrato_retention_newsletter_shortcode() {
	$msg = '';
	if ( isset( $_GET['estrato_nl'] ) && 'confirmed' === $_GET['estrato_nl'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = '<p class="estrato-nl-success">Inscrição confirmada. Obrigado!</p>';
	}
	if ( isset( $_POST['estrato_nl_email'] ) && isset( $_POST['estrato_nl_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['estrato_nl_nonce'] ) ), 'estrato_newsletter' ) ) {
			$email = sanitize_email( wp_unslash( $_POST['estrato_nl_email'] ) );
			if ( is_email( $email ) ) {
				if ( estrato_retention_newsletter_is_confirmed( $email ) ) {
					$msg = '<p class="estrato-nl-success">Este e-mail já está inscrito.</p>';
				} else {
					$list  = get_option( ESTRATO_NEWSLETTER_OPTION, array() );
					$list  = is_array( $list ) ? $list : array();
					$token = wp_generate_password( 32, false );
					$found = false;
					foreach ( $list as &$row ) {
						if ( is_array( $row ) && strtolower( $row['email'] ?? '' ) === strtolower( $email ) ) {
							$row['token']  = $token;
							$row['status'] = 'pending';
							$row['signed'] = current_time( 'mysql' );
							$found         = true;
							break;
						}
					}
					unset( $row );
					if ( ! $found ) {
						$list[] = array(
							'email'  => $email,
							'status' => 'pending',
							'token'  => $token,
							'signed' => current_time( 'mysql' ),
						);
					}
					update_option( ESTRATO_NEWSLETTER_OPTION, $list, false );
					estrato_retention_newsletter_send_confirm( $email, $token );
					$msg = '<p class="estrato-nl-success">Enviamos um link de confirmação para seu e-mail.</p>';
				}
			}
		}
	}

	$tagline = get_bloginfo( 'description' );
	$html    = '<section class="estrato-newsletter" aria-label="Newsletter">';
	$html   .= '<h2 class="estrato-display">Newsletter ' . esc_html( get_bloginfo( 'name' ) ) . '</h2>';
	$html   .= '<p>' . esc_html( $tagline ?: 'Receba os destaques no seu e-mail.' ) . '</p>';
	$html   .= $msg;
	$html   .= '<form method="post" class="estrato-newsletter__form">';
	$html   .= wp_nonce_field( 'estrato_newsletter', 'estrato_nl_nonce', true, false );
	$html   .= '<label class="screen-reader-text" for="estrato-nl-email">E-mail</label>';
	$html   .= '<input id="estrato-nl-email" type="email" name="estrato_nl_email" placeholder="seu@email.com" required />';
	$html   .= '<button type="submit">Assinar</button></form></section>';
	return $html;
}
add_shortcode( 'estrato_newsletter', 'estrato_retention_newsletter_shortcode' );

/**
 * Incrementa contagem de views (cookie 12h por post).
 */
function estrato_retention_track_post_view() {
	if ( ! is_singular( 'post' ) || is_preview() ) {
		return;
	}
	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}
	$cookie = 'estrato_v_' . $post_id;
	if ( isset( $_COOKIE[ $cookie ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$views = (int) get_post_meta( $post_id, ESTRATO_VIEW_META, true );
	update_post_meta( $post_id, ESTRATO_VIEW_META, $views + 1 );
	setcookie( $cookie, '1', time() + 12 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
}
add_action( 'template_redirect', 'estrato_retention_track_post_view', 5 );

/**
 * Posts em alta por views reais (fallback linkdex / data).
 *
 * @param int    $count
 * @param int    $days
 * @param string $category_slug
 * @return array<int, WP_Post>
 */
function estrato_retention_get_trending_posts( $count = 7, $days = 7, $category_slug = '' ) {
	$count = max( 1, (int) $count );
	$days  = max( 1, (int) $days );
	$args  = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $count,
		'meta_key'       => ESTRATO_VIEW_META,
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'date_query'     => array( array( 'after' => $days . ' days ago' ) ),
	);
	if ( $category_slug ) {
		$term = get_term_by( 'slug', sanitize_title( $category_slug ), 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$args['cat'] = (int) $term->term_id;
		}
	}
	$posts = get_posts( $args );
	if ( $posts ) {
		return $posts;
	}
	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => $count,
		'orderby'        => 'meta_value_num',
		'meta_key'       => '_yoast_wpseo_linkdex',
		'order'          => 'DESC',
		'date_query'     => array( array( 'after' => $days . ' days ago' ) ),
	);
	if ( ! empty( $args['cat'] ) ) {
		// cat already set if category was valid.
	} elseif ( $category_slug ) {
		unset( $args['cat'] );
	}
	if ( $category_slug ) {
		$term = get_term_by( 'slug', sanitize_title( $category_slug ), 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			$args['cat'] = (int) $term->term_id;
		}
	}
	$posts = get_posts( $args );
	if ( $posts ) {
		return $posts;
	}
	return get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
}

/**
 * Página de autor enriquecida.
 *
 * @param string $content
 * @return string
 */
function estrato_retention_author_archive( $content ) {
	if ( ! is_author() ) {
		return $content;
	}
	$author = get_queried_object();
	if ( ! $author ) {
		return $content;
	}
	$bio  = get_the_author_meta( 'description', $author->ID );
	$job  = get_the_author_meta( 'estrato_job_title', $author->ID );
	$html = '<div class="estrato-author-page">';
	$html .= get_avatar( $author->ID, 96, '', '', array( 'class' => 'estrato-author-page__avatar' ) );
	$html .= '<h1 class="estrato-display">' . esc_html( $author->display_name ) . '</h1>';
	if ( $job ) {
		$html .= '<p class="estrato-author-page__job">' . esc_html( $job ) . '</p>';
	}
	if ( $bio ) {
		$html .= '<p class="estrato-author-page__bio">' . esc_html( $bio ) . '</p>';
	}
	$posts = get_posts(
		array(
			'author'         => $author->ID,
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
		)
	);
	if ( $posts ) {
		$html .= '<h2>Últimas matérias</h2><ul>';
		foreach ( $posts as $post ) {
			$html .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
		}
		$html .= '</ul>';
	}
	$html .= '</div>';
	return $html . $content;
}
add_filter( 'the_content', 'estrato_retention_author_archive', 6 );

/**
 * Dark mode toggle.
 */
function estrato_retention_dark_mode_assets() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<style id="estrato-dark-mode">
	[data-estrato-theme="dark"]{
		--estrato-bg:#121212;--estrato-ink:#f2f2f2;--estrato-muted:#a8a8a8;--estrato-line:#2e2e2e
	}
	.estrato-theme-toggle{
		position:fixed;bottom:1rem;right:1rem;z-index:10060;border:1px solid var(--estrato-line);
		background:var(--estrato-bg);color:var(--estrato-ink);padding:.4rem .75rem;border-radius:var(--estrato-radius,4px);
		font-size:12px;cursor:pointer
	}
	</style>
	<button type="button" class="estrato-theme-toggle" id="estrato-theme-toggle" aria-label="Alternar tema">Modo escuro</button>
	<script>
	(function(){
		var btn=document.getElementById('estrato-theme-toggle');
		if(!btn)return;
		var root=document.documentElement;
		var stored=localStorage.getItem('estrato-theme');
		if(stored==='dark'||(!stored&&window.matchMedia('(prefers-color-scheme:dark)').matches)){
			root.setAttribute('data-estrato-theme','dark');
			btn.textContent='Modo claro';
		}
		btn.addEventListener('click',function(){
			var dark=root.getAttribute('data-estrato-theme')==='dark';
			if(dark){root.removeAttribute('data-estrato-theme');localStorage.setItem('estrato-theme','light');btn.textContent='Modo escuro';}
			else{root.setAttribute('data-estrato-theme','dark');localStorage.setItem('estrato-theme','dark');btn.textContent='Modo claro';}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'estrato_retention_dark_mode_assets', 5 );

/**
 * Mais lidas v2 — views reais.
 *
 * @param array<string, mixed> $atts
 * @return string
 */
function estrato_retention_mais_lidas_v2( $atts ) {
	$atts = shortcode_atts(
		array(
			'count'    => 5,
			'category' => '',
			'days'     => 7,
		),
		$atts,
		'estrato_mais_lidas_v2'
	);
	$posts = estrato_retention_get_trending_posts( (int) $atts['count'], (int) $atts['days'], $atts['category'] );
	if ( ! $posts ) {
		return '';
	}
	$html = '<div class="estrato-mais-lidas-v2"><h2>Mais lidas</h2><ol>';
	$n    = 1;
	foreach ( $posts as $post ) {
		$views = (int) get_post_meta( $post->ID, ESTRATO_VIEW_META, true );
		$html .= '<li><span class="estrato-mais-lidas-v2__n">' . (int) $n . '</span> ';
		$html .= '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
		if ( $views > 0 ) {
			$html .= ' <span class="estrato-caption">(' . (int) $views . ' leituras)</span>';
		}
		$html .= '</li>';
		++$n;
	}
	$html .= '</ol></div>';
	return $html;
}
add_shortcode( 'estrato_mais_lidas_v2', 'estrato_retention_mais_lidas_v2' );
