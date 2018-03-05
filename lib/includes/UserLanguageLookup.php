<?php

namespace Wikibase\Lib;

use User;

/**
 * Service for looking up the languages understood by a user.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 * @author Marius Hoch
 */
interface UserLanguageLookup {

	/**
	 * Returns a list of languages the user specified in addition to the non-optional interface
	 * language.
	 * Note: This can contain language codes not actually valid to MediaWiki or valid at all.
	 *
	 * @param User $user The current user.
	 *
	 * @return string[] Which language codes the user specified.
	 */
	public function getUserSpecifiedLanguages( User $user );

	/**
	 * Collects all languages from all user settings we can reach at this point.
	 * Note: This can contain language codes not actually valid to MediaWiki or valid at all.
	 *
	 * @param User $user The current user.
	 *
	 * @return string[] List of all the user's language codes.
	 */
	public function getAllUserLanguages( User $user );

}
