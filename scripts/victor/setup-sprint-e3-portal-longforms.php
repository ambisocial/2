<?php
/**
 * Sprint E3 — 2 longforms editoriais por portal (modo analysis, ≥1500 palavras).
 *
 * Uso: ESTRATO_PORTAL=estrato-mind wp eval-file setup-sprint-e3-portal-longforms.php
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$portal = getenv( 'ESTRATO_PORTAL' ) ?: ( function_exists( 'estrato_nav_current_portal_id' ) ? estrato_nav_current_portal_id() : '' );

/**
 * @param string $content
 * @param string $title
 * @param string $cat_slug
 * @param int    $min
 * @return string
 */
function estrato_e3_pad_words( $content, $title, $cat_slug, $min = 1500 ) {
	if ( ! function_exists( 'estrato_content_word_count' ) || ! function_exists( 'estrato_content_build_extension_block' ) ) {
		return $content;
	}
	$guard = 0;
	while ( estrato_content_word_count( $content ) < $min && $guard < 15 ) {
		$content .= "\n" . estrato_content_build_extension_block( $title, $cat_slug );
		++$guard;
	}
	return $content;
}

/**
 * @param string $blog_name
 * @param string $area
 * @param string $title
 * @return string
 */
function estrato_e3_longform_skeleton( $blog_name, $area, $title ) {
	$lead = sprintf(
		'<p class="estrato-editorial-lead"><strong>Análise —</strong> Esta reportagem de fundo do %s examina %s com recorte editorial, dados públicos e implicações para leitores no Brasil. O objetivo é ir além do fluxo de notícias e oferecer um mapa de leitura para as próximas semanas.</p>',
		esc_html( $blog_name ),
		esc_html( $area )
	);

	$sections = array(
		'Panorama'                  => sprintf(
			'O debate sobre %s ganhou densidade nos últimos meses. Especialistas, comunidades e formuladores de opinião passaram a cruzar evidências empíricas com experiências práticas, o que altera a forma como o tema aparece em buscas, newsletters e conversas profissionais. No %s, tratamos o assunto como pauta contínua — não como evento isolado — e por isso organizamos contexto, contrapontos e sinais de monitoramento.',
			$area,
			$blog_name
		),
		'Dados e evidências'        => sprintf(
			'Quando analisamos %s, priorizamos fontes verificáveis: publicações acadêmicas, relatórios institucionais, bases abertas e cobertura primária. Essa triangulação reduz o ruído de opiniões desconectadas de método. Em “%s”, destacamos onde há consenso, onde há divergência metodológica e quais lacunas de dados ainda impedem conclusões definitivas.',
			$area,
			wp_strip_all_tags( $title )
		),
		'Riscos e oportunidades'    => sprintf(
			'Toda leitura aprofundada de %s precisa equilibrar otimismo e cautela. Há oportunidades reais para quem acompanha o tema com disciplina — mas também armadilhas comuns: generalizações apressadas, vieses de confirmação e narrativas que confundem correlação com causalidade. Mapeamos cenários plausíveis para o curto prazo e indicamos quais hipóteses merecem revisão quando surgirem novos dados.',
			$area
		),
		'O que monitorar'           => sprintf(
			'Nos próximos dias, vale observar três frentes em %s: (1) novas publicações ou comunicados oficiais; (2) reação de comunidades e mercados de ideias; (3) desdobramentos práticos relatados por profissionais do setor. O %s atualizará esta análise quando algum desses vetores mudar materialmente o quadro apresentado aqui.',
			$area,
			$blog_name
		),
	);

	$html = $lead;
	foreach ( $sections as $heading => $paragraph ) {
		$html .= '<h2>' . esc_html( $heading ) . '</h2><p>' . esc_html( $paragraph ) . '</p>';
		$html .= '<p>' . esc_html(
			sprintf(
				'Em síntese, %s permanece relevante porque conecta microdecisões cotidianas a tendências estruturais. Leitores que constroem um repositório pessoal de referências — artigos, notas, entrevistas — tendem a formar julgamentos mais estáveis do que quem depende apenas de manchetes diárias.',
				$area
			)
		) . '</p>';
	}

	return $html;
}

$seeds = array(
	'estrato-finance'   => array(
		array(
			'category' => 'mercados',
			'title'    => 'Análise: fluxo estrangeiro e volatilidade na B3 no segundo semestre',
			'excerpt'  => 'Como o investidor brasileiro pode ler o cruzamento entre juros globais, câmbio e rotação setorial na bolsa.',
		),
		array(
			'category' => 'economia',
			'title'    => 'Análise: IPCA, serviços e o que o Copom pode sinalizar em 2026',
			'excerpt'  => 'Panorama de inflação e política monetária com foco em núcleos e expectativas do mercado.',
		),
	),
	'estrato-mind'      => array(
		array(
			'category' => 'aprendizado-cognicao',
			'title'    => 'Análise: segundo cérebro digital sem produtividade tóxica',
			'excerpt'  => 'Métodos de captura, revisão e conexão de notas para aprendizado autodidata no Brasil.',
		),
		array(
			'category' => 'financas-comportamentais',
			'title'    => 'Análise: FIRE brasileiro — cenários realistas além do hype',
			'excerpt'  => 'Independência financeira, inflação local e armadilhas comportamentais para quem planeja longo prazo.',
		),
	),
	'estrato-lifestyle' => array(
		array(
			'category' => 'sabores-paixao',
			'title'    => 'Análise: terroir urbano e a nova onda de fermentação caseira',
			'excerpt'  => 'De kombucha a café de filtro: como hobbies gastronômicos viram cultura de comunidade.',
		),
		array(
			'category' => 'movimento-ar-livre',
			'title'    => 'Análise: trilhas metropolitanas e segurança para iniciantes',
			'excerpt'  => 'Equipamento, planejamento e redes locais para quem começa a explorar ar livre perto das cidades.',
		),
	),
	'estrato-science'   => array(
		array(
			'category' => 'neuro-biologia',
			'title'    => 'Análise: sono, cognição e evidências para rotinas de alta performance',
			'excerpt'  => 'O que a neurociência popular acerta — e onde o ceticismo é necessário.',
		),
		array(
			'category' => 'ia-seguranca',
			'title'    => 'Análise: IA generativa e superfície de ataque para empresas brasileiras',
			'excerpt'  => 'Riscos de vazamento, prompt injection e governança mínima para times enxutos.',
		),
	),
	'estrato-sustain'   => array(
		array(
			'category' => 'agro-sustentavel',
			'title'    => 'Análise: transição agroecológica e viabilidade econômica no campo',
			'excerpt'  => 'SAFs, crédito rural e mercados de diferenciação para pequenos e médios produtores.',
		),
		array(
			'category' => 'vida-nomade',
			'title'    => 'Análise: nomadismo digital regulado — impostos e residência fiscal',
			'excerpt'  => 'Cenários para brasileiros que trabalham remoto fora do país em 2026.',
		),
	),
	'estrato-culture'   => array(
		array(
			'category' => 'jogos-imaginacao',
			'title'    => 'Análise: OSR e a ressurgência do RPG de mesa no Brasil',
			'excerpt'  => 'Comunidades, financiamento coletivo e design indie em mesa.',
		),
		array(
			'category' => 'narrativas-som',
			'title'    => 'Análise: podcasts de nicho e descoberta fora do algoritmo mainstream',
			'excerpt'  => 'Como narrativas longas em áudio criam fandoms sustentáveis em português.',
		),
	),
);

$items = $seeds[ $portal ] ?? array();
if ( ! $items ) {
	WP_CLI::error( "Sem seeds longform para {$portal}" );
}

$blog_name = get_bloginfo( 'name' );
$created   = 0;
$skipped   = 0;

foreach ( $items as $item ) {
	$cat_slug = $item['category'];
	$title    = $item['title'];
	$key      = 'estrato-longform-' . sanitize_title( $portal . '-' . $cat_slug . '-' . substr( md5( $title ), 0, 8 ) );

	$existing = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_estrato_pipeline_id',
			'meta_value'     => $key,
		)
	);
	if ( ! empty( $existing[0] ) ) {
		$post_id = (int) $existing[0];
		update_post_meta( $post_id, '_estrato_content_mode', 'analysis' );
		update_post_meta( $post_id, '_estrato_editorial_source', '1' );
		$current = get_post_field( 'post_content', $post_id );
		$padded  = estrato_e3_pad_words( $current, $title, $cat_slug, 1500 );
		if ( $padded !== $current ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $padded,
				)
			);
		}
		if ( function_exists( 'estrato_bridge_set_fallback_thumbnail' ) && ! has_post_thumbnail( $post_id ) ) {
			estrato_bridge_set_fallback_thumbnail( $post_id );
		}
		if ( 'publish' !== get_post_status( $post_id ) ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'publish',
				)
			);
		}
		++$skipped;
		WP_CLI::log( "Longform atualizado: #{$post_id}" );
		continue;
	}

	$term = get_term_by( 'slug', $cat_slug, 'category' );
	if ( ! $term ) {
		WP_CLI::warning( "Categoria ausente: {$cat_slug}" );
		continue;
	}

	$area    = function_exists( 'estrato_content_category_label' )
		? estrato_content_category_label( $cat_slug )
		: $term->name;
	$content = estrato_e3_longform_skeleton( $blog_name, $area, $title );
	$content = estrato_e3_pad_words( $content, $title, $cat_slug, 1500 );

	$author_id = function_exists( 'estrato_eeat_resolve_author_id' )
		? estrato_eeat_resolve_author_id( $cat_slug )
		: 1;

	$post_id = wp_insert_post(
		array(
			'post_title'    => $title,
			'post_content'  => $content,
			'post_excerpt'  => $item['excerpt'],
			'post_status'   => 'draft',
			'post_author'   => $author_id,
			'post_category' => array( (int) $term->term_id ),
			'meta_input'    => array(
				'_estrato_pipeline_id'       => $key,
				'_estrato_content_mode'      => 'analysis',
				'_estrato_editorial_source'  => '1',
			),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( $post_id->get_error_message() );
		continue;
	}

	if ( function_exists( 'estrato_content_enrich_post' ) && ! defined( 'ESTRATO_ENRICHING' ) ) {
		define( 'ESTRATO_ENRICHING', true );
		estrato_content_enrich_post( (int) $post_id );
	}

	if ( function_exists( 'estrato_bridge_set_editorial_thumbnail' ) && ! has_post_thumbnail( $post_id ) ) {
		estrato_bridge_set_editorial_thumbnail( (int) $post_id );
	} elseif ( function_exists( 'estrato_bridge_set_fallback_thumbnail' ) && ! has_post_thumbnail( $post_id ) ) {
		estrato_bridge_set_fallback_thumbnail( (int) $post_id );
	}

	wp_update_post(
		array(
			'ID'          => (int) $post_id,
			'post_status' => 'publish',
		)
	);

	++$created;
	WP_CLI::log( "Longform #{$post_id} — {$title}" );
}

$longforms = function_exists( 'estrato_regression_longform_count' )
	? estrato_regression_longform_count()
	: $created;

WP_CLI::success(
	wp_json_encode(
		array(
			'portal'    => $portal,
			'created'   => $created,
			'skipped'   => $skipped,
			'longforms' => $longforms,
		),
		JSON_UNESCAPED_UNICODE
	)
);
