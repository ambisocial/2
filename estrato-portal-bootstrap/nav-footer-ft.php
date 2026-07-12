<?php
/**
 * Footer institucional estilo Financial Times — acordeão + Grupo Estrato.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Negócios adicionais do grupo (além dos portais Estrato).
 * Filtro: estrato_ft_group_businesses_extra
 *
 * @return array<int, array{name:string,url:string,tagline?:string}>
 */
function estrato_ft_group_businesses_extra() {
	$defaults = array(
		array(
			'name'    => 'Ambi Social',
			'url'     => 'https://ambi.social/',
			'tagline' => 'Tecnologia e mídia',
		),
	);
	return apply_filters( 'estrato_ft_group_businesses_extra', $defaults );
}

/**
 * Todos os negócios do grupo para o grid "Mais do Grupo Estrato".
 *
 * @return array<int, array{name:string,url:string,tagline:string,current:bool}>
 */
function estrato_ft_group_businesses_all() {
	$current_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$out          = array();

	if ( function_exists( 'estrato_nav_network_catalog' ) ) {
		foreach ( estrato_nav_network_catalog() as $node ) {
			$host = (string) wp_parse_url( $node['url'], PHP_URL_HOST );
			$out[] = array(
				'name'    => $node['name'],
				'url'     => $node['url'],
				'tagline' => $node['tagline'],
				'current' => $host === $current_host,
			);
		}
	}

	foreach ( estrato_ft_group_businesses_extra() as $biz ) {
		if ( empty( $biz['url'] ) || empty( $biz['name'] ) ) {
			continue;
		}
		$host = (string) wp_parse_url( $biz['url'], PHP_URL_HOST );
		$out[] = array(
			'name'    => (string) $biz['name'],
			'url'     => (string) $biz['url'],
			'tagline' => (string) ( $biz['tagline'] ?? '' ),
			'current' => $host === $current_host,
		);
	}

	return $out;
}

/**
 * Verifica se URL interna existe (página publicada ou asset).
 *
 * @param string $url
 * @return bool
 */
function estrato_ft_footer_link_ok( $url ) {
	$home = home_url( '/' );
	if ( 0 !== strpos( $url, $home ) ) {
		return true;
	}
	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	if ( '' === $path ) {
		return true;
	}
	if ( preg_match( '/\.(xml|txt)$/i', $path ) ) {
		return true;
	}
	if ( 0 === strpos( $path, 'category/' ) || 0 === strpos( $path, 'tudo-sobre/' ) ) {
		$page = get_page_by_path( $path );
		return $page && 'publish' === $page->post_status;
	}
	$page = get_page_by_path( $path );
	return $page && 'publish' === $page->post_status;
}

/**
 * Seções do acordeão (padrão FT).
 *
 * @return array<int, array{id:string,label:string,open:bool,links:array<int,array{label:string,url:string}>}>
 */
function estrato_ft_footer_accordion_sections() {
	$portal = function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : 'estrato-finance';
	$is_finance = 'estrato-finance' === $portal;

	$sections = array(
		array(
			'id'    => 'support',
			'label' => 'Suporte',
			'open'  => true,
			'links' => array(
				array( 'label' => 'Sobre nós', 'url' => home_url( '/sobre/' ) ),
				array( 'label' => 'Contato', 'url' => home_url( '/contato/' ) ),
				array( 'label' => 'Política editorial', 'url' => home_url( '/politica-editorial/' ) ),
			),
		),
		array(
			'id'    => 'legal',
			'label' => 'Legal e privacidade',
			'open'  => false,
			'links' => array(
				array( 'label' => 'Política de privacidade', 'url' => home_url( '/politica-de-privacidade/' ) ),
				array( 'label' => 'Política editorial', 'url' => home_url( '/politica-editorial/' ) ),
			),
		),
		array(
			'id'    => 'services',
			'label' => 'Serviços',
			'open'  => false,
			'links' => array(
				array( 'label' => 'Feed RSS', 'url' => home_url( '/feed/' ) ),
				array( 'label' => 'Newsletter', 'url' => home_url( '/contato/' ) ),
			),
		),
		array(
			'id'    => 'tools',
			'label' => 'Ferramentas',
			'open'  => false,
			'links' => array(
				array( 'label' => 'Mapa do site', 'url' => home_url( '/sitemap_index.xml' ) ),
				array( 'label' => 'News sitemap', 'url' => home_url( '/news-sitemap.xml' ) ),
				array( 'label' => 'llms.txt', 'url' => home_url( '/llms.txt' ) ),
			),
		),
		array(
			'id'    => 'community',
			'label' => 'Comunidade',
			'open'  => false,
			'links' => array(),
		),
	);

	if ( $is_finance ) {
		$sections[2]['links'][] = array( 'label' => 'Cotações', 'url' => home_url( '/cotacoes/' ) );
		$sections[3]['links'][] = array( 'label' => 'Tudo sobre Selic', 'url' => home_url( '/tudo-sobre/selic/' ) );
		$sections[3]['links'][] = array( 'label' => 'Tudo sobre Ibovespa', 'url' => home_url( '/tudo-sobre/ibovespa/' ) );
	}

	// Editorias no acordeão Comunidade.
	if ( function_exists( 'estrato_nav_footer_editorias' ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'parent'     => 0,
				'hide_empty' => false,
				'number'     => 8,
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			$legacy = array( 'politica', 'tecnologia', 'brasil', 'sem-categoria', 'economia', 'mercados', 'negocios', 'financas-pessoais', 'criptomoedas', 'agronegocio', 'mundo' );
			if ( ! $is_finance ) {
				$legacy = array( 'politica', 'tecnologia', 'brasil', 'sem-categoria' );
			}
			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $legacy, true ) ) {
					continue;
				}
				$sections[4]['links'][] = array(
					'label' => $term->name,
					'url'   => get_category_link( $term ),
				);
			}
		}
	}

	// Remove links para páginas inexistentes.
	foreach ( $sections as &$section ) {
		$section['links'] = array_values(
			array_filter(
				$section['links'],
				function ( $link ) {
					return estrato_ft_footer_link_ok( $link['url'] );
				}
			)
		);
	}
	unset( $section );

	return apply_filters( 'estrato_ft_footer_accordion_sections', $sections, $portal );
}

/**
 * Renderiza o footer FT completo.
 */
function estrato_ft_render_footer() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$sections  = estrato_ft_footer_accordion_sections();
	$businesses = estrato_ft_group_businesses_all();
	$site_name = get_bloginfo( 'name' );
	$year      = gmdate( 'Y' );

	?>
	<footer id="estrato-ft-footer" class="estrato-ft-footer" role="contentinfo" aria-label="Rodapé institucional">
		<div class="estrato-ft-footer__inner">
			<nav class="estrato-ft-accordion" aria-label="Links do rodapé">
				<?php foreach ( $sections as $section ) : ?>
					<?php if ( empty( $section['links'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<details class="estrato-ft-accordion__item" <?php echo ! empty( $section['open'] ) ? 'open' : ''; ?>>
						<summary class="estrato-ft-accordion__head">
							<span><?php echo esc_html( $section['label'] ); ?></span>
							<span class="estrato-ft-accordion__chev" aria-hidden="true"></span>
						</summary>
						<ul class="estrato-ft-accordion__body">
							<?php foreach ( $section['links'] as $link ) : ?>
								<li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php endforeach; ?>
			</nav>

			<div class="estrato-ft-group">
				<details class="estrato-ft-group__toggle">
					<summary class="estrato-ft-group__head">
						<span>Mais do Grupo Estrato</span>
						<span class="estrato-ft-group__chev" aria-hidden="true">›</span>
					</summary>
					<div class="estrato-ft-group__panel">
						<h3 class="estrato-ft-group__title">Mais do Grupo Estrato</h3>
						<ul class="estrato-ft-group__grid">
							<?php foreach ( $businesses as $biz ) : ?>
								<?php if ( ! empty( $biz['current'] ) ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<li>
									<a href="<?php echo esc_url( $biz['url'] ); ?>">
										<span class="estrato-ft-group__name"><?php echo esc_html( $biz['name'] ); ?></span>
										<?php if ( ! empty( $biz['tagline'] ) ) : ?>
											<span class="estrato-ft-group__tag"><?php echo esc_html( $biz['tagline'] ); ?></span>
										<?php endif; ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</details>
			</div>

			<div class="estrato-ft-legal">
				<p class="estrato-ft-legal__disclaimer">
					<?php if ( 'estrato-finance' === estrato_nav_current_portal_id() ) : ?>
						Cotações e índices sujeitos a delay de 15 minutos (B3). Dados de câmbio via Frankfurter/ECB.
					<?php else : ?>
						Conteúdo curado de fontes públicas com atribuição editorial. Atualização contínua via RSS e pipeline.
					<?php endif; ?>
				</p>
				<p class="estrato-ft-legal__copy">
					© <?php echo esc_html( strtoupper( $site_name ) ); ?> <?php echo esc_html( $year ); ?>.
					Marcas e nomes citados pertencem aos respectivos titulares.
				</p>
				<p class="estrato-ft-legal__code">
					A redação segue princípios de transparência de fontes descritos na
					<a href="<?php echo esc_url( home_url( '/politica-editorial/' ) ); ?>">política editorial</a>.
				</p>
			</div>
		</div>
		<div class="estrato-ft-footer__brand">
			<span class="estrato-ft-footer__brand-text">Estrato Mídia e Conteúdo Ltda.</span>
		</div>
	</footer>
	<?php
}
add_action( 'wp_footer', 'estrato_ft_render_footer', 15 );

/**
 * CSS footer FT + oculta widgets legado PressGrid.
 */
function estrato_ft_footer_styles() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<style id="estrato-ft-footer-css">
	.site-footer .footer-widgets,.site-footer .widget-area,.pg-footer-widgets,.pg-footer-top,footer.site-footer .container > .row:first-child,#footer-widgets{display:none!important}
	.site-footer,.pg-footer{padding:0!important;background:transparent!important;border:0!important}
	.pg-footer-bottom{display:none!important}
	.estrato-ft-footer{background:#262a33;color:#ced4da;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;margin-top:2rem}
	.estrato-ft-footer__inner{max-width:1200px;margin:0 auto;padding:0 1rem 1.5rem}
	.estrato-ft-accordion__item{border-top:1px solid rgba(255,255,255,.12)}
	.estrato-ft-accordion__item:last-of-type{border-bottom:1px solid rgba(255,255,255,.12)}
	.estrato-ft-accordion__head{display:flex;align-items:center;justify-content:space-between;padding:1rem 0;cursor:pointer;list-style:none;font-weight:700;color:#fff;font-size:.9375rem}
	.estrato-ft-accordion__head::-webkit-details-marker{display:none}
	.estrato-ft-accordion__chev{width:.5rem;height:.5rem;border-right:2px solid #ced4da;border-bottom:2px solid #ced4da;transform:rotate(45deg);transition:transform .2s;margin-right:.25rem}
	.estrato-ft-accordion__item[open] .estrato-ft-accordion__chev{transform:rotate(-135deg);margin-top:.35rem}
	.estrato-ft-accordion__body{list-style:none;margin:0 0 1rem;padding:0}
	.estrato-ft-accordion__body a{display:block;padding:.45rem 0;color:#ced4da;text-decoration:none;font-size:.875rem}
	.estrato-ft-accordion__body a:hover{color:#fff;text-decoration:underline}
	.estrato-ft-group{border-top:1px solid rgba(255,255,255,.12);padding:.25rem 0}
	.estrato-ft-group__head{display:flex;align-items:center;justify-content:space-between;padding:1rem 0;cursor:pointer;list-style:none;font-weight:700;color:#fff;font-size:.9375rem}
	.estrato-ft-group__head::-webkit-details-marker{display:none}
	.estrato-ft-group__chev{font-size:1.25rem;line-height:1;color:#fff}
	.estrato-ft-group__panel{background:#fff1e5;color:#33302e;padding:1.25rem 1rem 1.5rem;margin:0 -1rem}
	.estrato-ft-group__title{font-family:Georgia,"Times New Roman",serif;font-size:1.125rem;font-weight:400;margin:0 0 1rem;color:#33302e}
	.estrato-ft-group__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.35rem 1.5rem;list-style:none;margin:0;padding:0}
	.estrato-ft-group__grid a{display:block;text-decoration:underline;color:#0d7680;font-size:.875rem;line-height:1.45;padding:.2rem 0}
	.estrato-ft-group__grid a:hover{color:#004d4d}
	.estrato-ft-group__name{display:block}
	.estrato-ft-group__tag{display:block;font-size:.75rem;color:#5c5650;text-decoration:none;margin-top:.1rem}
	.estrato-ft-legal{padding:1.25rem 0 0;font-size:.75rem;line-height:1.55;color:#9aa3ad}
	.estrato-ft-legal a{color:#ced4da;text-decoration:underline}
	.estrato-ft-legal a:hover{color:#fff}
	.estrato-ft-footer__brand{background:#000;padding:.85rem 1rem;text-align:right}
	.estrato-ft-footer__brand-text{color:#fff;font-size:.8125rem;font-style:italic;font-weight:700;letter-spacing:.02em}
	@media(min-width:768px){
		.estrato-ft-accordion{display:grid;grid-template-columns:repeat(3,1fr);gap:0 2rem;border-top:1px solid rgba(255,255,255,.12)}
		.estrato-ft-accordion__item{border:0!important}
		.estrato-ft-accordion__item[open] .estrato-ft-accordion__chev,.estrato-ft-accordion__chev{display:none}
		.estrato-ft-accordion__head{pointer-events:none;padding:.75rem 0 .5rem}
		.estrato-ft-group__grid{grid-template-columns:repeat(3,minmax(0,1fr))}
	}
	@media(min-width:1024px){
		.estrato-ft-group__grid{grid-template-columns:repeat(4,minmax(0,1fr))}
	}
	</style>
	<?php
}
add_action( 'wp_head', 'estrato_ft_footer_styles', 35 );

/**
 * Acordeão: um painel aberto por vez no mobile (comportamento FT).
 */
function estrato_ft_footer_scripts() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	?>
	<script id="estrato-ft-footer-js">
	(function(){
		var root=document.getElementById('estrato-ft-footer');
		if(!root||window.matchMedia('(min-width:768px)').matches)return;
		var items=root.querySelectorAll('.estrato-ft-accordion__item');
		items.forEach(function(el){
			el.addEventListener('toggle',function(){
				if(!el.open)return;
				items.forEach(function(other){if(other!==el)other.open=false;});
			});
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'estrato_ft_footer_scripts', 40 );

/**
 * Desativa injeção legada no pg-footer-bottom (substituída pelo footer FT).
 */
function estrato_ft_disable_legacy_footer_legal() {
	remove_action( 'wp_footer', 'estrato_eeat_footer_legal', 20 );
}
add_action( 'init', 'estrato_ft_disable_legacy_footer_legal', 30 );
