<?php

namespace Wikibase\Test;

use Exception;
use RuntimeException;
use ValueFormatters\ValueFormatter;
use ValueParsers\ParseException;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Internationalisation\WikibaseExceptionLocalizer;

/**
 * @covers Wikibase\Lib\Internationalisation\WikibaseExceptionLocalizer
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
		);
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, $expectedParams ) {
		$localizer = new WikibaseExceptionLocalizer();

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

}
