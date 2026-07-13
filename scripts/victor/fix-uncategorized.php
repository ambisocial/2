<?php
/**
 * Recategoriza posts em "sem-categoria" via regras de palavras-chave.
 * Uso: wp eval-file fix-uncategorized.php [--dry-run]
 *
 * @package EstratoVictor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$dry_run = '1' === getenv( 'ESTRATO_DRY_RUN' );

$rules = array(
	'criptomoedas'     => '/\b(bitcoin|btc|ethereum|cripto|blockchain|nft|stablecoin|binance)\b/iu',
	'agronegocio'      => '/\b(agroneg[oó]cio|soja|caf[eé]|pecu[aá]ria|rural|safra|colheita|d[eé]ficit h[ií]drico|dívida rural)\b/iu',
	'mercados'         => '/\b(ibovespa|bolsa|b3|ações|d[oó]lar|euro|selic|juros|cdi|renda fixa|cota[cç][aã]o|fii|etf|tesouro|copom|banco central)\b/iu',
	'financas-pessoais' => '/\b(finanças pessoais|cart[aã]o de cr[eé]dito|consignado|inss|aposentadoria|orçamento familiar|educa[cç][aã]o financeira)\b/iu',
	'negocios'         => '/\b(empresa|m&a|fus[aã]o|aquisi[cç][aã]o|startup|fintech|bets|aposta|saf\b|varejo|ind[uú]stria|petrobras|vale\b)\b/iu',
	'economia'         => '/\b(pib|infla[cç][aã]o|ipca|fiscal|orçamento|imposto|tribut|desemprego|emprego|sal[aá]rio m[ií]nimo|crise econ[oô]mica)\b/iu',
	'mundo'            => '/\b(eua|europa|china|guerra|onu|geopol|internacional|copa do mundo|marrocos|fran[cç]a)\b/iu',
	'politica'         => '/\b(congresso|senado|c[aâ]mara|bolsonaro|lula|stf|ministro|deputado|elei[cç][aã]o|governo federal)\b/iu',
	'tecnologia'       => '/\b(ia\b|intelig[eê]ncia artificial|chatgpt|software|aplicativo|smartphone|5g|dados pessoais|lgpd)\b/iu',
	'brasil'           => '/\b(brasil\b|nordeste|sudeste|sul\b|amazonas|bahia|rio de janeiro|s[aã]o paulo)\b/iu',
);

$finance_slugs = array(
	'economia',
	'mercados',
	'negocios',
	'financas-pessoais',
	'criptomoedas',
	'agronegocio',
	'mundo',
);

$uncat = get_term_by( 'slug', 'sem-categoria', 'category' );
if ( ! $uncat || is_wp_error( $uncat ) ) {
	echo "sem-categoria não encontrada\n";
	return;
}

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'category'       => (int) $uncat->term_id,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

$assigned = 0;
$default  = 0;

foreach ( $posts as $post ) {
	$haystack = strtolower( $post->post_title . ' ' . wp_strip_all_tags( $post->post_content ) );
	$target   = 'negocios';

	foreach ( $rules as $slug => $pattern ) {
		if ( preg_match( $pattern, $haystack ) ) {
			$target = $slug;
			break;
		}
	}

	// Preferir categorias financeiras quando possível.
	if ( ! in_array( $target, $finance_slugs, true ) ) {
		if ( preg_match( $rules['negocios'], $haystack ) ) {
			$target = 'negocios';
		} elseif ( preg_match( $rules['economia'], $haystack ) ) {
			$target = 'economia';
		} elseif ( preg_match( $rules['mundo'], $haystack ) ) {
			$target = 'mundo';
		} else {
			$target = 'economia';
			++$default;
		}
	}

	$term = get_term_by( 'slug', $target, 'category' );
	if ( ! $term || is_wp_error( $term ) ) {
		echo "categoria {$target} ausente para post {$post->ID}\n";
		continue;
	}

	if ( $dry_run ) {
		echo "[dry-run] #{$post->ID} → {$target}\n";
		++$assigned;
		continue;
	}

	wp_set_post_categories( $post->ID, array( (int) $term->term_id ), false );
	++$assigned;
}

$remaining = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'category'       => (int) $uncat->term_id,
		'fields'         => 'ids',
	)
);

$mode = $dry_run ? 'DRY-RUN' : 'APPLIED';
echo "{$mode}: {$assigned} posts recategorizados, default economia: {$default}, restantes sem-categoria: " . count( $remaining ) . "\n";
