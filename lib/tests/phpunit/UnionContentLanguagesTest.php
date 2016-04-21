<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\UnionContentLanguages;

/**
 * @covers Wikibase\Lib\UnionContentLanguages
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class UnionContentLanguagesTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideTestGetLanguages
	 */
	public function testGetLanguages( ContentLanguages $a, ContentLanguages $b, array $expected ) {
		$contentLanguages = new UnionContentLanguages( $a, $b );
		$result = $contentLanguages->getLanguages();

		$this->assertSame( $expected, $result );
	}

	public function provideTestGetLanguages() {
		$empty = new StaticContentLanguages( [] );
		$one = new StaticContentLanguages( array( 'one' ) );
		$two = new StaticContentLanguages( array( 'one', 'two' ) );
		$otherTwo = new StaticContentLanguages( array( 'three', 'four' ) );

		return array(
			array( $empty, $empty, [] ),
			array( $empty, $one, array( 'one' ) ),
			array( $one, $empty, array( 'one' ) ),
			array( $one, $two, array( 'one', 'two' ) ),
			array( $two, $one, array( 'one', 'two' ) ),
			array( $two, $otherTwo, array( 'one', 'two', 'three', 'four' ) ),
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
		$empty = new StaticContentLanguages( [] );
		$one = new StaticContentLanguages( array( 'one' ) );
		$two = new StaticContentLanguages( array( 'one', 'two' ) );
		$otherTwo = new StaticContentLanguages( array( 'three', 'four' ) );

		return array(
			array( $empty, $empty, 'one', false ),
			array( $empty, $one, 'one', true ),
			array( $empty, $one, 'two', false ),
			array( $two, $one, 'one', true ),
			array( $two, $one, 'two', true ),
			array( $two, $one, 'three', false ),
			array( $two, $otherTwo, 'one', true ),
			array( $two, $otherTwo, 'two', true ),
			array( $two, $otherTwo, 'three', true ),
			array( $two, $otherTwo, 'four', true ),
		);
	}

}
