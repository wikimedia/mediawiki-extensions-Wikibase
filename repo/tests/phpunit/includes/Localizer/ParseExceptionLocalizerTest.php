<?php

namespace Wikibase\Repo\Tests\Localizer;

use Exception;
use ValueParsers\ParseException;
use Wikibase\Repo\Localizer\ParseExceptionLocalizer;

/**
 * @covers \Wikibase\Repo\Localizer\ParseExceptionLocalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParseExceptionLocalizerTest extends \PHPUnit\Framework\TestCase {

	public function provideGetExceptionMessage() {
		return [
			'ParseException' => [ new ParseException( 'Blarg!' ), 'wikibase-parse-error', [] ],
		];
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, array $expectedParams ) {
		$localizer = new ParseExceptionLocalizer();

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

}
