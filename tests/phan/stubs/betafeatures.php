<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the BetaFeatures extension.
 * @codingStandardsIgnoreFile
 */

class BetaFeatures {
	/**
	 * @param User $user The user to check
	 * @param string $feature The key passed back to BetaFeatures
	 *     from the GetBetaFeaturePreferences hook
	 * @return bool
	 */
	static function isFeatureEnabled( $user, $feature ) {
	}
}
