<?php

namespace Wikibase;

use User;

/**
 * Service for looking up the languages understood by a user.
 *
 * The current implementation relies on the Babel extension, but that may change.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 * @author Marius Hoch
 */
class UserLanguageLookup {

	/**
	 * Local caching since calling the Babel extension can be expensive.
	 *
	 * @var array[]
	 */
	private $babelLanguages = array();

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
			if ( class_exists( 'Babel' ) && !$user->isAnon() ) {
				$this->babelLanguages[$key] = \Babel::getUserLanguages( $user );
			}
			else {
				$this->babelLanguages[$key] = array();
			}
		}

		return $this->babelLanguages[$key];
	}

	/**
	 * Returns a list of languages the user specified in addition to the non-optional interface
	 * language.
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

		$supportedLanguages = Utils::getLanguageCodes();
		$languages = array_intersect( $languages, $supportedLanguages );
		$languages = array_values( $languages ); // Reindex

		return $languages;
	}

	/**
	 * Returns true if the user does have additional languages specified in addition to the
	 * non-optional interface language, or explicitly specified to not have additional languages.
	 *
	 * @param User $user The current user.
	 *
	 * @return bool If the user specified languages.
	 */
	public function hasSpecifiedLanguages( User $user ) {
		$userSpecifiedLanguages = $this->getUserSpecifiedLanguages( $user );
		return !empty( $userSpecifiedLanguages );
	}

	/**
	 * Collects all languages from all user settings we can reach at this point, in order of
	 * preference, duplicates stripped:
	 * 1. The interface language from the user's settings
	 * 2. All languages in the user's Babel box
	 *
	 * @param User $user The current user.
	 *
	 * @return string[] List of all the user's language codes.
	 */
	public function getAllUserLanguages( User $user ) {
		wfProfileIn( __METHOD__ );

		$languages = array();

		// Start with the user's UI language
		$userLanguage = $user->getOption( 'language' );
		if ( $userLanguage !== null ) {
			$languages[] = $userLanguage;
		}

		$userSpecifiedLanguages = $this->getUserSpecifiedLanguages( $user );
		if ( !empty( $userSpecifiedLanguages ) ) {
			$languages = array_merge( $languages, $userSpecifiedLanguages );
			$languages = array_unique( $languages );
		}

		wfProfileOut( __METHOD__ );
		return $languages;
	}

	/**
	 * Returns the languages desired by the user, in order of preference.
	 *
	 * @param User $user The current user.
	 * @param string[] $skip List of language codes to skip.
	 *
	 * @return string[] List of language codes.
	 */
	public function getExtraUserLanguages( User $user, array $skip ) {
		return array_diff( $this->getAllUserLanguages( $user ), $skip );
	}

}
