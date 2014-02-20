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
 */
class UserLanguageLookup {

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * Local caching since calling the Babel extension can be expensive.
	 *
	 * @var string[]
	 */
	private $babelLanguages;

	/**
	 * @param User $user The current user.
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * @return string[] List of language codes in the users Babel box.
	 */
	protected function getBabelLanguages() {
		// Lazy initialisation
		if ( $this->babelLanguages === null ) {
			// If the extension is installed, grab the languages from the user's Babel box
			if ( class_exists( 'Babel' ) && !$this->user->isAnon() ) {
				$this->babelLanguages = \Babel::getUserLanguages( $this->user );
			}
			else {
				$this->babelLanguages = array();
			}
		}
		return $this->babelLanguages;
	}

	/**
	 * Returns if and which languages the user specified in addition to the non-optional interface
	 * language.
	 *
	 * @return null|string[] If and which language codes the user specified.
	 */
	public function getUserSpecifiedLanguages() {
		// TODO: If Universal Language Selector (ULS) supports setting additional/alternative
		// languages, these should be used in addition or instead of Babel.
		$babelLanguages = $this->getBabelLanguages();
		return empty( $babelLanguages ) ? null : $babelLanguages;
	}

	/**
	 * Returns true if the user does have additional languages specified in addition to the
	 * non-optional interface language, or explicitly specified to not have additional languages.
	 *
	 * @return bool If the user specified languages.
	 */
	public function hasSpecifiedAlternativeLanguages() {
		return $this->getUserSpecifiedLanguages() !== null;
	}

	/**
	 * Collects all languages from all user settings we can reach at this point, in order of
	 * preference, duplicates stripped:
	 * 1. The interface language from the user's settings
	 * 2. All languages in the user's Babel box
	 *
	 * @return string[] List of all the user's language codes.
	 */
	public function getAllUserLanguages() {
		wfProfileIn( __METHOD__ );

		$languages = array();

		// Start with the user's UI language
		$userLanguage = $this->user->getOption( 'language' );
		if ( $userLanguage !== null ) {
			$languages[] = $userLanguage;
		}

		$userSpecifiedLanguages = $this->getUserSpecifiedLanguages();
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
	 * @param string[] $skip List of language codes to skip.
	 *
	 * @return string[] List of language codes.
	 */
	public function getExtraUserLanguages( array $skip ) {
		return array_diff( $this->getAllUserLanguages(), $skip );
	}

}
