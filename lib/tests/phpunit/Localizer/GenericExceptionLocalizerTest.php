<?php

namespace Wikibase\Test;

use Exception;
use RuntimeException;
use Wikibase\Lib\Localizer\GenericExceptionLocalizer;

/**
 * @covers Wikibase\Lib\Localizer\GenericExceptionLocalizer
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GenericExceptionLocalizerTest extends \PHPUnit_Framework_TestCase {

	public function provideGetExceptionMessage() {
		return array(
			'RuntimeException' => array(
				new RuntimeException( 'Oops!' ),
				'wikibase-error-unexpected',
				array( 'Oops!' )
			)
		);
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, $expectedParams ) {
		$localizer = new GenericExceptionLocalizer();

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

}
