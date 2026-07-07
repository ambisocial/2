<?php
/**
 * Google Search Template Part
 *
 * This template can be overridden by copying it to your-child-theme/templates/google-search.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author 		TieLabs
 * @version   6.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

get_header(); ?>

	<div <?php tie_content_column_attr(); ?>>

		<header class="entry-header-outer container-wrapper">

			<?php do_action( 'TieLabs/before_archive_title' ); ?>

			<h1 class="page-title"><?php printf( esc_html__( 'Search Results for: %s', TIELABS_TEXTDOMAIN ), '<span>' . esc_html( get_search_query() ) . '</span>' ); ?></h1>

			<?php do_action( 'TieLabs/after_archive_title' ); ?>

			<style>
				/** Search Box */
				.entry-header-outer .gsc-search-box,
				.entry-header-outer .gsc-search-box *{
					margin: 0 !important;
					padding: 0 !important;
					border: none !important;
					background: none !important;
					font-size: inherit !important;
					font-family: inherit !important;
					color: #777 !important;
				}

				.entry-header-outer .gsc-search-box .gsc-search-button button{
					padding: 0 15px !important;
				}

				.entry-header-outer .gsc-search-button-v2 svg{
					fill: #777;
					width: 16px;
					height: 16px;
					margin-top: 5px !important;
				}

				.entry-header-outer .gsc-search-box div.gsc-input-box{
					padding-left: 10px !important;
				}

				.entry-header-outer .gssb_c{
					width: 187px !important;
					margin-top: 30px !important;
					margin-left: -9px !important;
				}

				.entry-header-outer .gssb_c *{
					font-family: inherit !important;
					font-size: inherit !important;
					box-shadow: none !important;
				}

				.entry-header-outer .gsc-completion-container td{
					padding-top: 3px !important;
					padding-bottom: 3px !important;
				}

				.entry-header-outer form.gsc-search-box{
					border: 1px solid rgba(0,0,0,0.1) !important;
					margin-top: 15px !important;
				}

				.entry-header-outer form.gsc-search-box input.gsc-input{
					padding: 10px !important;
				}

				.dark-skin .entry-header-outer .gsc-completion-container tr:nth-child(2n){
					background: rgba(0,0,0,0.08) !important;
				}

				.dark-skin .entry-header-outer .gsc-completion-container tr:hover{
					background: rgba(0,0,0,0.1) !important;
				}

				.dark-skin .entry-header-outer .gsc-completion-container{
					background: #1f2024;
					border: 1px solid rgba(255,255,255,0.07);
				}

				.dark-skin .gsc-adBlock,
				.dark-skin .gsc-webResult.gsc-result, 
				.dark-skin .entry-header-outer form.gsc-search-box{
					border-color: rgba(255,255,255,0.1) !important;
				}

				.gsc-control-cse{
					padding: 0 !important;
				}

				.gsc-control-cse,
				.gsc-above-wrapper-area,
				.gsc-webResult.gsc-result, .gsc-results .gsc-imageResult,
				.gsc-control-cse .gsc-table-result{
					border: none !important;
					background: none !important;
					font-size: inherit !important;
					font-family: inherit !important;
				}

				.gs-web-image-box, .gs-promotion-image-box{
					width: 120px !important;
					text-align: left !important;
				}

				.gs-result .gs-image,
				.gs-result .gs-promotion-image{
					border: none !important;
					width: 100px;
					height: 100px;
					max-width: 100px;
					max-height: 100px;
					object-fit: cover;
				}

				.gsc-table-result{
					padding-top: 10px !important;
				}

				.dark-skin .gs-webResult:not(.gs-no-results-result):not(.gs-error-result) .gs-snippet, .gs-fileFormatType{
					color: #fff !important;
				}
				.gsc-selected-option-container{
					min-width: 100px !important;
				}

				.gsc-result{
					padding-bottom: 15px !important;
				}

				.gsc-webResult.gsc-result{
					border-bottom: 1px solid rgba(0,0,0,0.1) !important;
					padding: 20px 0 !important;
				}

				.gs-snippet{
					font-size: 14px;
					padding-top: 15px;
				}

				.dark-skin .gsc-result-info,
				.dark-skin .gsc-orderby-label,
				.dark-skin .gsc-results .gsc-cursor-box .gsc-cursor-page{
					color: #fff !important;
				}
				
				.gsc-results .gsc-cursor-box{
					margin: 25px 15px; !important;
				}
				
				.gsc-results .gsc-cursor-box .gsc-cursor-page{
					padding: 10px !important;
					margin-bottom: 10px;
					display: inline-block;
					border: 1px solid rgba(255,255,255,0.1)!important;
					background: transparent !important;
				}

				/** Overlay Layout */
				.gsc-modal-background-image{
					display: none;
					position: relative !important;
					width: 0;
					height: 0;
				}

				.gsc-results-wrapper-overlay{
					position: relative !important;
					width: 100%;
					height: auto;
					top: 0;
					left: 0;
					right: 0;
					background: transparent;
					box-shadow: none;
					padding: 0;
					overflow: visible !important;
				}

				.gsc-overflow-hidden{
					overflow: visible !important;
				}

				.rtl .gs-bidi-start-align,
				.rtl .gsc-control-cse .gsc-expansionArea,
				.rtl .gsc-control-cse .gsc-expansionArea *{
					direction: rtl !important;
					text-align: right !important;
				}

				.rtl .gsc-results .gsc-cursor-box .gsc-cursor-page{
					margin-right: 0;
					margin-left: 8px;
				}
				.rtl .gs-web-image-box,
				.rtl .gs-promotion-image-box{
					float: right;
					padding-right: 0;
					padding-left: 8px;
				}
			</style>

			<?php wp_enqueue_script( 'tie-google-search' ); ?>
			<div class="gcse-searchbox-only" data-resultsUrl="<?php echo esc_url( home_url( '?s=' ) ) ?>"></div>

		</header><!-- .entry-header-outer /-->

		<div class="mag-box">
			<div class="container-wrapper">
				<div class="mag-box-container clearfix">

			
					<div class="gcse-searchresults-only"></div>

					<div class="clearfix"></div>
				</div><!-- .mag-box-container /-->
			</div><!-- .container-wrapper /-->
		</div><!-- .mag-box /-->

	</div><!-- .main-content /-->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
