<?php
/**
 * Traduções pt_BR para strings em búlgaro/inglês hardcoded no tema PressGrid.
 *
 * O autor do PressGrid usou msgids em cirílico (ex.: "Свързани статии") sem
 * arquivo pressgrid-pt_BR.mo — o WordPress exibe o msgid literal.
 *
 * @package EstratoPortalBootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string, string>
 */
function estrato_portal_pressgrid_pt_br_map() {
	return array(
		'Home'                                                                           => 'Início',
		'Breadcrumbs'                                                                    => 'Navegação',
		'By'                                                                             => 'Por',
		'Navigate'                                                                       => 'Institucional',
		'Updated: %s'                                                                    => 'Atualizado: %s',
		'Свързани статии'                                                                 => 'Matérias relacionadas',
		'Сподели:'                                                                       => 'Compartilhar:',
		'Сподели'                                                                        => 'Compartilhar',
		'Сподели статията'                                                               => 'Compartilhar matéria',
		'Сподели в LinkedIn'                                                             => 'Compartilhar no LinkedIn',
		'Копирай линка'                                                                  => 'Copiar link',
		'Копирано!'                                                                      => 'Copiado!',
		'Обратно нагоре'                                                                 => 'Voltar ao topo',

		// Newsletter / formulários.
		'Абонирайте се за бюлетина'                                                      => 'Inscreva-se na newsletter',
		'Абониране'                                                                      => 'Inscrever-se',
		'Имейл адрес'                                                                    => 'Endereço de e-mail',
		'Вашият имейл'                                                                   => 'Seu e-mail',
		'Изпрати'                                                                        => 'Enviar',

		// Clima.
		'Времето'                                                                        => 'Clima',
		'Прогноза за времето'                                                            => 'Previsão do tempo',
		'PressGrid: Времето'                                                             => 'PressGrid: Clima',
		'Влажност'                                                                       => 'Umidade',
		'Вятър'                                                                          => 'Vento',
		'Облачност'                                                                      => 'Nebulosidade',
		'Засичане на локация'                                                            => 'Detectar localização',
		'Усеща се като %s'                                                               => 'Sensação de %s',
		'Покажи прогноза за времето'                                                     => 'Mostrar previsão do tempo',
		'Покажи в горната лента'                                                         => 'Mostrar na barra superior',
		'OpenWeather API ключ'                                                           => 'Chave API OpenWeather',
		'Град (напр. Sofia,BG)'                                                          => 'Cidade (ex.: São Paulo,BR)',
		'Единици (metric / imperial)'                                                    => 'Unidades (metric / imperial)',
		'Въведете OpenWeather API ключ в Customizer → PressGrid: Времето'                => 'Insira a chave OpenWeather API em Personalizar → PressGrid: Clima',
		'Данните за времето не са налични. Проверете API ключа и града.'                 => 'Dados meteorológicos indisponíveis. Verifique a chave API e a cidade.',

		// Forex / câmbio.
		'Валутни курсове'                                                                => 'Câmbio',
		'PressGrid: Валути (Forex)'                                                      => 'PressGrid: Câmbio (Forex)',
		'Базова валута'                                                                  => 'Moeda base',
		'Показвани валути'                                                               => 'Moedas exibidas',
		'EUR, USD, BGN, GBP и т.н.'                                                      => 'EUR, USD, BRL, GBP etc.',
		'Разделени със запетая. Напр: USD,GBP,BGN,CHF'                                   => 'Separadas por vírgula. Ex.: USD,EUR,GBP,CHF',
		'Показвай винаги (независимо от Layout Builder)'                                 => 'Mostrar sempre (independente do Layout Builder)',
		'Slug на бизнес категорията (незадължително)'                                    => 'Slug da categoria de negócios (opcional)',
		'Данни: Европейска централна банка'                                              => 'Fonte: Banco Central Europeu',
		'Данните не са налични'                                                          => 'Dados indisponíveis',
		'Валутният тикер се показва автоматично в топ бара когато има активна секция с бизнес категория в Layout Builder. Не е нужен API ключ — използва безплатния Frankfurter API (ЕЦБ данни).' => 'O ticker de câmbio aparece automaticamente na barra superior quando há uma seção de negócios ativa no Layout Builder. Não requer chave API — usa a API gratuita Frankfurter (dados do BCE).',

		// Outros.
		'Реклама'                                                                        => 'Publicidade',
		'мин. четене'                                                                    => 'min de leitura',
		'Breaking'                                                                       => 'Urgente',
	);
}

/**
 * @param string $translated
 * @param string $text
 * @param string $domain
 * @return string
 */
function estrato_portal_pressgrid_pt_br_gettext( $translated, $text, $domain ) {
	if ( 'pressgrid' !== $domain ) {
		return $translated;
	}

	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	if ( 0 !== strpos( $locale, 'pt' ) ) {
		return $translated;
	}

	static $map = null;
	if ( null === $map ) {
		$map = estrato_portal_pressgrid_pt_br_map();
	}

	return $map[ $text ] ?? $translated;
}

add_filter( 'gettext', 'estrato_portal_pressgrid_pt_br_gettext', 20, 3 );
