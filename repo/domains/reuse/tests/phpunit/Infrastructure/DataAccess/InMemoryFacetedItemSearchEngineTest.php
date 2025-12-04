<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\Domains\Reuse\Domain\Model\AndOperation;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;

/**
 * @covers \Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess\InMemoryFacetedItemSearchEngine
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class InMemoryFacetedItemSearchEngineTest extends TestCase {

	/**
	 * @dataProvider searchProvider
	 */
	public function testSearch(
		array $items,
		PropertyValueFilter|AndOperation $filter,
		array $expectedResults
	): void {
		$search = new InMemoryFacetedItemSearchEngine();

		foreach ( $items as $item ) {
			$search->addItem( $item );
		}

		$this->assertEquals(
			$expectedResults,
			$search->search( $filter )
		);
	}

	public static function searchProvider(): Generator {
		$item1Id = new ItemId( 'Q1' );
		$item2Id = new ItemId( 'Q321' );
		$property1Id = new NumericPropertyId( 'P1' );
		$property2Id = new NumericPropertyId( 'P2' );

		yield 'no items returns empty array' => [
			'items' => [],
			'filter' => new PropertyValueFilter( $property1Id, null ),
			'expectedResults' => [],
		];

		yield 'property filter without value returns matching item' => [
			'items' => [
				NewItem::withId( $item1Id )
					->andStatement( NewStatement::noValueFor( $property1Id ) )
					->andStatement( NewStatement::noValueFor( $property2Id ) )
					->build(),
			],
			'filter' => new PropertyValueFilter( $property1Id, null ),
			'expectedResults' => [ new ItemSearchResult( $item1Id ) ],
		];

		$statement = NewStatement::forProperty( $property1Id )->withValue( new EntityIdValue( $item2Id ) )->build();
		yield 'property filter with non-matching value returns empty array' => [
			'items' => [
				NewItem::withId( $item1Id )->andStatement( $statement )->build(),
				NewItem::withId( $item2Id )->andStatement( NewStatement::noValueFor( $property1Id ) )->build(),
			],
			'filter' => new PropertyValueFilter( $property1Id, 'stringValue' ),
			'expectedResults' => [],
		];

		$stringValue = 'stringValue';
		$statementWithStringValue = NewStatement::forProperty( $property1Id )->withValue( $stringValue )->build();

		yield 'property filter with string value returns matching items' => [
			'items' => [
				NewItem::withId( $item1Id )->andStatement( $statementWithStringValue )->build(),
				NewItem::withId( $item2Id )->andStatement( $statementWithStringValue )->build(),
			],
			'filter' => new PropertyValueFilter( $property1Id, $stringValue ),
			'expectedResults' => [ new ItemSearchResult( $item1Id ), new ItemSearchResult( $item2Id ) ],
		];

		yield 'nested filters without value returns matching item' => [
			'items' => [
				NewItem::withId( $item1Id )
					->andStatement( NewStatement::noValueFor( $property1Id ) )
					->andStatement( NewStatement::noValueFor( $property2Id ) )
					->build(),
				NewItem::withId( $item2Id )->andStatement( NewStatement::noValueFor( $property1Id ) )->build(),
			],
			'filter' => new AndOperation( [
				new PropertyValueFilter( $property1Id, null ),
				new AndOperation( [ new PropertyValueFilter( $property2Id, null ) ] ),
			] ),
			'expectedResults' => [ new ItemSearchResult( $item1Id ) ],
		];

		$itemIdValue = 'Q3';
		$statement = NewStatement::forProperty( $property2Id )
			->withValue( new EntityIdValue( new ItemId( $itemIdValue ) ) )
			->build();
		yield 'nested filters with item value returns matching item' => [
			'items' => [
				NewItem::withId( $item1Id )
					->andStatement( NewStatement::noValueFor( $property1Id ) )
					->andStatement( $statement )
					->build(),
				NewItem::withId( $item2Id )->andStatement( NewStatement::noValueFor( $property1Id ) )->build(),
			],
			'filter' => new AndOperation( [
				new PropertyValueFilter( $property1Id, null ),
				new AndOperation( [ new PropertyValueFilter( $property2Id, $itemIdValue ) ] ),
			] ),
			'expectedResults' => [
				new ItemSearchResult( $item1Id ),
			],
		];
	}
}
