<?php

namespace Wikibase\Lib\Store;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
interface LabelLookupFactory {

	/**
	 * @param {string[]|string} $languageSpec
	 *
	 * @return LabelLookup
	 */
	public function getLabelLookup( $languageSpec );

}
