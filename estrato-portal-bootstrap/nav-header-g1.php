<?php
/**
 * Header estilo G1 (g1.globo.com) — barra rede, faixa principal, navegação horizontal.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cor e rótulo do header principal (home = vermelho G1; seção = branding da editoria).
 *
 * @return array{bg:string,fg:string,label:string,section:bool}
 */
function estrato_g1_header_context() {
	$defaults = array(
		'bg'      => '#C4170C',
		'fg'      => '#ffffff',
		'label'   => '',
		'section' => false,
	);

	if ( function_exists( 'estrato_portal_get_archive_term_id' )
		&& function_exists( 'estrato_portal_resolve_branding_for_term' ) ) {
		$term_id = estrato_portal_get_archive_term_id();
		if ( $term_id ) {
			$branding = estrato_portal_resolve_branding_for_term( $term_id );
			if ( ! empty( $branding['primary_color'] ) ) {
				$defaults['bg']      = (string) $branding['primary_color'];
				$defaults['fg']      = '#ffffff';
				$defaults['label']   = (string) ( $branding['brand_name'] ?? '' );
				$defaults['section'] = true;
				if ( empty( $defaults['label'] ) ) {
					$term = get_term( $term_id, 'category' );
					if ( $term && ! is_wp_error( $term ) ) {
						$defaults['label'] = $term->name;
					}
				}
				return $defaults;
			}
		}
	}

	$config = function_exists( 'estrato_portal_get_config' ) ? estrato_portal_get_config() : array();
	$primary = $config['branding']['primary_color'] ?? '#000000';
	if ( '#000000' === strtolower( $primary ) ) {
		return $defaults;
	}
	$defaults['bg'] = $primary;
	return $defaults;
}

/**
 * Renderiza o header G1.
 */
function estrato_g1_render_header() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$ctx       = estrato_g1_header_context();
	$current   = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '';
	$site_name = get_bloginfo( 'name' );
	$search    = home_url( '/' );
	$logo_id   = (int) get_theme_mod( 'custom_logo' );
	$logo_html = '';
	if ( $logo_id ) {
		$logo_html = wp_get_attachment_image(
			$logo_id,
			'medium',
			false,
			array(
				'class'    => 'estrato-g1-header__logo-img',
				'alt'      => $site_name,
				'decoding' => 'async',
			)
		);
	}

	$style_attr = sprintf(
		' style="--estrato-g1-bar-bg:%1$s;--estrato-g1-bar-fg:%2$s"',
		esc_attr( $ctx['bg'] ),
		esc_attr( $ctx['fg'] )
	);

	?>
	<header id="estrato-g1-header" class="estrato-g1-header<?php echo $ctx['section'] ? ' estrato-g1-header--section' : ' estrato-g1-header--home'; ?>" role="banner"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="estrato-g1-header__rede" aria-label="Rede Estrato">
			<div class="estrato-g1-header__inner">
				<span class="estrato-g1-header__rede-label">Rede Estrato</span>
				<ul class="estrato-g1-header__rede-list">
					<?php if ( function_exists( 'estrato_nav_network_catalog' ) ) : ?>
						<?php foreach ( estrato_nav_network_catalog() as $node ) : ?>
							<?php if ( $node['id'] === $current ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<li><a href="<?php echo esc_url( $node['url'] ); ?>"><?php echo esc_html( $node['name'] ); ?></a></li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</div>
		</div>

		<div class="estrato-g1-header__principal">
			<div class="estrato-g1-header__inner estrato-g1-header__principal-row">
				<button type="button" class="estrato-g1-header__menu-btn" id="estrato-g1-menu-btn" aria-controls="estrato-g1-nav" aria-expanded="false" aria-label="Abrir menu">
					<span class="estrato-g1-header__menu-icon" aria-hidden="true"></span>
				</button>
				<div class="estrato-g1-header__logo-wrap">
					<a class="estrato-g1-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( $site_name ); ?>">
						<?php if ( $logo_html ) : ?>
							<?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<span class="estrato-g1-header__logo-text"><?php echo esc_html( $site_name ); ?></span>
						<?php endif; ?>
					</a>
				</div>
				<div class="estrato-g1-header__actions">
					<button type="button" class="estrato-g1-header__search-btn" id="estrato-g1-search-btn" aria-controls="estrato-g1-search" aria-expanded="false" aria-label="Buscar">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					</button>
				</div>
			</div>
		</div>

		<?php if ( $ctx['label'] ) : ?>
			<div class="estrato-g1-header__editoria">
				<div class="estrato-g1-header__inner">
					<span class="estrato-g1-header__editoria-label"><?php echo esc_html( $ctx['label'] ); ?></span>
				</div>
			</div>
		<?php endif; ?>

		<nav id="estrato-g1-nav" class="estrato-g1-header__nav" aria-label="Editorias">
			<div class="estrato-g1-header__inner">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'estrato-g1-menu estrato-mega-nav',
						'container'      => false,
						'fallback_cb'    => false,
						'depth'          => 3,
					)
				);
				?>
			</div>
		</nav>

		<div id="estrato-g1-search" class="estrato-g1-header__search-panel" hidden>
			<div class="estrato-g1-header__inner">
				<form class="estrato-g1-header__search-form" role="search" method="get" action="<?php echo esc_url( $search ); ?>">
					<label class="screen-reader-text" for="estrato-g1-search-input">Buscar</label>
					<input id="estrato-g1-search-input" type="search" name="s" placeholder="Buscar notícias" autocomplete="off" />
					<button type="submit">Buscar</button>
				</form>
			</div>
		</div>
	</header>
	<?php
}
add_action( 'wp_body_open', 'estrato_g1_render_header', 5 );

/**
 * Oculta header legado PressGrid (substituído pelo G1).
 */
function estrato_g1_header_hide_pressgrid() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<style id="estrato-g1-header-hide-pressgrid">
	.pg-topbar,.pg-masthead,.pg-nav-wrap{display:none!important}
	.estrato-columns-ribbon{display:none!important}
	.pg-forex-bar,.pg-breaking-bar{
		background:#fff1e5!important;border-top:1px solid #e8d5c4;border-bottom:1px solid #e8d5c4;
		font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
		font-size:.8125rem;color:#333
	}
	.pg-forex-label,.pg-breaking-label{background:#C4170C!important;color:#fff!important;font-weight:700;padding:.35rem .75rem}
	.pg-forex-ticker a,.pg-forex-item{color:#333!important}
	</style>
	<?php
}
add_action( 'wp_head', 'estrato_g1_header_hide_pressgrid', 20 );

/**
 * CSS header G1.
 */
function estrato_g1_header_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<style id="estrato-g1-header-css">
	.estrato-g1-header{
		--estrato-g1-bar-bg:#C4170C;--estrato-g1-bar-fg:#fff;
		--estrato-g1-rede-bg:#1e1e1e;--estrato-g1-nav-bg:#fff;--estrato-g1-nav-fg:#1e1e1e;
		font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
		position:sticky;top:0;z-index:10050;background:#fff;box-shadow:0 1px 0 rgba(0,0,0,.06)
	}
	.estrato-g1-header__inner{max-width:1200px;margin:0 auto;padding:0 1rem}
	.estrato-g1-header__rede{background:var(--estrato-g1-rede-bg);color:#ccc;font-size:.75rem}
	.estrato-g1-header__rede .estrato-g1-header__inner{display:flex;align-items:center;gap:1rem;min-height:32px;overflow-x:auto}
	.estrato-g1-header__rede-label{font-weight:700;color:#fff;white-space:nowrap;text-transform:uppercase;letter-spacing:.04em}
	.estrato-g1-header__rede-list{display:flex;gap:.85rem;list-style:none;margin:0;padding:0;white-space:nowrap}
	.estrato-g1-header__rede-list a{color:#ccc;text-decoration:none}
	.estrato-g1-header__rede-list a:hover{color:#fff;text-decoration:underline}
	.estrato-g1-header__principal{background:var(--estrato-g1-bar-bg);color:var(--estrato-g1-bar-fg)}
	.estrato-g1-header__principal-row{display:grid;grid-template-columns:48px 1fr 48px;align-items:center;min-height:56px}
	.estrato-g1-header__menu-btn,.estrato-g1-header__search-btn{
		background:transparent;border:0;color:inherit;cursor:pointer;padding:.5rem;
		display:flex;align-items:center;justify-content:center;width:40px;height:40px
	}
	.estrato-g1-header__menu-icon{
		display:block;width:18px;height:2px;background:currentColor;position:relative
	}
	.estrato-g1-header__menu-icon::before,.estrato-g1-header__menu-icon::after{
		content:"";position:absolute;left:0;width:18px;height:2px;background:currentColor
	}
	.estrato-g1-header__menu-icon::before{top:-6px}
	.estrato-g1-header__menu-icon::after{top:6px}
	.estrato-g1-header__logo-wrap{display:flex;justify-content:center;align-items:center}
	.estrato-g1-header__logo{display:inline-flex;align-items:center;text-decoration:none;color:inherit}
	.estrato-g1-header__logo-img{max-height:36px;width:auto;filter:brightness(0) invert(1)}
	.estrato-g1-header__logo-text{font-size:1.75rem;font-weight:800;letter-spacing:-.03em;line-height:1}
	.estrato-g1-header__actions{display:flex;justify-content:flex-end}
	.estrato-g1-header__editoria{background:var(--estrato-g1-bar-bg);color:var(--estrato-g1-bar-fg);border-top:1px solid rgba(255,255,255,.15)}
	.estrato-g1-header__editoria .estrato-g1-header__inner{padding:.4rem 1rem}
	.estrato-g1-header__editoria-label{font-size:.8125rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
	.estrato-g1-header__nav{background:var(--estrato-g1-nav-bg);border-bottom:1px solid #e5e5e5}
	.estrato-g1-header__nav .estrato-g1-header__inner{overflow-x:auto;-webkit-overflow-scrolling:touch}
	.estrato-g1-menu{display:flex;flex-wrap:nowrap;gap:0;list-style:none;margin:0;padding:0;min-height:44px;align-items:stretch}
	.estrato-g1-menu>li{position:relative;flex:0 0 auto}
	.estrato-g1-menu>li>a{
		display:flex;align-items:center;padding:.65rem .9rem;color:var(--estrato-g1-nav-fg);
		font-size:.875rem;font-weight:600;text-decoration:none;white-space:nowrap;border-bottom:3px solid transparent
	}
	.estrato-g1-menu>li>a:hover,.estrato-g1-menu>li.current-menu-item>a,.estrato-g1-menu>li.current-menu-ancestor>a{
		color:#C4170C;border-bottom-color:#C4170C
	}
	.estrato-g1-menu>li.estrato-mega-parent>.sub-menu{
		background:#fff;border:1px solid #e2e8f0;border-radius:0 0 6px 6px;box-shadow:0 12px 32px rgba(15,23,42,.12);
		display:none;left:0;list-style:none;margin:0;min-width:220px;padding:1rem 1.25rem;position:absolute;right:auto;top:100%;z-index:9999;
		grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.35rem 1.5rem
	}
	.estrato-g1-menu>li.estrato-mega-parent:hover>.sub-menu,
	.estrato-g1-menu>li.estrato-mega-parent.estrato-mega-open>.sub-menu{display:grid}
	.estrato-g1-menu .sub-menu .menu-item{margin:0;padding:0}
	.estrato-g1-menu .sub-menu a{color:#1e293b;display:block;font-size:.875rem;font-weight:500;line-height:1.35;padding:.35rem 0;text-decoration:none}
	.estrato-g1-menu .sub-menu a:hover{color:#C4170C}
	.estrato-g1-menu .estrato-mega-column>a{font-weight:700;border-left:3px solid #9aff33;padding-left:.5rem}
	.estrato-g1-header__search-panel{background:#f5f5f5;border-bottom:1px solid #e5e5e5;padding:.75rem 0}
	.estrato-g1-header__search-form{display:flex;gap:.5rem}
	.estrato-g1-header__search-form input{flex:1;border:1px solid #ccc;border-radius:4px;padding:.55rem .75rem;font-size:.9375rem}
	.estrato-g1-header__search-form button{background:#C4170C;color:#fff;border:0;border-radius:4px;padding:.55rem 1rem;font-weight:600;cursor:pointer}
	@media(min-width:960px){
		.estrato-g1-header__menu-btn{display:none}
		.estrato-g1-header__principal-row{grid-template-columns:1fr auto 1fr}
		.estrato-g1-header__logo-wrap{grid-column:2}
		.estrato-g1-header__actions{grid-column:3}
	}
	@media(max-width:959px){
		.estrato-g1-header__nav{display:none}
		.estrato-g1-header__nav.estrato-g1-header__nav--open{display:block;border-top:1px solid rgba(255,255,255,.2);background:var(--estrato-g1-bar-bg)}
		.estrato-g1-header__nav--open .estrato-g1-menu{flex-direction:column;min-height:0}
		.estrato-g1-header__nav--open .estrato-g1-menu>li>a{color:#fff;border-bottom-color:transparent}
		.estrato-g1-header__nav--open .estrato-g1-menu .sub-menu{position:relative;box-shadow:none;border:0;border-top:1px solid rgba(255,255,255,.15);display:none;padding:.5rem 0 .5rem 1rem;background:transparent}
		.estrato-g1-header__nav--open .estrato-g1-menu>li.estrato-mega-open>.sub-menu{display:block}
		.estrato-g1-header__nav--open .estrato-g1-menu .sub-menu a{color:#fff}
	}
	</style>
	<?php
}
add_action( 'wp_head', 'estrato_g1_header_styles', 28 );

/**
 * JS — menu mobile, busca e mega menu.
 */
function estrato_g1_header_scripts() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<script id="estrato-g1-header-js">
	(function(){
		var root=document.getElementById('estrato-g1-header');
		if(!root)return;
		var menuBtn=document.getElementById('estrato-g1-menu-btn');
		var nav=document.getElementById('estrato-g1-nav');
		var searchBtn=document.getElementById('estrato-g1-search-btn');
		var searchPanel=document.getElementById('estrato-g1-search');
		if(menuBtn&&nav){
			menuBtn.addEventListener('click',function(){
				var open=nav.classList.toggle('estrato-g1-header__nav--open');
				menuBtn.setAttribute('aria-expanded',open?'true':'false');
			});
		}
		if(searchBtn&&searchPanel){
			searchBtn.addEventListener('click',function(){
				var open=!searchPanel.hidden;
				searchPanel.hidden=open;
				searchBtn.setAttribute('aria-expanded',open?'false':'true');
				if(!open){var i=document.getElementById('estrato-g1-search-input');if(i)i.focus();}
			});
		}
		var mega=root.querySelector('.estrato-g1-menu');
		if(mega&&window.matchMedia('(max-width:959px)').matches){
			mega.addEventListener('click',function(e){
				var link=e.target.closest('a');
				if(!link)return;
				var li=link.parentElement;
				if(!li||!li.classList.contains('estrato-mega-parent'))return;
				if(li.querySelector('.sub-menu')){
					e.preventDefault();
					li.classList.toggle('estrato-mega-open');
				}
			});
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'estrato_g1_header_scripts', 25 );

/**
 * Remove header/nav legado PressGrid do DOM (menu único — Sprint 1 / B5).
 */
function estrato_g1_strip_pressgrid_nav_start() {
	if ( is_admin() || is_feed() || wp_doing_ajax() || wp_is_json_request() ) {
		return;
	}
	ob_start( 'estrato_g1_strip_pressgrid_nav_callback' );
}
add_action( 'template_redirect', 'estrato_g1_strip_pressgrid_nav_start', 0 );

/**
 * @param string $html
 * @return string
 */
function estrato_g1_strip_pressgrid_nav_callback( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}
	$patterns = array(
		'/<div[^>]*class="[^"]*\bpg-topbar\b[^"]*"[^>]*>[\s\S]*?<\/div>/iu',
		'/<header[^>]*class="[^"]*\bpg-masthead\b[^"]*"[^>]*>[\s\S]*?<\/header>/iu',
		'/<nav[^>]*class="[^"]*\bpg-nav-wrap\b[^"]*"[^>]*>[\s\S]*?<\/nav>/iu',
	);
	foreach ( $patterns as $pattern ) {
		$prev = '';
		while ( $prev !== $html ) {
			$prev = $html;
			$html = preg_replace( $pattern, '', $html );
		}
	}
	return $html;
}
