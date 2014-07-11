<?php

namespace Wikibase\DataAccess;

use Language;
use Parser;
use ValueFormatters\FormatterOptions;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\PropertyLabelResolver;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionRendererFactory {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @param EntityLookup $entityLookup
	 * @param PropertyLabelResolver $propertyLabelResolver
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 */
	public function __construct(
		EntityLookup $entityLookup,
		PropertyLabelResolver $propertyLabelResolver,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory
	) {
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
	}

	/**
	 * @param Parser $parser
	 *
	 * @return PropertyParserFunctionRenderer
	 */
	public function newFromParser( Parser $parser ) {
		if ( $this->useVariants( $parser ) ) {
			$language = $parser->getConverterLanguage();
			return $this->newVariantsRenderer( $language );
		} else {
			$language = $parser->getTargetLanguage();
			return $this->newLanguageRenderer( $language );
		}
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
		return $this->isParserUsingVariants( $parser )
			&& $parser->getConverterLanguage()->hasVariants();
	}

	/**
	 * @param Language $language
	 *
	 * @return PropertyParserFunctionLanguageRenderer
	 */
	private function newLanguageRenderer( Language $language ) {
		return new PropertyParserFunctionLanguageRenderer(
			$this->newPropertyParserFunctionEntityRenderer( $language ),
			$language
		);
	}

	/**
	 * @param Language $language
	 *
	 * @return PropertyParserFunctionVariantsRenderer
	 */
	private function newVariantsRenderer( Language $language ) {
		return new PropertyParserFunctionVariantsRenderer(
			$this->newLanguageRenderer( $language ),
			$language->getVariants()
		);
	}

	/**
	 * @param Language $language
	 *
	 * @return PropertyParserFunctionEntityRenderer
	 */
	private function newPropertyParserFunctionEntityRenderer( Language $language ) {
		return new PropertyParserFunctionEntityRenderer(
			$language,
			$this->entityLookup,
			$this->propertyLabelResolver,
			$this->newSnakFormatterForLanguage( $language )
		);
	}

	/**
	 * @param Language $language
	 *
	 * @return SnakFormatter
	 */
	private function newSnakFormatterForLanguage( Language $language ) {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$language,
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		);

		$options = new FormatterOptions( array(
			'languages' => $languageFallbackChain,
			// ...more options... (?)
		) );

		$snakFormatter = $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_WIKI,
			$options
		);

		return $snakFormatter;
	}

}
