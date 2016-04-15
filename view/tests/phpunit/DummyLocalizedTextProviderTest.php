<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers Wikibase\View\DummyLocalizedTextProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class DummyLocalizedTextProviderTest extends PHPUnit_Framework_TestCase {

	public function dummyLocalizedTextProviderProvider() {
		return [
			[
				new DummyLocalizedTextProvider(),
				true,
				'(parentheses: VALUE)',
				'qqx'
			]
		];
	}

	/**
	 * @dataProvider dummyLocalizedTextProviderProvider
	 */
	public function testGet( DummyLocalizedTextProvider $localizedTextProvider, $has, $content, $languageCode ) {
		$this->assertEquals( $localizedTextProvider->has( 'parentheses' ), $has );
		$this->assertEquals( $localizedTextProvider->get( 'parentheses', [ 'VALUE' ] ), $content );
		$this->assertEquals( $localizedTextProvider->getLanguageOf( 'parentheses' ), $languageCode );
	}

}
