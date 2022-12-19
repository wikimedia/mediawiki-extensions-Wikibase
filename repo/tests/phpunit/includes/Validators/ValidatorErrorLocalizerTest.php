<?php

namespace Wikibase\Repo\Tests\Validators;

use Message;
use Status;
use ValueFormatters\ValueFormatter;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @covers \Wikibase\Repo\Validators\ValidatorErrorLocalizer
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ValidatorErrorLocalizerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return ValueFormatter
	 */
	private function getMockFormatter() {
		$mock = $this->createMock( ValueFormatter::class );
		$mock->method( 'format' )
			->willReturnCallback(
				function ( $param ) {
					if ( is_array( $param ) ) {
						$param = implode( '|', $param );
					}

					return strval( $param );
				}
			);

		return $mock;
	}

	public function provideGetErrorMessage() {
		return [
			'simple' => [
				Error::newError( 'Bla bla' ),
				[],
			],
			'with params' => [
				Error::newError(
					'Bla bla',
					null,
					'test',
					[ 'thingy', [ 'a', 'b', 'c' ] ]
				),
				[ 'thingy', 'a|b|c' ],
			],
		];
	}

	/**
	 * @dataProvider provideGetErrorMessage()
	 */
	public function testGetErrorMessage( $error, array $params ) {
		$localizer = new ValidatorErrorLocalizer( $this->getMockFormatter() );
		$message = $localizer->getErrorMessage( $error );

		//TODO: check that messages for actual error codes exist
		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $params, $message->getParams() );
	}

	public function provideGetResultStatus() {
		return [
			[ Result::newSuccess() ],
			[ Result::newError( [] ) ],
			[ Result::newError( [ Error::newError( 'Bla bla' ) ] ) ],
			[ Result::newError( [
				Error::newError( 'Foo', null, 'too-long' ),
				Error::newError( 'Foo', null, 'too-short' ),
			] ) ],
		];
	}

	/**
	 * @dataProvider provideGetResultStatus()
	 */
	public function testGetResultStatus( Result $result ) {
		$localizer = new ValidatorErrorLocalizer( $this->getMockFormatter() );
		$status = $localizer->getResultStatus( $result );

		$this->assertInstanceOf( Status::class, $status );
		$this->assertEquals( $result->isValid(), $status->isOK(), 'isOK()' );

		$this->assertSame( count( $result->getErrors() ), count( $status->getErrorsArray() ), 'Error count:' );
	}

}
