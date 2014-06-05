<?php

namespace Wikibase\Test;

use Exception;
use RuntimeException;
use ValueParsers\ParseException;
use Wikibase\Lib\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Lib\Localizer\GenericExceptionLocalizer;
use Wikibase\Lib\Localizer\MessageExceptionLocalizer;
use Wikibase\Lib\Localizer\ParseExceptionLocalizer;

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
		$localizers = array(
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer(),
			'Exception' => new GenericExceptionLocalizer()
		);

		return array(
			'RuntimeException' => array(
				new RuntimeException( 'Oops!' ),
				'wikibase-error-unexpected',
				array( 'Oops!' ),
				$localizers
			),
			'ParseException' => array(
				new ParseException( 'Blarg!' ),
				'wikibase-parse-error',
				array(),
				$localizers
			)
		);
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $exception, $expectedKey, $expectedParams,
		$localizers
	) {
		$localizer = new DispatchingExceptionLocalizer( $localizers );

		$this->assertTrue( $localizer->hasExceptionMessage( $exception ) );

		$message = $localizer->getExceptionMessage( $exception );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

	public function provideGetExceptionMessageThrowsException() {
		$localizers = array(
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer(),
		);

		$this->setExpectedException( 'InvalidArgumentException' );

		$localizer = new DispatchingExceptionLocalizer( $localizers );
		$localizer->getExceptionMessage( new RuntimeException( 'oops!' ) );
	}

}
