<?php

namespace Wikibase\Repo;

use ExtensionRegistry;
use MediaWiki\Babel\Babel;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\Lib\UserLanguageLookup;

/**
 * Service for looking up the languages understood by a user.
 *
 * The current implementation relies on the Babel extension, but that may change.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 * @author Marius Hoch
 */
class BabelUserLanguageLookup implements UserLanguageLookup {

	/**
	 * Local caching since calling the Babel extension can be expensive.
	 *
	 * @var array[]
	 */
	private $babelLanguages = [];

	/**
	 * @param User $user The current user.
	 *
	 * @return string[] List of language codes in the users Babel box.
	 */
	protected function getBabelLanguages( User $user ) {
		$key = $user->getId();
		// Lazy initialisation
		if ( !isset( $this->babelLanguages[$key] ) ) {
			// If the extension is installed, grab the languages from the user's Babel box
			if ( ExtensionRegistry::getInstance()->isLoaded( 'Babel' ) && $user->isRegistered() ) {
				$this->babelLanguages[$key] = Babel::getCachedUserLanguages( $user );
			} else {
				$this->babelLanguages[$key] = [];
			}
		}

		return $this->babelLanguages[$key];
	}

	/**
	 * Returns a list of languages the user specified in addition to the non-optional interface
	 * language.
	 * Note: This can contain language codes not actually valid to MediaWiki or valid at all.
	 *
	 * @param User $user The current user.
	 *
	 * @return string[] Which language codes the user specified.
	 */
	public function getUserSpecifiedLanguages( User $user ) {
		// TODO: If Universal Language Selector (ULS) supports setting additional/alternative
		// languages, these should be used in addition or instead of Babel (also needs API support).

		$languages = $this->getBabelLanguages( $user );

		// All languages in MediaWiki are lower-cased, while Babel doesn't enforce
		// that for regions.
		$languages = array_map( 'strtolower', $languages );

		return $languages;
	}

	/**
	 * Collects all languages from all user settings we can reach at this point, in order of
	 * preference, duplicates stripped:
	 * 1. The interface language from the user's settings
	 * 2. All languages in the user's Babel box
	 * Note: This can contain language codes not actually valid to MediaWiki or valid at all.
	 *
	 * @param User $user The current user.
	 *
	 * @return string[] List of all the user's language codes.
	 */
	public function getAllUserLanguages( User $user ) {
		$languages = [];
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		// Start with the user's UI language
		$userLanguage = $userOptionsLookup->getOption( $user, 'language' );
		if ( $userLanguage !== null ) {
			$languages[] = $userLanguage;
		}

		$userSpecifiedLanguages = $this->getUserSpecifiedLanguages( $user );
		if ( !empty( $userSpecifiedLanguages ) ) {
			$languages = array_merge( $languages, $userSpecifiedLanguages );
			$languages = array_values( array_unique( $languages ) );
		}

		return $languages;
	}

}
