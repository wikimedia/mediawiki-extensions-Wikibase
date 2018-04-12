<?php

namespace Wikibase\Repo\Tests\Localizer;

use Exception;
use PHPUnit4And6Compat;
use ValueFormatters\ValueFormatter;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\Localizer\ChangeOpValidationExceptionLocalizer;

/**
 * @covers Wikibase\Repo\Localizer\ChangeOpValidationExceptionLocalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangeOpValidationExceptionLocalizerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function provideGetExceptionMessage() {
		$result0 = Result::newError( [] );
		$result1 = Result::newError( [
			Error::newError( 'Eeek!', null, 'too-long', [ 8 ] ),
		] );
		$result2 = Result::newError( [
			Error::newError( 'Eeek!', null, 'too-long', [ [ 'eekwiki', 'Eek' ] ] ),
			Error::newError( 'Foo!', null, 'too-short', [ [ 'foowiki', 'Foo' ] ] ),
		] );

		return [
			'ChangeOpValidationException(0)' => [
				new ChangeOpValidationException( $result0 ),
				'wikibase-validator-invalid',
				[]
			],
			'ChangeOpValidationException(1)' => [
				new ChangeOpValidationException( $result1 ),
				'wikibase-validator-too-long',
				[ '8' ]
			],
			'ChangeOpValidationException(2)' => [
				new ChangeOpValidationException( $result2 ),
				'wikibase-validator-too-long',
				[ 'eekwiki|Eek' ]
			],
		];
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, array $expectedParams ) {
		$formatter = $this->getMockFormatter();
		$localizer = new ChangeOpValidationExceptionLocalizer( $formatter );

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

	/**
	 * @return ValueFormatter
	 */
	private function getMockFormatter() {
		$mock = $this->getMock( ValueFormatter::class );
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

}
