<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\UnboundedQuantityValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
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
	const UNIT_PREFIX = 'unit:';

	/**
	 * @dataProvider statementsAndExtractedIdsProvider
	 */
	public function testExtractEntityIds( $statements, $expectedExtractedIds ) {
		$entity = new Item( null, null, null, new StatementList( $statements ) );
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

		yield 'statements with item in quantity value' => [
			[
				new Statement( new PropertyValueSnak(
					1,
					UnboundedQuantityValue::newFromNumber( 1, self::UNIT_PREFIX . 'Q21' )
				) ),
				new Statement( new PropertyValueSnak(
					1,
					QuantityValue::newFromNumber( 1, self::UNIT_PREFIX . 'Q22' )
				) )
			],
			[ new PropertyId( 'P1' ), new ItemId( 'Q21' ), new ItemId( 'Q22' ) ],
		];
	}

	private function getMockEntityIdParser() {
		$entityIdParser = $this->getMockBuilder( SuffixEntityIdParser::class )
			->disableOriginalConstructor()
			->getMock();
		$entityIdParser->method( 'parse' )
			->willReturnCallback( function ( $id ) {
				return new ItemId(
					substr( $id, strlen( self::UNIT_PREFIX ) )
				);
			} );

		return $entityIdParser;
	}

}
