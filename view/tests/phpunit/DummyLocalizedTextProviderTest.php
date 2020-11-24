<?php

namespace Wikibase\View\Tests;

use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\RawMessageParameter;

/**
 * @covers \Wikibase\View\DummyLocalizedTextProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class DummyLocalizedTextProviderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider dummyLocalizedTextProviderProvider
	 */
	public function testGet( $messageKey, $params, $expectedValue ) {
		$this->assertEquals(
			$expectedValue,
			( new DummyLocalizedTextProvider() )->get( $messageKey, $params )
		);
	}

	public function dummyLocalizedTextProviderProvider() {
		yield [
			'messageKey' => 'parentheses',
			'params' => [ 'VALUE' ],
			'expectedValue' => '(parentheses: VALUE)',
		];

		yield [
			'messageKey' => 'some-message-key',
			'params' => [ 'foo', '<bar />' ],
			'expectedValue' => '(some-message-key: foo, <bar />)',
		];
	}

	/**
	 * @dataProvider escapedMessageProvider
	 */
	public function testGetEscaped( $messageKey, $params, $expectedValue ) {
		$this->assertEquals(
			$expectedValue,
			( new DummyLocalizedTextProvider() )->getEscaped( $messageKey, $params )
		);
	}

	public function escapedMessageProvider() {
		yield [
			'messageKey' => 'parentheses',
			'params' => [ 'VALUE' ],
			'expectedValue' => '(parentheses: VALUE)',
		];

		yield [
			'messageKey' => 'some-message-key',
			'params' => [ 'foo', '<bar />' ],
			'expectedValue' => '(some-message-key: foo, &lt;bar /&gt;)',
		];

		yield [
			'messageKey' => 'some-message-key',
			'params' => [ 'foo', new RawMessageParameter( '<bar />' ) ],
			'expectedValue' => '(some-message-key: foo, <bar />)',
		];
	}

	public function testHas() {
		$this->assertTrue( ( new DummyLocalizedTextProvider() )->has( 'some-message-key' ) );
	}

	public function testGetLanguageOf() {
		$this->assertEquals(
			'qqx',
			( new DummyLocalizedTextProvider() )->getLanguageOf( 'some-message-key' )
		);
	}

}
