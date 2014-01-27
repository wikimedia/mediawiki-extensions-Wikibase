<?php

namespace Wikibase\Test\Validators;

use ValueValidators\Error;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Validators\ValidatorErrorLocalizer
 *
 * @license GPL 2+
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @author Daniel Kinzler
 */
class ValidatorErrorLocalizerTest extends \PHPUnit_Framework_TestCase {

	public static function provideGetErrorMessage() {
		return array(
			array( Error::newError( 'Bla bla' ) ),
			array( Error::newError( 'Bla bla', null, 'test', array( 'thingy' ) ) ),
		);
	}

	/**
	 * @dataProvider provideGetErrorMessage()
	 */
	public function testGetErrorMessage( $error ) {
		$localizer = new ValidatorErrorLocalizer( );
		$message = $localizer->getErrorMessage( $error );

		$this->assertInstanceOf( 'Message', $message );
	}

}