<?php

namespace Wikibase\Lib\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\MediaWikiContentLanguages;

/**
 * @covers Wikibase\Lib\MediaWikiContentLanguages
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class MediaWikiContentLanguagesTest extends PHPUnit_Framework_TestCase {

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
