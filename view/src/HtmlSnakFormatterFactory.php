<?php

namespace Wikibase\View;

use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\LabelLookup;

/**
 * A factory constructing SnakFormatters that output HTML.
 * @since 0.5
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
interface HtmlSnakFormatterFactory {

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
	);

}
