<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use GraphQL\GraphQL;
use MediaWiki\Site\MediaWikiSite;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\WbReuse;

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
	private static MediaWikiSite $sitelinkSite;
	private const ALLOWED_SITELINK_SITES = [ 'examplewiki', 'otherwiki' ];
	private const CUSTOM_ENTITY_DATA_TYPE = 'test-type';

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	/**
	 * @dataProvider searchProvider
	 */
	public function testQuery( string $query, array $expectedResult ): void {
		$entityLookup = new InMemoryEntityLookup();
		foreach ( self::$items as $item ) {
			$entityLookup->addEntity( $item );
		}

		$this->assertEquals(
			$expectedResult,
			$this->newGraphQLService( $entityLookup )->query( $query )
		);
	}

	public function searchProvider(): Generator {
		$itemProperty = $this->createProperty( 'wikibase-item' );
		$statementValueItemEnLabel = 'statement value item';
		$itemUsedAsStatementValue = $this->createItem( NewItem::withLabel( 'en', $statementValueItemEnLabel ) );

		$statementWithItemValue = NewStatement::forProperty( $itemProperty->getId() )
			->withSomeGuid()
			->withValue( $itemUsedAsStatementValue->getId() )
			->build();

		// TODO expect this item in search results
		$this->createItem(
			NewItem::withLabel( 'en', 'some label' )
			->andStatement( $statementWithItemValue )
		);

		yield 'simple searchItems query without value' => [
			"{  searchItems( query: { property: \"{$itemProperty->getId()}\" } ) { id } }",
			[ 'data' => [ 'searchItems' => [] ] ], // TODO: actual search results
		];

		yield 'simple searchItems query with value' => [
			"{  searchItems( query: {
				property: \"{$itemProperty->getId()}\",
				value: \"{$itemUsedAsStatementValue->getId()}\"
			} ) { id } }",
			[ 'data' => [ 'searchItems' => [] ] ], // TODO: actual search results
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

	private function newGraphQLService( EntityLookup $entityLookup ): GraphQLService {
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );

		return WbReuse::getGraphQLService();
	}
}
