<?php


namespace Wikibase\View\Tests;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\View\EntityMetaTags;

abstract class EntityMetaTagsTest extends \PHPUnit\Framework\TestCase {
	use \PHPUnit4And6Compat;

	/**
	 * @dataProvider provideTestGetMetaTags
	 */
	public function testGetMetaTags(
		EntityMetaTags $entityMetaTags,
		EntityDocument $entity,
		array $expectedTags
	) {
		$outputTags = $entityMetaTags->getMetaTags($entity);
		$this->assertEquals($expectedTags, $outputTags);
	}

}
