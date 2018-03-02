<?php

namespace Wikibase\Repo;

use Language;
use MWException;
use Wikibase\View\LanguageDirectionalityLookup;

/**
 * Service for looking up language directionalities based on MediaWiki's Language
 * class.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class MediaWikiLanguageDirectionalityLookup implements LanguageDirectionalityLookup {

	/**
	 * @see LanguageDirectionalityLookup::getDirectionality
	 *
	 * @param string $languageCode
	 *
	 * @return string|null 'ltr', 'rtl' or null if unknown
	 */
	public function getDirectionality( $languageCode ) {
		try {
			$lang = Language::factory( $languageCode );
		} catch ( MWException $ex ) {
			return null;
		}

		return $lang->getDir();
	}

}
