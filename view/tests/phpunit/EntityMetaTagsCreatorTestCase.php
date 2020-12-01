<?php

namespace Wikibase\View\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\View\EntityMetaTagsCreator;

/**
 * @group Wikibase
 *
 * @covers \Wikibase\View\EntityMetaTags
 * @license GPL-2.0-or-later
 */
abstract class EntityMetaTagsCreatorTestCase extends TestCase {

	abstract public function provideTestGetMetaTags();

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
