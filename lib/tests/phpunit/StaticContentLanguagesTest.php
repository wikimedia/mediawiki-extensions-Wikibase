<?php

namespace Wikibase\Lib\Test;

use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers Wikibase\Lib\StaticContentLanguages
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class StaticContentLanguagesTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider provideTestGetLanguages
	 */
	public function testGetLanguages( $expected ) {
		$contentLanguages = new StaticContentLanguages( $expected );
		$result = $contentLanguages->getLanguages();

		$this->assertInternalType( 'array', $result );

		$this->assertSame( $expected, $result );
	}

	public function provideTestGetLanguages() {
		return array(
			array( array() ),
			array( array( 'one' ) ),
			array( array( 'one', 'two' ) ),
		);
	}

	/**
	 * @dataProvider provideTestHasLanguage
	 */
	public function testHasLanguage( $in, $lang, $expected ) {
		$contentLanguages = new StaticContentLanguages( $in );
		$result = $contentLanguages->hasLanguage( $lang );

		$this->assertSame( $expected, $result );
	}

	public function provideTestHasLanguage() {
		return array(
			array( array(), 'one', false ),
			array( array( 'one' ), 'two', false ),
			array( array( 'one' ), 'one', true ),
			array( array( 'one', 'two' ), 'two', true ),
			array( array( 'one', 'two' ), 'three', false ),
		);
	}

}
