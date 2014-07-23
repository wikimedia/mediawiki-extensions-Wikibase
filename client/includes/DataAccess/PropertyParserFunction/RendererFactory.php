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
	 * @param SnaksFinder $snaksFinder
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param OutputFormatSnakFormatterFactory $snakFormatterFactory
	 */
	public function __construct(
		SnaksFinder $snaksFinder,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory
	) {
		$this->snaksFinder = $snaksFinder;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
	}

	/**
	 * @param Parser $parser
	 *
	 * @return Renderer
	 */
	public function newRendererFromParser( Parser $parser ) {
		if ( $this->useVariants( $parser ) ) {
			$variants = $parser->getConverterLanguage()->getVariants();
			return $this->newVariantsAwareRenderer( $variants );
		} else {
			$targetLanguage = $parser->getTargetLanguage();
			return $this->newLanguageAwareRenderer( $targetLanguage );
		}
	}

	/**
	 * @param Language $language
	 *
	 * @return LanguageAwareRenderer
	 */
	public function newLanguageAwareRenderer( Language $language ) {
		return new LanguageAwareRenderer(
			$language,
			$this->snaksFinder,
			$this->newSnakFormatterForLanguage( $language )
		);
	}

	/**
	 * @param string[] $variants
	 *
	 * @return VariantsAwareRenderer
	 */
	private function newVariantsAwareRenderer( array $variants ) {
		return new VariantsAwareRenderer( $this, $variants );
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
