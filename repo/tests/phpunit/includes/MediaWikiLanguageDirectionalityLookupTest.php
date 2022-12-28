<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;

/**
 * @covers \Wikibase\Repo\MediaWikiLanguageDirectionalityLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MediaWikiLanguageDirectionalityLookupTest extends \PHPUnit\Framework\TestCase {

	public function provideLanguageCodes() {
		return [
			'Known LTR language' => [ 'en', 'ltr' ],
			'Known RTL language' => [ 'fa', 'rtl' ],
			'Unknown code' => [ 'unknown', 'ltr' ],
			'Invalid code' => [ '<invalid>', null ],
		];
	}

	/**
	 * @dataProvider provideLanguageCodes
	 */
	public function testGetDirectionality( $languageCode, $expected ) {
		$lookup = new MediaWikiLanguageDirectionalityLookup(
			MediaWikiServices::getInstance()->getLanguageFactory(),
			MediaWikiServices::getInstance()->getLanguageNameUtils()
		);
		$this->assertSame( $expected, $lookup->getDirectionality( $languageCode ) );
	}

}
