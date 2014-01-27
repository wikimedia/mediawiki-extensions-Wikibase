<?php

namespace Wikibase\Test\Api;

use Wikibase\Settings;

/**
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class TermTestHelper {

	public static function makeOverlyLongString( $text = "Test", $length = null ) {
		if ( $length === null ) {
			$limits = Settings::get( 'multilang-limits' );
			$length = $limits['length'];
		}

		$rep = $length / strlen( $text ) + 1;
		$s = str_repeat( $text, $rep );

		return $s;
	}

}
