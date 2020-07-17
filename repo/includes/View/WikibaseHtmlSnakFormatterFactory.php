<?php

namespace Wikibase\Repo\View;

use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\View\HtmlSnakFormatterFactory;

/**
 * An HtmlSnakFormatterFactory implementation using an OutputFormatSnakFormatterFactory
 *
 * @license GPL-2.0-or-later
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
	 * @param TermLanguageFallbackChain $termLanguageFallbackChain
	 * @return FormatterOptions
	 */
	private function getFormatterOptions(
		$languageCode,
		TermLanguageFallbackChain $termLanguageFallbackChain
	) {
		$formatterOptions = new FormatterOptions( [
			ValueFormatter::OPT_LANG => $languageCode,
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $termLanguageFallbackChain,
		] );
		return $formatterOptions;
	}

	/**
	 * @param string $languageCode
	 * @param TermLanguageFallbackChain $termLanguageFallbackChain
	 * @return SnakFormatter
	 */
	public function getSnakFormatter(
		$languageCode,
		TermLanguageFallbackChain $termLanguageFallbackChain
	) {
		$formatterOptions = $this->getFormatterOptions( $languageCode, $termLanguageFallbackChain );

		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_VERBOSE,
			$formatterOptions
		);
	}

}
