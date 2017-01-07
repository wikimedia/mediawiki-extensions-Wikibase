<?php

namespace Wikibase\Repo\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;

/**
 * @covers Wikibase\Repo\MediaWikiLocalizedTextProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class MediaWikiLocalizedTextProviderTest extends PHPUnit_Framework_TestCase {

	public function mediaWikiLocalizedTextProviderProvider() {
		return [
			[
				new MediaWikiLocalizedTextProvider( 'en' ),
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
