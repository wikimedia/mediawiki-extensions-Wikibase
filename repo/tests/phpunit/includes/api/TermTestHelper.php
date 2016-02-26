<?php

namespace Wikibase\Test\Repo\Api;

use Wikibase\Repo\WikibaseRepo;

/**
 * @license GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class TermTestHelper {

	public static function makeOverlyLongString( $text = "Test", $length = null ) {
		if ( $length === null ) {
			$limits = WikibaseRepo::getDefaultInstance()->
				getSettings()->getSetting( 'multilang-limits' );
			$length = $limits['length'];
		}

		$rep = $length / strlen( $text ) + 1;
		$s = str_repeat( $text, $rep );

		return $s;
	}

}
