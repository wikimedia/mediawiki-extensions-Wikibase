<?php

namespace Wikibase\Lib\Test;

use Wikibase\Lib\WikibaseContentLanguages;

/**
 * @covers Wikibase\Lib\WikibaseContentLanguages
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseContentLanguagesTest extends \MediaWikiTestCase {

	public function testGetLanguages() {
		$wbContentLanguages = new WikibaseContentLanguages();
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
