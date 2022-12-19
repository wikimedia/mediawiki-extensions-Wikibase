<?php

namespace Wikibase\Repo\Tests\Localizer;

use Exception;
use Wikibase\Lib\MessageException;
use Wikibase\Repo\Localizer\MessageExceptionLocalizer;

/**
 * @covers \Wikibase\Repo\Localizer\MessageExceptionLocalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MessageExceptionLocalizerTest extends \PHPUnit\Framework\TestCase {

	public function provideGetExceptionMessage() {
		$exception = new MessageException(
			'wikibase-entitydata-storage-error',
			[ 'Q1', 123 ],
			'a message'
		);

		return [
			'MessageException' => [
				$exception,
				'wikibase-entitydata-storage-error',
				[ 'Q1', 123 ],
			],
		];
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
