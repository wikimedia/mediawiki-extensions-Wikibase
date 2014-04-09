<?php

namespace Wikibase\Test\Validators;

use Status;
use ValueValidators\Error;
use ValueValidators\Result;
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
		$localizer = new ValidatorErrorLocalizer();
		$message = $localizer->getErrorMessage( $error );

		//TODO: check that messages for actual error codes exist
		$this->assertInstanceOf( 'Message', $message );
	}

	public static function provideGetResultStatus() {
		return array(
			array( Result::newSuccess() ),
			array( Result::newError( array() ) ),
			array( Result::newError( array( Error::newError( 'Bla bla' ) ) ) ),
			array( Result::newError( array(
				Error::newError( 'Foo' ),
				Error::newError( 'Bar' ),
			) ) ),
		);
	}

	/**
	 * @dataProvider provideGetResultStatus()
	 */
	public function testGetResultStatus( Result $result ) {
		$localizer = new ValidatorErrorLocalizer();
		$status = $localizer->getResultStatus( $result );

		$this->assertInstanceOf( 'Status', $status );
		$this->assertEquals( $result->isValid(), $status->isOk(), 'isOK()' );

		$this->assertEquals( count( $result->getErrors() ), count( $status->getErrorsArray() ), 'Error count:' );
	}

}