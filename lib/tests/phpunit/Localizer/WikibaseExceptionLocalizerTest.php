<?php

namespace Wikibase\Test;

use Exception;
use RuntimeException;
use ValueFormatters\ValueFormatter;
use ValueParsers\ParseException;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Localizer\WikibaseExceptionLocalizer;

/**
 * @covers Wikibase\Lib\Localizer\WikibaseExceptionLocalizer
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseExceptionLocalizerTest extends \PHPUnit_Framework_TestCase {

	public function provideGetExceptionMessage() {
		$result0 = Result::newError( array() );
		$result1 = Result::newError( array(
			Error::newError( 'Eeek!', null, 'too-long', array( 8 ) ),
		) );
		$result2 = Result::newError( array(
			Error::newError( 'Eeek!', null, 'too-long', array( array( 'eekwiki', 'Eek' ) ) ),
			Error::newError( 'Foo!', null, 'too-short', array( array( 'foowiki', 'Foo' ) ) ),
		) );

		return array(
			'RuntimeException' => array( new RuntimeException( 'Oops!' ), 'wikibase-error-unexpected', array( 'Oops!' ) ),
			'ParseException' => array( new ParseException( 'Blarg!' ), 'wikibase-parse-error', array() ),

			'ChangeOpValidationException(0)' => array( new ChangeOpValidationException( $result0 ), 'wikibase-validator-invalid', array() ),
			'ChangeOpValidationException(1)' => array( new ChangeOpValidationException( $result1 ), 'wikibase-validator-too-long', array( '8' ) ),
			'ChangeOpValidationException(2)' => array( new ChangeOpValidationException( $result2 ), 'wikibase-validator-too-long', array( 'eekwiki|Eek' ) ),
		);
	}

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

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, $expectedParams ) {
		$localizer = new WikibaseExceptionLocalizer( $this->getMockFormatter() );

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

}
