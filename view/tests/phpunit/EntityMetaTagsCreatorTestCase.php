<?php

namespace Wikibase\View\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @group Wikibase
 *
 * @covers \Wikibase\View\EntityMetaTags
 * @license GPL-2.0-or-later
 */
abstract class EntityMetaTagsCreatorTestCase extends TestCase {

	abstract public static function provideTestGetMetaTags();

	/**
	 * @dataProvider provideTestGetMetaTags
	 */
	public function testGetMetaTags(
		callable $entityMetaTagsFactory,
		callable $entityFactory,
		array $expectedTags
	) {
		$entityMetaTags = $entityMetaTagsFactory( $this );
		$entity = $entityFactory( $this );

		$outputTags = $entityMetaTags->getMetaTags( $entity );
		$this->assertEquals( $expectedTags, $outputTags );
	}

}
