<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @covers Wikibase\Lib\StaticContentLanguages
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class StaticContentLanguagesTest extends PHPUnit_Framework_TestCase {

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
			array( [] ),
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
			array( [], 'one', false ),
			array( array( 'one' ), 'two', false ),
			array( array( 'one' ), 'one', true ),
			array( array( 'one', 'two' ), 'two', true ),
			array( array( 'one', 'two' ), 'three', false ),
		);
	}

}
