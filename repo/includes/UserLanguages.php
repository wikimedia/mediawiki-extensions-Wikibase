<?php

namespace Wikibase;
use Language;
use User;

/**
 * Utility for determining the languages understood by a user.
 *
 * The current implementation relies on the Babel extension, but
 * that may change.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UserLanguages {

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @param User $user
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * Returns the languages desired by the user, in order of preference.
	 *
	 * @param array $skip a list of language codes to skip.
	 *
	 * @return string[] a list of language codes
	 */
	public function getLanguages( $skip = array() ) {
		wfProfileIn( __METHOD__ );

		$languages = array();

		// start with the user's UI language
		$userLanguage = $this->user->getOption( 'language' );

		if ( $userLanguage !== null ) {
			$languages[] = $userLanguage;
		}

		// if the Babel extension is installed, grab the languages from the user's babel box
		if ( class_exists( 'Babel' ) && ( !$this->user->isAnon() ) ) {
			$languages = array_merge( $languages, \Babel::getUserLanguages( $this->user ) );
		}

		$languages = array_diff( $languages, $skip );
		$languages = array_unique( $languages );

		wfProfileOut( __METHOD__ );
		return $languages;
	}

}
