<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\TermCacheKeyBuilder;

/**
 * @covers \Wikibase\Lib\Store\TermCacheKeyBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermCacheKeyBuilderTest extends TestCase {

	use TermCacheKeyBuilder;

	/**
	 * @dataProvider cacheKeyParamsProvider
	 */
	public function testBuildCacheKey( $entity, $revision, $language, $termType, $expected ) {
		$this->assertSame(
			$expected,
			$this->buildCacheKey( $entity, $revision, $language, $termType )
		);
	}

	public function cacheKeyParamsProvider() {
		yield [ new ItemId( 'Q123' ), 777, 'en', 'label', 'Q123_777_en_label' ];
		yield [ new PropertyId( 'P666' ), 789, 'de', 'alias', 'P666_789_de_alias' ];
	}

}
