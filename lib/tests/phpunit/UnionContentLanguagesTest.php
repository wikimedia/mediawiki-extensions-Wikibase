<?php

namespace Wikibase\Lib\Test;

use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\UnionContentLanguages;

/**
 * @covers Wikibase\Lib\UnionContentLanguages
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class UnionContentLanguagesTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider provideTestGetLanguages
	 */
	public function testGetLanguages( ContentLanguages $a, ContentLanguages $b, array $expected ) {
		$contentLanguages = new UnionContentLanguages( $a, $b );
		$result = $contentLanguages->getLanguages();

		$this->assertSame( $expected, $result );
	}

	public function provideTestGetLanguages() {
		$empty = $this->getMockContentLanguages( array() );
		$one = $this->getMockContentLanguages( array( 'one' ) );
		$two = $this->getMockContentLanguages( array( 'one', 'two' ) );

		return array(
			array( $empty, $empty, array() ),
			array( $empty, $one, array( 'one' ) ),
			array( $one, $empty, array( 'one' ) ),
			array( $one, $two, array( 'one', 'two' ) ),
			array( $two, $one, array( 'one', 'two' ) ),
		);
	}

	/**
	 * @dataProvider provideTestHasLanguage
	 */
	public function testHasLanguage( ContentLanguages $a, ContentLanguages $b, $lang, $expected ) {
		$contentLanguages = new UnionContentLanguages( $a, $b );
		$result = $contentLanguages->hasLanguage( $lang );

		$this->assertSame( $expected, $result );
	}

	public function provideTestHasLanguage() {
		$empty = $this->getMockContentLanguages( array() );
		$one = $this->getMockContentLanguages( array( 'one' ) );
		$two = $this->getMockContentLanguages( array( 'one', 'two' ) );

		return array(
			array( $empty, $empty, 'one', false ),
			array( $empty, $one, 'one', true ),
			array( $empty, $one, 'two', false ),
			array( $two, $one, 'one', true ),
			array( $two, $one, 'two', true ),
			array( $two, $one, 'three', false ),
		);
	}

	private function getMockContentLanguages( $languages ) {
		$contentLanguages = $this->getMock( 'Wikibase\Lib\ContentLanguages' );
		$contentLanguages->expects( $this->any() )
			->method( 'getLanguages' )
			->will( $this->returnValue( $languages ) );
		return $contentLanguages;
	}

}
