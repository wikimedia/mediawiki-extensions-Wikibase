<?php

namespace Wikibase\View;

use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * A factory constructing SnakFormatters that output HTML.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface HtmlSnakFormatterFactory {

	/**
	 * @param string $languageCode
	 * @param TermLanguageFallbackChain $termLanguageFallbackChain
	 * @return SnakFormatter
	 */
	public function getSnakFormatter(
		$languageCode,
		TermLanguageFallbackChain $termLanguageFallbackChain
	);

}
