<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;

/**
 * @covers Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementEntityReferenceExtractorTest extends TestCase {

	/**
	 * @dataProvider statementsAndExtractedIdsProvider
	 */
	public function testExtractEntityIds( $statements, $expectedExtractedIds ) {
		$entity = new Item( null, null, null, new StatementList( $statements ) );
		$extractor = new StatementEntityReferenceExtractor();

		$this->assertSame(
			$extractor->extractEntityIds( $entity ),
			$expectedExtractedIds
		);
	}

	public function statementsAndExtractedIdsProvider() {
		yield 'no statements' => [ [], [] ];
	}

}
