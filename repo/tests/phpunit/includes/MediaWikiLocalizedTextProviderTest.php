<?php

namespace Wikibase\Repo\Tests;

use Language;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;

/**
 * @covers \Wikibase\Repo\MediaWikiLocalizedTextProvider
 *
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class MediaWikiLocalizedTextProviderTest extends \PHPUnit\Framework\TestCase {

	public function mediaWikiLocalizedTextProviderProvider() {
		return [
			[
				new MediaWikiLocalizedTextProvider( Language::factory( 'en' ) ),
				true,
				'($1)',
				'en'
			]
		];
	}

	/**
	 * @dataProvider mediaWikiLocalizedTextProviderProvider
	 */
	public function testGet( MediaWikiLocalizedTextProvider $localizedTextProvider, $has, $content, $languageCode ) {
		$this->assertEquals( $localizedTextProvider->has( 'parentheses' ), $has );
		$this->assertEquals( $localizedTextProvider->get( 'parentheses' ), $content );
		$this->assertEquals( $localizedTextProvider->getLanguageOf( 'parentheses' ), $languageCode );
	}

}
