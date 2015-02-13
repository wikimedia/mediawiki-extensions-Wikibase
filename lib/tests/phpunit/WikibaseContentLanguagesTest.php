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

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testHasLanguage( $expected, $languageCode ) {
		$wbContentLanguages = new WikibaseContentLanguages();
		$this->assertSame( $expected, $wbContentLanguages->hasLanguage( $languageCode ) );
	}

	public function languageCodeProvider() {
		return array(
			array( true, 'en' ),
			array( true, 'de' ),
			array( true, 'es' ),
			array( true, 'fr' ),
			array( true, 'nl' ),
			array( true, 'ru' ),
			array( true, 'zh' ),
			array( false, 'kittens' ),
		);
	}
}
