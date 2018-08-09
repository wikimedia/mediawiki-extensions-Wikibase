<?php

namespace Wikibase\View\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\View\EntityMetaTagsCreator;

/**
 * @group Wikibase
 *
 * @covers \Wikibase\View\EntityMetaTags
 */
abstract class EntityMetaTagsCreatorTestCase extends TestCase {
	use \PHPUnit4And6Compat;

	public function provideTestGetMetaTags() {
		// Not implemented in the abstract class
	}

	/**
	 * @dataProvider provideTestGetMetaTags
	 */
	public function testGetMetaTags(
		EntityMetaTagsCreator $entityMetaTags,
		EntityDocument $entity,
		array $expectedTags
	) {
		$outputTags = $entityMetaTags->getMetaTags( $entity );
		$this->assertEquals( $expectedTags, $outputTags );
	}

}
