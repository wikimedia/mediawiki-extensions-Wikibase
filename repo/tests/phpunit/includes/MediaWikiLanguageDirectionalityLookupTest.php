<?php

namespace Wikibase\Repo\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;

/**
 * @covers \Wikibase\Repo\MediaWikiLanguageDirectionalityLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class MediaWikiLanguageDirectionalityLookupTest extends PHPUnit_Framework_TestCase {

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
		$lookup = new MediaWikiLanguageDirectionalityLookup();
		$this->assertSame( $expected, $lookup->getDirectionality( $languageCode ) );
	}

}
