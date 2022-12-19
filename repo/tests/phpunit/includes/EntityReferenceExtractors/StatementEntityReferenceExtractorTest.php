<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\UnboundedQuantityValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;

/**
 * @covers \Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementEntityReferenceExtractorTest extends TestCase {
	private const UNIT_PREFIX = 'unit:';

	/**
	 * @dataProvider statementsAndExtractedIdsProvider
	 */
	public function testExtractEntityIds( $statements, $expectedExtractedIds ) {
		$entity = new Item( null, null, null, new StatementList( ...$statements ) );
		$extractor = new StatementEntityReferenceExtractor( $this->getMockEntityIdParser() );

		$this->assertEquals(
			$expectedExtractedIds,
			$extractor->extractEntityIds( $entity )
		);
	}

	public function statementsAndExtractedIdsProvider() {
		yield 'no statements' => [ [], [] ];

		yield 'two statements, one referencing an item' => [
			[
				new Statement( new PropertyValueSnak(
					new NumericPropertyId( 'P23' ),
					new EntityIdValue( new ItemId( 'Q42' ) )
				) ),
				new Statement( new PropertyValueSnak(
					new NumericPropertyId( 'P321' ),
					new StringValue( 'foo' )
				) ),
			],
			[ new NumericPropertyId( 'P23' ), new ItemId( 'Q42' ), new NumericPropertyId( 'P321' ) ],
		];

		yield 'statement with qualifiers' => [
			[ new Statement(
				new PropertyValueSnak( new NumericPropertyId( 'P789' ), new StringValue( 'meow' ) ),
				new SnakList( [
					new PropertyValueSnak(
						new NumericPropertyId( 'P666' ),
						new EntityIdValue( new ItemId( 'Q666' ) )
					),
				] )
			) ],
			[ new NumericPropertyId( 'P789' ), new NumericPropertyId( 'P666' ), new ItemId( 'Q666' ) ],
		];

		yield 'statements with item in quantity value' => [
			[
				new Statement( new PropertyValueSnak(
					new NumericPropertyId( 'P1' ),
					UnboundedQuantityValue::newFromNumber( 1, self::UNIT_PREFIX . 'Q21' )
				) ),
				new Statement( new PropertyValueSnak(
					new NumericPropertyId( 'P1' ),
					QuantityValue::newFromNumber( 1, self::UNIT_PREFIX . 'Q22' )
				) ),
			],
			[ new NumericPropertyId( 'P1' ), new ItemId( 'Q21' ), new ItemId( 'Q22' ) ],
		];
	}

	public function testSeparateIdsPerCall() {
		$p1 = new NumericPropertyId( 'P1' );
		$p2 = new NumericPropertyId( 'P2' );
		$statement1 = NewStatement::noValueFor( $p1 );
		$statement2 = NewStatement::noValueFor( $p2 );
		$item1 = NewItem::withStatement( $statement1 )->build();
		$item2 = NewItem::withStatement( $statement2 )->build();

		$extractor = new StatementEntityReferenceExtractor( $this->getMockEntityIdParser() );
		$ids1 = $extractor->extractEntityIds( $item1 );
		$ids2 = $extractor->extractEntityIds( $item2 );

		$this->assertSame( [ $p1 ], $ids1 );
		$this->assertSame( [ $p2 ], $ids2 );
	}

	private function getMockEntityIdParser() {
		$entityIdParser = $this->createMock( SuffixEntityIdParser::class );
		$entityIdParser->method( 'parse' )
			->willReturnCallback( function ( $id ) {
				return new ItemId(
					substr( $id, strlen( self::UNIT_PREFIX ) )
				);
			} );

		return $entityIdParser;
	}

}
