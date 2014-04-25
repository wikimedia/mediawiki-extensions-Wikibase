<?php

namespace Wikibase\Test\Validators;

use ValueFormatters\ValueFormatter;
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

	/**
	 * @return ValueFormatter
	 */
	private function getMockFormatter() {
		$mock = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$mock->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback(
				function ( $param ) {
					if ( is_array( $param ) ) {
						$param = implode( '|', $param );
					}

					return strval( $param );
				}
			) );

		return $mock;
	}

	public static function provideGetErrorMessage() {
		return array(
			'simple' => array(
				Error::newError( 'Bla bla' ),
				array()
			),
			'with params' => array(
				Error::newError(
					'Bla bla',
					null,
					'test',
					array( 'thingy', array( 'a', 'b', 'c' ) )
				),
				array( 'thingy', 'a|b|c' )
			),
		);
	}

	/**
	 * @dataProvider provideGetErrorMessage()
	 */
	public function testGetErrorMessage( $error, $params ) {
		$localizer = new ValidatorErrorLocalizer( $this->getMockFormatter() );
		$message = $localizer->getErrorMessage( $error );

		//TODO: check that messages for actual error codes exist
		$this->assertInstanceOf( 'Message', $message );
		$this->assertEquals( $params, $message->getParams() );
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
		$localizer = new ValidatorErrorLocalizer( $this->getMockFormatter() );
		$status = $localizer->getResultStatus( $result );

		$this->assertInstanceOf( 'Status', $status );
		$this->assertEquals( $result->isValid(), $status->isOk(), 'isOK()' );

		$this->assertEquals( count( $result->getErrors() ), count( $status->getErrorsArray() ), 'Error count:' );
	}

}
