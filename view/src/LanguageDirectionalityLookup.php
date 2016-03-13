<?php

namespace Wikibase\View;

/**
 * Returns the directionality of a language
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
interface LanguageDirectionalityLookup {

	/**
	 * @param string $languageCode
	 *
	 * @return string|null 'ltr', 'rtl' or null if unknown
	 */
	public function getDirectionality( $languageCode );

}
