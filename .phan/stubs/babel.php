<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the Babel extension.
 * @codingStandardsIgnoreFile
 */

class Babel {

	/**
	 * @param User $user
	 * @param string $level Minimal level as given in $wgBabelCategoryNames
	 *
	 * @return string[] List of language codes
	 */
	public static function getCachedUserLanguages( User $user, $level = null ) {
	}

}
