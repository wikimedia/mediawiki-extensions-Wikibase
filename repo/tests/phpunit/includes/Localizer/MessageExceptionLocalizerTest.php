<?php

namespace Wikibase\Test;

use Exception;
use Wikibase\Lib\MessageException;
use Wikibase\Repo\Localizer\MessageExceptionLocalizer;

/**
 * @covers Wikibase\Repo\Localizer\MessageExceptionLocalizer
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MessageExceptionLocalizerTest extends \PHPUnit_Framework_TestCase {

	public function provideGetExceptionMessage() {
		$exception = new MessageException(
			'wikibase-error-autocomplete-response',
			array( 'cannot autocomplete' ),
			'autocomplete error'
		);

		return array(
			'MessageException' => array(
				$exception,
				'wikibase-error-autocomplete-response',
				array( 'cannot autocomplete' )
			)
		);
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, array $expectedParams ) {
		$localizer = new MessageExceptionLocalizer();

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

}
