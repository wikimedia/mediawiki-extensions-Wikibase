<?php

namespace Wikibase\Lib\Test;

use ValueParsers\ParserOptions;
use Wikibase\Lib\MediaWikiNumberUnlocalizer;

/**
 * @covers Wikibase\Lib\MediaWikiNumberUnlocalizer
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MediaWikiNumberUnlocalizerTest extends \PHPUnit_Framework_TestCase {

	public function provideUnlocalize() {
		return array(
			array( '123,456.789', 'en', '123456.789' ),
			array( '123.456,789', 'de', '123456.789' ),
		);
	}

	/**
	 * @dataProvider provideUnlocalize
	 *
	 * @param $localized
	 * @param $lang
	 * @param $expected
	 */
	public function testUnlocalize( $localized, $lang, $expected ) {
		$unlocalizer = new MediaWikiNumberUnlocalizer();
		$options = new ParserOptions();

		$actual = $unlocalizer->unlocalize( $localized, $lang, $options );

		$this->assertEquals( $expected, $actual );
	}
}
