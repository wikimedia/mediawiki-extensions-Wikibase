<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use Language;
use Parser;
use ValueFormatters\FormatterOptions;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RendererFactory {

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
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param SnaksFinder $snaksFinder
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param OutputFormatSnakFormatterFactory $snakFormatterFactory
	 */
	public function __construct(
		PropertyIdResolver $propertyIdResolver,
		SnaksFinder $snaksFinder,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory
	) {
		$this->propertyIdResolver = $propertyIdResolver;
		$this->snaksFinder = $snaksFinder;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
	}

	/**
	 * @param Parser $parser
	 *
	 * @return Renderer
	 */
	public function newFromParser( Parser $parser ) {
		if ( $this->useVariants( $parser ) ) {
			$variants = $parser->getConverterLanguage()->getVariants();
			return $this->newVariantsRenderer( $variants );
		} else {
			$targetLanguage = $parser->getTargetLanguage();
			return $this->newFromLanguage( $targetLanguage );
		}
	}

	/**
	 * @param Language $language
	 *
	 * @return Renderer
	 */
	public function newFromLanguage( Language $language ) {
		return new LanguageRenderer(
			$language,
			$this->propertyIdResolver,
			$this->snaksFinder,
			$this->newSnakFormatterForLanguage( $language )
		);
	}

	/**
	 * @param string[] $variants
	 *
	 * @return VariantsRenderer
	 */
	private function newVariantsRenderer( array $variants ) {
		return new VariantsRenderer( $this, $variants );
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
