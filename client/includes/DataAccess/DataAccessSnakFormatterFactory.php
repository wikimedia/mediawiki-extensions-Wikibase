<?php

namespace Wikibase\Client\DataAccess;

use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingSnakFormatter;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
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
