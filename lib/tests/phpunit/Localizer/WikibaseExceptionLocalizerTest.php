<?php

namespace Wikibase\Test;

use Exception;
use RuntimeException;
use ValueFormatters\ValueFormatter;
use ValueParsers\ParseException;
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
		return array(
			'RuntimeException' => array( new RuntimeException( 'Oops!' ), 'wikibase-error-unexpected', array( 'Oops!' ) ),
			'ParseException' => array( new ParseException( 'Blarg!' ), 'wikibase-parse-error', array() ),
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
