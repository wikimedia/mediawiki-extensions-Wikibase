<?php

namespace Wikibase\Repo\View;

use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\HtmlSnakFormatterFactory;

/**
 * An HtmlSnakFormatterFactory implementation using an OutputFormatSnakFormatterFactory
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class WikibaseHtmlSnakFormatterFactory implements HtmlSnakFormatterFactory {

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	public function __construct( OutputFormatSnakFormatterFactory $snakFormatterFactory ) {
		$this->snakFormatterFactory = $snakFormatterFactory;
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return FormatterOptions
	 */
	private function getFormatterOptions(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		$formatterOptions = new FormatterOptions( [
			ValueFormatter::OPT_LANG => $languageCode,
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $languageFallbackChain,
			FormatterLabelDescriptionLookupFactory::OPT_LABEL_DESCRIPTION_LOOKUP => $labelDescriptionLookup,
		] );
		return $formatterOptions;
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return SnakFormatter
	 */
	public function getSnakFormatter(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		$formatterOptions = $this->getFormatterOptions( $languageCode, $languageFallbackChain, $labelDescriptionLookup );

		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML,
			$formatterOptions
		);
	}

}
