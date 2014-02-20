<?php

namespace Wikibase;

use User;

/**
 * Service for looking up the languages understood by a user.
 *
 * The current implementation relies on the Babel extension, but
 * that may change.
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
	private $user;

	/**
	 * Local caching since calling the Babel extension may be expensive
	 *
	 * @var string[]
	 */
	private $babelLanguages;

	/**
	 * @param User $user the current user
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * @return string[] List of language codes in the users Babel box
	 */
	private function getBabelLanguages() {
		// Lazy initialisation
		if ( $this->babelLanguages === null ) {
			// if the Babel extension is installed, grab the languages from the user's babel box
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
	 * @return bool if the user does have a Babel box
	 */
	public function hasSpecifiedAlternativeLanguages() {
		$babelLanguages = $this->getBabelLanguages();
		return !empty( $babelLanguages );
	}

	public function getAllUserLanguages() {
		wfProfileIn( __METHOD__ );

		$languages = array();

		// start with the user's UI language
		$userLanguage = $this->user->getOption( 'language' );
		if ( $userLanguage !== null ) {
			$languages[] = $userLanguage;
		}

		$languages = array_merge( $languages, $this->getBabelLanguages() );
		$languages = array_unique( $languages );

		wfProfileOut( __METHOD__ );
		return $languages;
	}

	/**
	 * Returns the languages desired by the user, in order of preference.
	 *
	 * @param string[] $skip a list of language codes to skip.
	 *
	 * @return string[] a list of language codes
	 */
	public function getExtraUserLanguages( array $skip ) {
		return array_diff( $this->getAllUserLanguages(), $skip );
	}

}
