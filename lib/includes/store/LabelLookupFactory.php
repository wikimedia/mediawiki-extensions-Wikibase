<?php

namespace Wikibase\Lib\Store;

use Wikibase\LanguageFallbackChain;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
interface LabelLookupFactory {

	/**
	 * @param {LanguageFallbackChain|string} $languageSpec
	 *
	 * @return LabelLookup
	 */
	public function getLabelLookup( $languageSpec );

}
