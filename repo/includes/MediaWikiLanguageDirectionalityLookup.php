<?php

namespace Wikibase\Repo;

use Language;
use Wikibase\View\LanguageDirectionalityLookup;

/**
 * Service for looking up language directionalities based on MediaWiki's Language
 * class.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class MediaWikiLanguageDirectionalityLookup implements LanguageDirectionalityLookup {

	/**
	 * @see LanguageDirectionalityLookup::getDirectionality
	 */
	public function getDirectionality( $languageCode ) {
		return Language::factory( $languageCode )->getDir();
	}

}
