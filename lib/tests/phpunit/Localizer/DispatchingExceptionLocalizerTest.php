<?php

namespace Wikibase\Test;

use Exception;
use RuntimeException;
use ValueParsers\ParseException;
use Wikibase\Lib\Localizer\MessageExceptionLocalizer;
use Wikibase\Lib\Localizer\ParseExceptionLocalizer;
use Wikibase\Lib\Localizer\DispatchingExceptionLocalizer;

/**
 * @covers Wikibase\Lib\Localizer\DispatchingExceptionLocalizer
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DispatchingExceptionLocalizerTest extends \PHPUnit_Framework_TestCase {

	public function provideGetExceptionMessage() {
		return array(
			'RuntimeException' => array( new RuntimeException( 'Oops!' ), 'wikibase-error-unexpected', array( 'Oops!' ) ),
			'ParseException' => array( new ParseException( 'Blarg!' ), 'wikibase-parse-error', array() )
		);
	}

	private function getLocalizers() {
		return array(
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer()
		);
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, $expectedParams ) {
		$localizer = new DispatchingExceptionLocalizer( $this->getLocalizers() );

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

}
