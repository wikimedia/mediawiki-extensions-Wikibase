<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DifferenceContentLanguages;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers Wikibase\Lib\DifferenceContentLanguages
 *
 * @uses Wikibase\Lib\StaticContentLanguages
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class DifferenceContentLanguagesTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideTestGetLanguages
	 */
	public function testGetLanguages( ContentLanguages $a, ContentLanguages $b, array $expected ) {
		$contentLanguages = new DifferenceContentLanguages( $a, $b );
		$result = $contentLanguages->getLanguages();

		$this->assertSame( $expected, $result );
	}

	public function provideTestGetLanguages() {
		$empty = new StaticContentLanguages( array() );
		$one = new StaticContentLanguages( array( 'one' ) );
		$two = new StaticContentLanguages( array( 'one', 'two' ) );
		$otherTwo = new StaticContentLanguages( array( 'three', 'four' ) );

		return array(
			array( $empty, $empty, array() ),
			array( $empty, $one, array() ),
			array( $one, $empty, array( 'one' ) ),
			array( $one, $two, array() ),
			array( $two, $one, array( 'two' ) ),
			array( $two, $otherTwo, array( 'one', 'two' ) ),
		);
	}

	/**
	 * @dataProvider provideTestHasLanguage
	 */
	public function testHasLanguage( ContentLanguages $a, ContentLanguages $b, $lang, $expected ) {
		$contentLanguages = new DifferenceContentLanguages( $a, $b );
		$result = $contentLanguages->hasLanguage( $lang );

		$this->assertSame( $expected, $result );
	}

	public function provideTestHasLanguage() {
		$empty = new StaticContentLanguages( array() );
		$one = new StaticContentLanguages( array( 'one' ) );
		$two = new StaticContentLanguages( array( 'one', 'two' ) );
		$otherTwo = new StaticContentLanguages( array( 'three', 'four' ) );

		return array(
			array( $empty, $empty, 'one', false ),
			array( $empty, $one, 'one', false ),
			array( $empty, $one, 'two', false ),
			array( $two, $one, 'one', false ),
			array( $two, $one, 'two', true ),
			array( $two, $one, 'three', false ),
			array( $two, $otherTwo, 'one', true ),
			array( $two, $otherTwo, 'two', true ),
			array( $two, $otherTwo, 'three', false ),
			array( $two, $otherTwo, 'four', false ),
		);
	}

}
