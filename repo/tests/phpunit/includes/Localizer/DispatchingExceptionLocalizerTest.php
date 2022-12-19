<?php

namespace Wikibase\Repo\Tests\Localizer;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use ValueParsers\ParseException;
use Wikibase\Repo\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Repo\Localizer\GenericExceptionLocalizer;
use Wikibase\Repo\Localizer\MessageExceptionLocalizer;
use Wikibase\Repo\Localizer\ParseExceptionLocalizer;

/**
 * @covers \Wikibase\Repo\Localizer\DispatchingExceptionLocalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DispatchingExceptionLocalizerTest extends \PHPUnit\Framework\TestCase {

	public function provideGetExceptionMessage() {
		$localizers = [
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer(),
			'Exception' => new GenericExceptionLocalizer(),
		];

		return [
			'RuntimeException' => [
				new RuntimeException( 'Oops!' ),
				'wikibase-error-unexpected',
				[ 'Oops!' ],
				$localizers,
			],
			'ParseException' => [
				new ParseException( 'Blarg!' ),
				'wikibase-parse-error',
				[],
				$localizers,
			],
		];
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
		$localizers = [
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer(),
		];

		$this->expectException( InvalidArgumentException::class );

		$localizer = new DispatchingExceptionLocalizer( $localizers );
		$localizer->getExceptionMessage( new RuntimeException( 'oops!' ) );
	}

}
