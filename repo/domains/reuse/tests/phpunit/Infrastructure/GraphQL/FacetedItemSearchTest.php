<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\WbReuse;
use Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess\InMemoryFacetedItemSearchEngine;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FacetedItemSearchTest extends MediaWikiIntegrationTestCase {

	/** @var Property[] */
	private static array $properties = [];

	/** @var Item[] */
	private static array $items = [];

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	/**
	 * @dataProvider searchProvider
	 */
	public function testQuery( string $query, array $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newGraphQLService()->query( $query )
		);
	}

	public function searchProvider(): Generator {
		$itemProperty = $this->createProperty( 'wikibase-item' );
		$statementValueItemEnLabel = 'statement value item';
		$itemUsedAsStatementValue = $this->createItem( NewItem::withLabel( 'en', $statementValueItemEnLabel ) );

		$item = $this->createItem(
			NewItem::withLabel( 'en', 'some label' )
				->andDescription( 'en', 'some description' )
				->andStatement(
					NewStatement::forProperty( $itemProperty->getId() )
						->withSomeGuid()
						->withValue( $itemUsedAsStatementValue->getId() )
				)
		);

		yield 'simple searchItems query without value' => [
			"{ searchItems( query: { property: \"{$itemProperty->getId()}\" } ) { id } }",
			[ 'data' => [ 'searchItems' => [ [ 'id' => $item->getId() ] ] ] ],
		];

		yield 'simple searchItems query with value' => [
			"{ searchItems( query: {
				property: \"{$itemProperty->getId()}\",
				value: \"{$itemUsedAsStatementValue->getId()}\"
			} ) { id } }",
			[ 'data' => [ 'searchItems' => [ [ 'id' => $item->getId() ] ] ] ],
		];

		yield 'searchItems with description in search results' => [
			"{  searchItems( query: { property: \"{$itemProperty->getId()}\" } ) {
				description(languageCode: \"en\")
			} }",
			[
				'data' => [
					'searchItems' => [
						[ 'description' => $item->getDescriptions()->getByLanguage( 'en' )->getText() ],
					],
				],
			],
		];

		yield 'searchItems with label in search result' => [
			"{  searchItems( query: {
				property: \"{$itemProperty->getId()}\",
			} ) { label(languageCode: \"en\") } }",
			[ 'data' => [ 'searchItems' => [ [ 'label' => $item->getLabels()->getByLanguage( 'en' )->getText() ] ] ] ],
		];
	}

	/**
	 * @dataProvider errorsProvider
	 */
	public function testErrors( string $query, string $expectedErrorMessage ): void {
		$result = $this->newGraphQLService( new InMemoryEntityLookup() )->query( $query );

		$this->assertSame( $expectedErrorMessage, $result['errors'][0]['message'] );
	}

	public static function errorsProvider(): Generator {
		yield 'rejects queries with more than one search' => [
			'{
			  s1: searchItems(query: { property: "P1" } ) { id }
			  s2: searchItems(query: { property: "P2" } ) { id }
			  s3: searchItems(query: { property: "P3" } ) { id }
			}',
			'The query complexity is 200% over the limit.',
		];
	}

	private function createProperty( string $dataType, ?string $enLabel = null ): Property {
		// assign the ID here so that we don't have to worry about collisions
		$nextId = empty( self::$properties ) ? 'P1' : 'P' . $this->getNextNumericId( self::$properties );
		$property = new Property( new NumericPropertyId( $nextId ), null, $dataType );
		if ( $enLabel ) {
			$property->setLabel( 'en', $enLabel );
		}
		self::$properties[] = $property;

		return $property;
	}

	private function createItem( NewItem $newItem ): Item {
		// assign the ID here so that we don't have to worry about collisions
		$nextId = empty( self::$items ) ? 'Q1' : 'Q' . $this->getNextNumericId( self::$items );
		$item = $newItem->andId( $nextId )->build();
		self::$items[] = $item;

		return $item;
	}

	private function getNextNumericId( array $entities ): int {
		$latestEntity = $entities[array_key_last( $entities )];
		return (int)substr( $latestEntity->getId()->getSerialization(), 1 ) + 1;
	}

	private function newGraphQLService(): GraphQLService {
		$termLookup = new InMemoryPrefetchingTermLookup();
		$termLookup->setData( self::$items );
		$this->setService( 'WikibaseRepo.PrefetchingTermLookup', $termLookup );

		$entityLookup = new InMemoryEntityLookup();
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );

		$search = new InMemoryFacetedItemSearchEngine();
		$this->setService( 'WbReuse.FacetedItemSearchEngine', $search );

		foreach ( self::$items as $item ) {
			$entityLookup->addEntity( $item );
			$search->addItem( $item );
		}

		return WbReuse::getGraphQLService();
	}
}
