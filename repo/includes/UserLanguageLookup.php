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
 */
class UserLanguageLookup {

	/**
	 * Returns the languages desired by the user, in order of preference.
	 *
	 * @param User $user
	 * @param array $skip a list of language codes to skip.
	 *
	 * @return string[] a list of language codes
	 */
	public function getUserLanguages( User $user, $skip = array() ) {
		wfProfileIn( __METHOD__ );

		$languages = array();

		// start with the user's UI language
		$userLanguage = $user->getOption( 'language' );

		if ( $userLanguage !== null ) {
			$languages[] = $userLanguage;
		}

		// if the Babel extension is installed, grab the languages from the user's babel box
		if ( class_exists( 'Babel' ) && ( !$user->isAnon() ) ) {
			$languages = array_merge( $languages, \Babel::getUserLanguages( $user ) );
		}

		$languages = array_diff( $languages, $skip );
		$languages = array_unique( $languages );

		wfProfileOut( __METHOD__ );
		return $languages;
	}

}
