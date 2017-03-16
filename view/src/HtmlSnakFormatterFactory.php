<?php

namespace Wikibase\View;

use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Edrsf\LanguageFallbackChain;
use Wikibase\Lib\SnakFormatter;

/**
 * A factory constructing SnakFormatters that output HTML.
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface HtmlSnakFormatterFactory {

	/**
	 * @param string $languageCode
	 * @param \Wikibase\Edrsf\LanguageFallbackChain $languageFallbackChain
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return SnakFormatter
	 */
	public function getSnakFormatter(
		$languageCode,
		LanguageFallbackChain $languageFallbackChain,
		LabelDescriptionLookup $labelDescriptionLookup
	);

}
