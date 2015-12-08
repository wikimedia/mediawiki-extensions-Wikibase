<?php

namespace Wikibase\Lib\Test;

use MediaWikiTestCase;
use Wikibase\Lib\MediaWikiContentLanguages;

/**
 * @covers Wikibase\Lib\MediaWikiContentLanguages
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class MediaWikiContentLanguagesTest extends MediaWikiTestCase {

	public function testGetLanguages() {
		$wbContentLanguages = new MediaWikiContentLanguages();
		$result = $wbContentLanguages->getLanguages();

		$this->assertInternalType( 'array', $result );

		// Just check for some langs
		$knownLangCodes = array( 'en', 'de', 'es', 'fr', 'nl', 'ru', 'zh' );
		$this->assertSame(
			$knownLangCodes,
			array_intersect( $knownLangCodes, $result )
		);
	}

}
