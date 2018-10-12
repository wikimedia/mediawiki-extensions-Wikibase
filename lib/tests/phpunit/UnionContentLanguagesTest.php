<?php

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\UnionContentLanguages;

/**
 * @covers \Wikibase\Lib\UnionContentLanguages
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class UnionContentLanguagesTest extends \PHPUnit\Framework\TestCase {

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
		$one = new StaticContentLanguages( [ 'one' ] );
		$two = new StaticContentLanguages( [ 'one', 'two' ] );
		$otherTwo = new StaticContentLanguages( [ 'three', 'four' ] );

		return [
			[ $empty, $empty, [] ],
			[ $empty, $one, [ 'one' ] ],
			[ $one, $empty, [ 'one' ] ],
			[ $one, $two, [ 'one', 'two' ] ],
			[ $two, $one, [ 'one', 'two' ] ],
			[ $two, $otherTwo, [ 'one', 'two', 'three', 'four' ] ],
		];
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
		$one = new StaticContentLanguages( [ 'one' ] );
		$two = new StaticContentLanguages( [ 'one', 'two' ] );
		$otherTwo = new StaticContentLanguages( [ 'three', 'four' ] );

		return [
			[ $empty, $empty, 'one', false ],
			[ $empty, $one, 'one', true ],
			[ $empty, $one, 'two', false ],
			[ $two, $one, 'one', true ],
			[ $two, $one, 'two', true ],
			[ $two, $one, 'three', false ],
			[ $two, $otherTwo, 'one', true ],
			[ $two, $otherTwo, 'two', true ],
			[ $two, $otherTwo, 'three', true ],
			[ $two, $otherTwo, 'four', true ],
		];
	}

}
