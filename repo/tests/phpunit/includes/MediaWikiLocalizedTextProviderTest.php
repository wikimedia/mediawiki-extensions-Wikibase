<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\View\RawMessageParameter;

/**
 * @covers \Wikibase\Repo\MediaWikiLocalizedTextProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class MediaWikiLocalizedTextProviderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider mediaWikiLocalizedTextProviderProvider
	 */
	public function testGet( $messageKey, $params, $expectedValue ) {
		$this->assertEquals(
			$expectedValue,
			$this->newEnglishMediaWikiLocalizedTextProvider()->get( $messageKey, $params )
		);
	}

	public function mediaWikiLocalizedTextProviderProvider() {
		yield 'message param without markup' => [
			'messageKey' => 'parentheses',
			'params' => [ 'VALUE' ],
			'expectedValue' => '(VALUE)',
		];

		yield 'param with markup' => [
			'messageKey' => 'parentheses',
			'params' => [ '<b>hi</b>' ],
			'expectedValue' => '(<b>hi</b>)',
		];
	}

	/**
	 * @dataProvider escapedMessageProvider
	 */
	public function testGetEscaped( $messageKey, $params, $expectedValue ) {
		$this->assertEquals(
			$expectedValue,
			$this->newEnglishMediaWikiLocalizedTextProvider()->getEscaped( $messageKey, $params )
		);
	}

	public function escapedMessageProvider() {
		yield 'message param without markup' => [
			'messageKey' => 'parentheses',
			'params' => [ 'VALUE' ],
			'expectedValue' => '(VALUE)',
		];

		yield 'param with unsafe html' => [
			'messageKey' => 'parentheses',
			'params' => [ '<script>alert("hi")</script>' ],
			'expectedValue' => '(&lt;script&gt;alert(&quot;hi&quot;)&lt;/script&gt;)',
		];

		yield 'raw parameter that is not escaped' => [
			'messageKey' => 'parentheses',
			'params' => [ new RawMessageParameter( '<b>hi</b>' ) ],
			'expectedValue' => '(<b>hi</b>)',
		];
	}

	public function testHas() {
		$this->assertTrue( $this->newEnglishMediaWikiLocalizedTextProvider()->has( 'parentheses' ) );
	}

	public function testGetLanguageOf() {
		$this->assertEquals(
			'en',
			$this->newEnglishMediaWikiLocalizedTextProvider()->getLanguageOf( 'parentheses' )
		);
	}

	private function newEnglishMediaWikiLocalizedTextProvider() {
		return new MediaWikiLocalizedTextProvider(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' )
		);
	}

}
