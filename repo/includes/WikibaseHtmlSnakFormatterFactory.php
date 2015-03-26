<?php

namespace Wikibase\Repo;

use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\View\HtmlSnakFormatterFactory;

/**
 * An HtmlSnakFormatterFactory implementation using an OutputFormatSnakFormatterFactory
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class WikibaseHtmlSnakFormatterFactory implements HtmlSnakFormatterFactory {

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @param OutputFormatSnakFormatterFactory $snakFormatterFactory
	 */
	public function __construct( OutputFormatSnakFormatterFactory $snakFormatterFactory ) {
		$this->snakFormatterFactory = $snakFormatterFactory;
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param LabelLookup $labelLookup
	 *
	 * @return FormatterOptions
	 */
	private function getFormatterOptions(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain,
		LabelLookup $labelLookup
	) {
		$formatterOptions = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $languageCode,
			'languages' => $languageFallbackChain,
			'LabelLookup' => $labelLookup,
		) );
		return $formatterOptions;
	}

	/**
	 * @param string $languageCode
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param LabelLookup $labelLookup
	 *
	 * @return SnakFormatter
	 */
	public function getSnakFormatter(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain,
		LabelLookup $labelLookup
	) {
		$formatterOptions = $this->getFormatterOptions( $languageCode, $languageFallbackChain, $labelLookup );

		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$formatterOptions
		);
	}

}
