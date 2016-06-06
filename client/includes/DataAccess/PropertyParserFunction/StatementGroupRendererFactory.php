<?php

namespace Wikibase\Client\DataAccess\PropertyParserFunction;

use Language;
use MWException;
use Parser;
use StubUserLang;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingSnakFormatter;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementGroupRendererFactory {

	/**
	 * @var PropertyIdResolver
	 */
	private $propertyIdResolver;

	/**
	 * @var SnaksFinder
	 */
	private $snaksFinder;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var LanguageAwareRenderer[]
	 */
	private $languageAwareRenderers = array();

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var bool
	 */
	private $allowDataAccessInUserLanguage;

	/**
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param SnaksFinder $snaksFinder
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param OutputFormatSnakFormatterFactory $snakFormatterFactory
	 * @param EntityLookup $entityLookup
	 * @param bool $allowDataAccessInUserLanguage
	 */
	public function __construct(
		PropertyIdResolver $propertyIdResolver,
		SnaksFinder $snaksFinder,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		EntityLookup $entityLookup,
		$allowDataAccessInUserLanguage
	) {
		$this->propertyIdResolver = $propertyIdResolver;
		$this->snaksFinder = $snaksFinder;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->entityLookup = $entityLookup;
		$this->allowDataAccessInUserLanguage = $allowDataAccessInUserLanguage;
	}

	/**
	 * @param Parser $parser
	 *
	 * @return StatementGroupRenderer
	 */
	public function newRendererFromParser( Parser $parser ) {
		$usageAccumulator = new ParserOutputUsageAccumulator( $parser->getOutput() );

		if ( $this->allowDataAccessInUserLanguage ) {
			// Use the user's language.
			// Note: This splits the parser cache.
			$targetLanguage = $parser->getOptions()->getUserLangObj();
			return $this->newLanguageAwareRenderer( $targetLanguage, $usageAccumulator );
		} elseif ( $this->useVariants( $parser ) ) {
			$variants = $parser->getConverterLanguage()->getVariants();
			return $this->newVariantsAwareRenderer( $variants, $usageAccumulator );
		} else {
			$targetLanguage = $parser->getTargetLanguage();
			return $this->newLanguageAwareRenderer( $targetLanguage, $usageAccumulator );
		}
	}

	/**
	 * @param Language|StubUserLang $language
	 * @param UsageAccumulator $usageAccumulator
	 *
	 * @return LanguageAwareRenderer
	 * @throws MWException
	 */
	private function newLanguageAwareRenderer( $language, UsageAccumulator $usageAccumulator ) {
		if ( !( $language instanceof Language ) ) {
			wfDebugLog(
				'T107711',
				get_class( $language ) . ' is not a Language object.',
				'all',
				array( 'trace' => wfBacktrace( true ) )
			);
		}
		StubUserLang::unstub( $language );

		$entityStatementsRenderer = new StatementTransclusionInteractor(
			$language,
			$this->propertyIdResolver,
			$this->snaksFinder,
			$this->newSnakFormatterForLanguage( $language, $usageAccumulator ),
			$this->entityLookup
		);

		return new LanguageAwareRenderer(
			$language,
			$entityStatementsRenderer
		);
	}

	/**
	 * @param string $languageCode
	 * @param UsageAccumulator $usageAccumulator
	 *
	 * @return LanguageAwareRenderer
	 */
	private function getLanguageAwareRendererFromCode( $languageCode, UsageAccumulator $usageAccumulator ) {
		if ( !isset( $this->languageAwareRenderers[$languageCode] ) ) {
			$languageAwareRenderer = $this->newLanguageAwareRendererFromCode( $languageCode, $usageAccumulator );
			$this->languageAwareRenderers[$languageCode] = $languageAwareRenderer;
		}

		return $this->languageAwareRenderers[$languageCode];
	}

	/**
	 * @param string $languageCode
	 * @param UsageAccumulator $usageAccumulator
	 *
	 * @return LanguageAwareRenderer
	 */
	private function newLanguageAwareRendererFromCode( $languageCode, UsageAccumulator $usageAccumulator ) {
		$language = Language::factory( $languageCode );

		return $this->newLanguageAwareRenderer(
			$language,
			$usageAccumulator
		);
	}

	/**
	 * @param string[] $variants
	 * @param UsageAccumulator $usageAccumulator
	 *
	 * @return VariantsAwareRenderer
	 */
	private function newVariantsAwareRenderer( array $variants, UsageAccumulator $usageAccumulator ) {
		$languageAwareRenderers = array();

		foreach ( $variants as $variant ) {
			$languageAwareRenderers[$variant] = $this->getLanguageAwareRendererFromCode(
				$variant,
				$usageAccumulator
			);
		}

		return new VariantsAwareRenderer( $languageAwareRenderers, $variants );
	}

	/**
	 * Check whether variants are used in this parser run.
	 *
	 * @param Parser $parser
	 *
	 * @return boolean
	 */
	private function isParserUsingVariants( Parser $parser ) {
		$parserOptions = $parser->getOptions();
		return $parser->OutputType() === Parser::OT_HTML && !$parserOptions->getInterfaceMessage()
			&& !$parserOptions->getDisableContentConversion();
	}

	/**
	 * @param Parser $parser
	 *
	 * @return boolean
	 */
	private function useVariants( Parser $parser ) {
		$converterLanguageHasVariants = $parser->getConverterLanguage()->hasVariants();
		return $this->isParserUsingVariants( $parser ) && $converterLanguageHasVariants;
	}

	/**
	 * @param Language $language
	 * @param UsageAccumulator $usageAccumulator
	 *
	 * @return SnakFormatter
	 */
	private function newSnakFormatterForLanguage(
		Language $language,
		UsageAccumulator $usageAccumulator
	) {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$language,
			LanguageFallbackChainFactory::FALLBACK_ALL
		);

		$options = new FormatterOptions( array(
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $languageFallbackChain,
			ValueFormatter::OPT_LANG => $language->getCode(),
			// ...more options... (?)
		) );

		$snakFormatter = new UsageTrackingSnakFormatter(
			$this->snakFormatterFactory->getSnakFormatter(
				SnakFormatter::FORMAT_WIKI,
				$options
			),
			$usageAccumulator,
			$languageFallbackChain->getFetchLanguageCodes()
		);

		return $snakFormatter;
	}

}
