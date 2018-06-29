<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
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

		$this->assertEquals(
			$extractor->extractEntityIds( $entity ),
			$expectedExtractedIds
		);
	}

	public function statementsAndExtractedIdsProvider() {
		yield 'no statements' => [ [], [] ];

		yield 'two statements, one referencing an item' => [
			[
				new Statement( new PropertyValueSnak(
					new PropertyId( 'P23' ),
					new EntityIdValue( new ItemId( 'Q42' ) )
				) ),
				new Statement( new PropertyValueSnak(
					new PropertyId( 'P321' ),
					new StringValue( 'foo' )
				) )
			],
			[ new PropertyId( 'P23' ), new ItemId( 'Q42' ), new PropertyId( 'P321' ) ]
		];

		yield 'statement with qualifiers' => [
			[ new Statement(
				new PropertyValueSnak( new PropertyId( 'P789' ), new StringValue( 'meow' ) ),
				new SnakList( [
					new PropertyValueSnak(
						new PropertyId( 'P666' ),
						new EntityIdValue( new ItemId( 'Q666' ) )
					)
				] )
			) ],
			[ new PropertyId( 'P789' ), new PropertyId( 'P666' ), new ItemId( 'Q666' ) ]
		];
	}

}
