<?php

namespace Wikibase\Repo\Tests\Parsers;

/**
 * Awful hack: several tests extend DataValues test classes. DataValues works on PHPUnit4 only, hence
 * it uses setExpectedException (amongst other things). But Wikibase requires PHP 7.2+ and PHPUnit6+,
 * where setExpectedException doesn't exist anymore. This was resolved by using the PHPUnit4And6Compat
 * trait provided by core, but that's deprecated as of 1.34.
 *
 * @todo Get rid of this trait as soon as DataValues moves away from withered PHPUnit.
 * @license GPL-2.0-or-later
 */
trait PHPUnit4CompatTrait {
	public function setExpectedException( $name, $message = '', $code = null ) {
		if ( $name !== null ) {
			$this->expectException( $name );
		}
		if ( $message !== '' ) {
			$this->expectExceptionMessage( $message );
		}
		if ( $code !== null ) {
			$this->expectExceptionCode( $code );
		}
	}
}
