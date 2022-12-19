<?php

namespace Wikibase\Repo\Tests\Localizer;

use Exception;
use RuntimeException;
use Wikibase\Repo\Localizer\GenericExceptionLocalizer;

/**
 * @covers \Wikibase\Repo\Localizer\GenericExceptionLocalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GenericExceptionLocalizerTest extends \PHPUnit\Framework\TestCase {

	public function provideGetExceptionMessage() {
		return [
			'RuntimeException' => [
				new RuntimeException( 'Oops!' ),
				'wikibase-error-unexpected',
				[ 'Oops!' ],
			],
		];
	}

	/**
	 * @dataProvider provideGetExceptionMessage
	 */
	public function testGetExceptionMessage( Exception $ex, $expectedKey, array $expectedParams ) {
		$localizer = new GenericExceptionLocalizer();

		$this->assertTrue( $localizer->hasExceptionMessage( $ex ) );

		$message = $localizer->getExceptionMessage( $ex );

		$this->assertTrue( $message->exists(), 'Message ' . $message->getKey() . ' should exist.' );
		$this->assertEquals( $expectedKey, $message->getKey(), 'Message key:' );
		$this->assertEquals( $expectedParams, $message->getParams(), 'Message parameters:' );
	}

}
