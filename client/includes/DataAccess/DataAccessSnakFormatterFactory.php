<?php

namespace Wikibase\Client\DataAccess;

use Language;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingSnakFormatter;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * A factory for SnakFormatters in a client context, to be reused in different methods that "access
 * repository data" from a client (typically parser functions and Lua scripts).
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch
 */
class DataAccessSnakFormatterFactory {

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param OutputFormatSnakFormatterFactory $snakFormatterFactory
	 */
	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory
	) {
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
	}

	/**
	 * @param Language $language
	 * @param UsageAccumulator $usageAccumulator
	 *
	 * @return SnakFormatter
	 */
	public function newSnakFormatterForLanguage(
		Language $language,
		UsageAccumulator $usageAccumulator
	) {
		$fallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$language,
			LanguageFallbackChainFactory::FALLBACK_ALL
		);

		$options = new FormatterOptions( [
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
			SnakFormatter::OPT_LANG => $language->getCode(),
		] );

		return new UsageTrackingSnakFormatter(
			$this->snakFormatterFactory->getSnakFormatter(
				SnakFormatter::FORMAT_WIKI,
				$options
			),
			$usageAccumulator,
			$fallbackChain->getFetchLanguageCodes()
		);
	}

}
