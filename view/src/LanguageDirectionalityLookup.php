<?php

namespace Wikibase\View;

/**
 * Returns the directionality of a language
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
interface LanguageDirectionalityLookup {

	/**
	 * @param string $languageCode
	 *
	 * @return string|null 'ltr', 'rtl' or null if unknown
	 */
	public function getDirectionality( $languageCode );

}
