<?php

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\MediaWikiContentLanguages;

/**
 * @covers \Wikibase\Lib\MediaWikiContentLanguages
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class MediaWikiContentLanguagesTest extends \PHPUnit\Framework\TestCase {

	public function testGetLanguages() {
		$wbContentLanguages = new MediaWikiContentLanguages();
		$result = $wbContentLanguages->getLanguages();

		$this->assertIsArray( $result );

		$missing = array_diff( [
			// Just check for some langs
			'de',
			'en',
			'es',
			'fr',
			'nl',
			'ru',
			'zh',
			// https://gerrit.wikimedia.org/r/599684
			'ami',
			'lld',
			'smn',
			'trv',
		], $result );
		$this->assertCount( 0, $missing, implode( ', ', $missing ) );
	}

}
