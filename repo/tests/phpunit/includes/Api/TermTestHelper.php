<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class TermTestHelper {

	/**
	 * Return a string that is guaranteed to be longer than the given length.
	 *
	 * @param string $text Repeated to build the string.
	 * @param int|null $length Defaults to the configured maximum length for
	 * multilanguage strings (labels, descriptions, etc.).
	 */
	public static function makeOverlyLongString(
		string $text = "Test",
		int $length = null
	): string {
		if ( $length === null ) {
			$limits = WikibaseRepo::getSettings()
				->getSetting( 'string-limits' )['multilang'];
			$length = $limits['length'];
		}

		$rep = (int)ceil( ( $length + 1 ) / strlen( $text ) );
		$s = str_repeat( $text, $rep );

		return $s;
	}

}
