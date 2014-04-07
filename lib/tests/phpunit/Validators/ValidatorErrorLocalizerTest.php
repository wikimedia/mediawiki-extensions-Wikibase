<?php

namespace Wikibase\Test\Validators;

use Message;
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
			array( Error::newError( 'Bla bla', null, 'malformed-value', array( 'thingy' ) ) ),
		);
	}

	/**
	 * @dataProvider provideGetErrorMessage()
	 */
	public function testGetErrorMessage( $error ) {
		$localizer = new ValidatorErrorLocalizer();
		$message = $localizer->getErrorMessage( $error );

		$this->assertInstanceOf( 'Message', $message );
		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
	}

	public static function provideGetErrorStatus() {
		return array(
			array( array() ),
			array( array( Error::newError( 'Bla bla' ) ) ),
			array( array( Error::newError( 'Bla bla', null, 'malformed-value', array( 'thingy' ) ) ) ),
			array( array(
				Error::newError( 'Bla bla', null, 'too-long', array( 8 ) ),
				Error::newError( 'Bla bla', null, 'too-short', array( 8 ) ),
			) ),
		);
	}

	/**
	 * @dataProvider provideGetErrorStatus()
	 */
	public function testGetErrorStatus( $errors ) {
		$localizer = new ValidatorErrorLocalizer();
		$status = $localizer->getErrorStatus( $errors );

		$this->assertEquals( empty( $errors ), $status->isOK(), 'isOK()' );

		$messages = $status->getErrorsArray();
		$this->assertEquals( count( $errors ), count( $messages ), 'There should be one message per error.' );

		foreach ( $messages as $args ) {
			$key = array_shift( $args );
			$message = wfMessage( $key, $args );

			$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		}
	}
}