<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\PaginationCursorCodec;
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

	use SearchEnabledTestTrait;
	use PaginationCursorCodec;

	/** @var Property[] */
	private static array $properties = [];

	/** @var Item[] */
	private static array $items = [];

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->simulateSearchEnabled();
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
		$stringProperty = $this->createProperty( 'string' );
		$itemProperty = $this->createProperty( 'wikibase-item' );
		$itemUsedAsStatementValue = $this->createItem( NewItem::withLabel( 'en', 'value item' ) );

		$item = $this->createItem(
			NewItem::withLabel( 'en', 'some label' )
				->andDescription( 'en', 'some description' )
				->andStatement(
					NewStatement::forProperty( $itemProperty->getId() )
						->withSomeGuid()
						->withValue( $itemUsedAsStatementValue->getId() )
				)
				->andStatement( NewStatement::forProperty( $stringProperty->getId() )->withValue( 'potato' ) )
		);
		$item2 = $this->createItem(
			NewItem::withLabel( 'en', 'item 2' )
				->andStatement( NewStatement::someValueFor( $stringProperty->getId() )->withSomeGuid() )
		);
		$item3 = $this->createItem(
			NewItem::withLabel( 'en', 'item 3' )
				->andStatement( NewStatement::someValueFor( $stringProperty->getId() )->withSomeGuid() )
		);

		yield 'simple searchItems query without value' => [
			"{ searchItems( query: { property: \"{$itemProperty->getId()}\" } ) { edges { node { id } } } }",
			[ 'data' =>
				[ 'searchItems' =>
					[ 'edges' =>
						[
							[ 'node' => [ 'id' => $item->getId() ] ],
						],
					],
				],
			],
		];

		yield 'simple searchItems query with cursor' => [
			"{ searchItems( query: { property: \"{$itemProperty->getId()}\" } ) {
				 edges {
					node { id }
					cursor
				}
				 pageInfo {
					endCursor
					hasPreviousPage
					hasNextPage
					startCursor
				 }
			  } }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' => [ 'id' => $item->getId() ], 'cursor' => $this->encodeOffsetAsCursor( 1 ) ],
						],
						'pageInfo' => [
							'endCursor' => $this->encodeOffsetAsCursor( 1 ),
							'hasPreviousPage' => false,
							'hasNextPage' => false,
							'startCursor' => $this->encodeOffsetAsCursor( 1 ),
						],
					],
				],
			],
		];

		yield 'simple searchItems query with value' => [
			"{ searchItems( query: {
				property: \"{$itemProperty->getId()}\",
				value: \"{$itemUsedAsStatementValue->getId()}\"
			} ) { edges { node { id } } } }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' => [ 'id' => $item->getId() ] ],
						],
					],
				],
			],
		];

		yield 'searchItems with description in search results' => [
			"{  searchItems( query: { property: \"{$itemProperty->getId()}\" } ) {
				edges { node { description(languageCode: \"en\") } }
			} }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' =>
								[ 'description' => $item->getDescriptions()->getByLanguage( 'en' )->getText() ],
							],
						],
					],
				],
			],
		];

		yield 'searchItems with label in search result' => [
			"{  searchItems( query: {
				property: \"{$itemProperty->getId()}\",
			} ) { edges { node { label(languageCode: \"en\") } } } }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' =>
								[ 'label' => $item->getLabels()->getByLanguage( 'en' )->getText() ],
							],
						],
					],
				],
			],
		];

		yield 'pagination - with limit' => [
			"{  searchItems(
				query: { property: \"{$stringProperty->getId()}\" },
				first: 2
				) {
				edges { node { id } }
				pageInfo {
					endCursor
					hasPreviousPage
					hasNextPage
					startCursor
				}
			 } }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' => [ 'id' => $item->getId() ] ],
							[ 'node' => [ 'id' => $item2->getId() ] ],
						],
						'pageInfo' => [
							'endCursor' => $this->encodeOffsetAsCursor( 2 ),
							'hasPreviousPage' => false,
							'hasNextPage' => true,
							'startCursor' => $this->encodeOffsetAsCursor( 1 ),
						],
					],
				],
			],
		];

		$offset = $this->encodeOffsetAsCursor( 1 );
		yield 'pagination - with offset' => [
			"{  searchItems(
				query: { property: \"{$stringProperty->getId()}\" },
				after: \"{$offset}\"
			) {
				edges { node { id } }
				pageInfo {
					endCursor
					hasPreviousPage
					hasNextPage
					startCursor
				}
			  } }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' => [ 'id' => $item2->getId() ] ],
							[ 'node' => [ 'id' => $item3->getId() ] ],
						],
						'pageInfo' => [
							'endCursor' => $this->encodeOffsetAsCursor( 3 ),
							'hasPreviousPage' => true,
							'hasNextPage' => false,
							'startCursor' => $this->encodeOffsetAsCursor( 2 ),
						],
					],
				],
			],
		];

		$offset = $this->encodeOffsetAsCursor( 1 );
		yield 'pagination - with offset and limit' => [
			"{  searchItems(
				query: { property: \"{$stringProperty->getId()}\" },
				first: 1,
				after: \"{$offset}\"
			) {
				edges { node { id } }
				pageInfo {
					endCursor
					hasPreviousPage
					hasNextPage
					startCursor
				}
			 } }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' => [ 'id' => $item2->getId() ] ],
						],
						'pageInfo' => [
							'endCursor' => $this->encodeOffsetAsCursor( 2 ),
							'hasPreviousPage' => true,
							'hasNextPage' => true,
							'startCursor' => $this->encodeOffsetAsCursor( 2 ),
						],
					],
				],
			],
		];

		$property = $this->createProperty( 'string' );
		yield 'pagination - no results' => [
			"{ searchItems(query: { property: \"{$property->getId()}\" }) {
				pageInfo {
					startCursor
					endCursor
					hasNextPage
					hasPreviousPage
				}
			} }",
			[
				'data' => [
					'searchItems' => [
						'pageInfo' => [
							'endCursor' => null,
							'hasPreviousPage' => false,
							'hasNextPage' => false,
							'startCursor' => null,
						],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider errorsProvider
	 */
	public function testErrors( string $query, string $expectedErrorMessage ): void {
		$result = $this->newGraphQLService()->query( $query );

		$this->assertSame( $expectedErrorMessage, $result['errors'][0]['message'] );
	}

	public function errorsProvider(): Generator {
		yield 'rejects queries with more than one search' => [
			'{
			  s1: searchItems(query: { property: "P1" } ) { edges { node { id } } }
			  s2: searchItems(query: { property: "P2" } ) { edges { node { id } } }
			  s3: searchItems(query: { property: "P3" } ) { edges { node { id } } }
			}',
			'The query complexity is 200% over the limit.',
		];

		yield 'invalid search query: empty filter' => [
			'{
			  searchItems(query: {} ) { edges { node { id } } }
			}',
			"Invalid search query: Query filters must contain either an 'and' or a 'property' field",
		];

		yield 'invalid search query: empty "and"' => [
			'{
			  searchItems(query: { and: [] } ) { edges { node { id } } }
			}',
			"Invalid search query: 'and' fields must contain at least two elements",
		];

		$stringProperty = $this->createProperty( 'string' );
		yield 'invalid search query: "and" and "property"' => [
			"{
				searchItems(query: {
					and: [ { property: \"{$stringProperty->getId()}\" } ],
					property: \"{$stringProperty->getId()}\"
				} ) { edges { node { id } } }
			}",
			"Invalid search query: Filters must not contain both an 'and' and a 'property' field",
		];

		$unsupportedProperty = $this->createProperty( 'wikibase-property' );
		yield 'invalid search query: unsupported property data type' => [
			"{
			  searchItems(query: { property: \"{$unsupportedProperty->getId()}\" } ) { edges { node { id } } }
			}",
			"Invalid search query: Data type of Property '{$unsupportedProperty->getId()}' is not supported",
		];

		yield 'invalid "first" param - less than 1' => [
			"{
			  searchItems(query: { property: \"{$stringProperty->getId()}\" }, first: 0) { edges { node { id } } }
			}",
			'"first" must not be less than 1 or greater than ' . FacetedItemSearchRequest::MAX_LIMIT,
		];

		yield 'invalid "first" param - above the maximum' => [
			"{
			  searchItems(query: { property: \"{$stringProperty->getId()}\" }, first: 51) { edges { node { id } } }
			}",
			'"first" must not be less than 1 or greater than ' . FacetedItemSearchRequest::MAX_LIMIT,
		];

		yield 'invalid cursor: not an encoded int' => [
			"{
			  searchItems(query: { property: \"{$stringProperty->getId()}\" }, after: \"potato\") { edges { node { id } } }
			}",
			'"after" does not contain a valid cursor',
		];

		$cursor = $this->encodeOffsetAsCursor( -1 );
		yield 'invalid cursor: offset below min' => [
			"{
			  searchItems(query: { property: \"{$stringProperty->getId()}\" }, after: \"$cursor\") { edges { node { id } } }
			}",
			'"after" does not contain a valid cursor',
		];

		$cursor = $this->encodeOffsetAsCursor( FacetedItemSearchRequest::MAX_OFFSET + 1 );
		yield 'invalid cursor: offset above max' => [
			"{
			  searchItems(query: { property: \"{$stringProperty->getId()}\" }, after: \"$cursor\") { edges { node { id } } }
			}",
			'"after" does not contain a valid cursor',
		];
	}

	public function testHandlesSearchNotAvailable(): void {
		$this->simulateSearchEnabled( false );

		$expectedErrorMessage = 'Search is not available due to insufficient server configuration';
		$query = '{ searchItems( query: { property: "P1" } ) { edges { node { id } } } }';

		$result = $this->newGraphQLService()->query( $query );

		$this->assertSame( $expectedErrorMessage, $result['errors'][0]['message'] );
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

		$dataTypeLookup = new InMemoryDataTypeLookup();
		foreach ( self::$properties as $property ) {
			$dataTypeLookup->setDataTypeForProperty( $property->getId(), $property->getDataTypeId() );
		}
		$this->setService( 'WikibaseRepo.PropertyDataTypeLookup', $dataTypeLookup );

		return WbReuse::getGraphQLService();
	}

}
