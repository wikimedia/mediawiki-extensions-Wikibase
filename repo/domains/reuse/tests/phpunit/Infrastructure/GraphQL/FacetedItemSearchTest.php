<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
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

	public static function searchProvider(): Generator {
		$stringProperty = self::createProperty( 'string' );
		$itemProperty = self::createProperty( 'wikibase-item' );
		$otherItemProperty = self::createProperty( 'wikibase-item' );
		$itemUsedAsStatementValue = self::createItem( NewItem::withLabel( 'en', 'value item' ) );

		$item = self::createItem(
			NewItem::withLabel( 'en', 'some label' )
				->andDescription( 'en', 'some description' )
				->andStatement(
					NewStatement::forProperty( $itemProperty->getId() )
						->withSomeGuid()
						->withValue( $itemUsedAsStatementValue->getId() )
				)
				->andStatement(
					NewStatement::forProperty( $stringProperty->getId() )
						->withSomeGuid()
						->withValue( 'potato' )
				)
		);
		$item2 = self::createItem(
			NewItem::withLabel( 'en', 'item 2' )
				->andStatement( NewStatement::someValueFor( $stringProperty->getId() )->withSomeGuid() )
				->andStatement( NewStatement::someValueFor( $otherItemProperty->getId() )->withSomeGuid() )
		);
		$item3 = self::createItem(
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
							[ 'node' => [ 'id' => $item->getId() ], 'cursor' => self::encodeOffsetAsCursor( 1 ) ],
						],
						'pageInfo' => [
							'endCursor' => self::encodeOffsetAsCursor( 1 ),
							'hasPreviousPage' => false,
							'hasNextPage' => false,
							'startCursor' => self::encodeOffsetAsCursor( 1 ),
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

		yield 'searchItems with "and"' => [
			"{ searchItems( query: {
				and: [
					{ property: \"{$itemProperty->getId()}\", value: \"{$itemUsedAsStatementValue->getId()}\" },
					{ property: \"{$stringProperty->getId()}\", value: \"potato\" }
				]
			} ) { edges { node { id } } } }",
			[ 'data' => [ 'searchItems' => [ 'edges' => [
				[ 'node' => [ 'id' => $item->getId() ] ],
			] ] ] ],
		];

		yield 'searchItems with "or"' => [
			"{ searchItems( query: {
				or: [
					{ property: \"{$itemProperty->getId()}\" },
					{ property: \"{$otherItemProperty->getId()}\" }
				]
			} ) { edges { node { id } } } }",
			[ 'data' => [ 'searchItems' => [ 'edges' => [
				[ 'node' => [ 'id' => $item->getId() ] ],
				[ 'node' => [ 'id' => $item2->getId() ] ],
			] ] ] ],
		];

		yield 'searchItems with "not"' => [
			"{ searchItems( query: {
				not:
					{ property: \"{$stringProperty->getId()}\" }
			} ) { edges { node { id } } } }",
			[ 'data' => [ 'searchItems' => [ 'edges' => [
				[ 'node' => [ 'id' => $itemUsedAsStatementValue->getId() ] ],
			] ] ] ],
		];

		yield 'searchItems with "and" and "or"' => [
			"{ searchItems( query: {
				and: [
					{ property: \"{$itemProperty->getId()}\" }
					{ or: [
						{ property: \"{$stringProperty->getId()}\" },
						{ property: \"{$otherItemProperty->getId()}\" }
					] }
				]
			} ) { edges { node { id } } } }",
			[ 'data' => [ 'searchItems' => [ 'edges' => [
				[ 'node' => [ 'id' => $item->getId() ] ],
			] ] ] ],
		];

		yield 'searchItems with "and" and "not"' => [
			"{ searchItems( query: {
				and: [
					{ property: \"{$itemProperty->getId()}\" }
					{ not:
						{ property: \"{$otherItemProperty->getId()}\", value: \"{$itemUsedAsStatementValue->getId()}\" },
					}
				]
			} ) { edges { node { id } } } }",
			[ 'data' => [ 'searchItems' => [ 'edges' => [
				[ 'node' => [ 'id' => $item->getId() ] ],
			] ] ] ],
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

		yield 'searchItems with labelWithLanguageFallback in search result' => [
			"{  searchItems( query: {
				property: \"{$itemProperty->getId()}\",
			} ) { edges { node { labelWithLanguageFallback(languageCode: \"ko\") { languageCode value } } } } }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' =>
								[ 'labelWithLanguageFallback' => [
									'languageCode' => 'en',
									'value' => $item->getLabels()->getByLanguage( 'en' )->getText(),
								] ],
							],
						],
					],
				],
			],
		];

		yield 'searchItems with statement value in search result' => [
			"{  searchItems( query: {
				property: \"{$itemProperty->getId()}\",
			} ) { edges { node { statements(propertyId: \"{$itemProperty->getId()}\") {
			   value {
            		... on ItemValue {
              			id
            		}
          		}
			} } } } }",
			[
				'data' => [
					'searchItems' => [
						'edges' => [
							[ 'node' =>
								[ 'statements' => [
									[
										'value' => [ 'id' => $itemUsedAsStatementValue->getId() ],
									],
								] ],
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
							'endCursor' => self::encodeOffsetAsCursor( 2 ),
							'hasPreviousPage' => false,
							'hasNextPage' => true,
							'startCursor' => self::encodeOffsetAsCursor( 1 ),
						],
					],
				],
			],
		];

		$offset = self::encodeOffsetAsCursor( 1 );
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
							'endCursor' => self::encodeOffsetAsCursor( 3 ),
							'hasPreviousPage' => true,
							'hasNextPage' => false,
							'startCursor' => self::encodeOffsetAsCursor( 2 ),
						],
					],
				],
			],
		];

		$offset = self::encodeOffsetAsCursor( 1 );
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
							'endCursor' => self::encodeOffsetAsCursor( 2 ),
							'hasPreviousPage' => true,
							'hasNextPage' => true,
							'startCursor' => self::encodeOffsetAsCursor( 2 ),
						],
					],
				],
			],
		];

		$property = self::createProperty( 'string' );
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

	public static function errorsProvider(): Generator {
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
			'Invalid search query: Query filters must contain either an operator field or a property/value condition',
		];

		yield 'invalid search query: empty "and"' => [
			'{
			  searchItems(query: { and: [] } ) { edges { node { id } } }
			}',
			"Invalid search query: 'and' fields must contain at least two elements",
		];

		$stringProperty = self::createProperty( 'string' );
		yield 'invalid search query: "and" and "property"' => [
			"{
				searchItems(query: {
					and: [ { property: \"{$stringProperty->getId()}\" } ],
					property: \"{$stringProperty->getId()}\"
				} ) { edges { node { id } } }
			}",
			'Invalid search query: Query filters must only contain a single operator field or a property/value condition',
		];

		$stringProperty = self::createProperty( 'string' );
		yield 'invalid search query: "not" and "property"' => [
			"{
				searchItems(query: {
					not: { property: \"{$stringProperty->getId()}\" },
					property: \"{$stringProperty->getId()}\"
				} ) { edges { node { id } } }
			}",
			'Invalid search query: Query filters must only contain a single operator field or a property/value condition',
		];

		$stringProperty = self::createProperty( 'string' );
		yield 'invalid search query: "and" nested' => [
			"{
				searchItems(query: {
					and: [ { property: \"{$stringProperty->getId()}\" },
						{
							and: [{ property: \"{$stringProperty->getId()}\"}]
						}
					]
				}) { edges { node { id } } }
			}",
			'Field "and" is not defined by type "AndOperationCondition".',
		];

		$unsupportedProperty = self::createProperty( 'wikibase-property' );
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

		$cursor = self::encodeOffsetAsCursor( -1 );
		yield 'invalid cursor: offset below min' => [
			"{
			  searchItems(query: { property: \"{$stringProperty->getId()}\" }, after: \"$cursor\") { edges { node { id } } }
			}",
			'"after" does not contain a valid cursor',
		];

		$cursor = self::encodeOffsetAsCursor( FacetedItemSearchRequest::MAX_OFFSET + 1 );
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

	private static function createProperty( string $dataType, ?string $enLabel = null ): Property {
		// assign the ID here so that we don't have to worry about collisions
		$nextId = empty( self::$properties ) ? 'P1' : 'P' . self::getNextNumericId( self::$properties );
		$property = new Property( new NumericPropertyId( $nextId ), null, $dataType );
		if ( $enLabel ) {
			$property->setLabel( 'en', $enLabel );
		}
		self::$properties[] = $property;

		return $property;
	}

	private static function createItem( NewItem $newItem ): Item {
		// assign the ID here so that we don't have to worry about collisions
		$nextId = empty( self::$items ) ? 'Q1' : 'Q' . self::getNextNumericId( self::$items );
		$item = $newItem->andId( $nextId )->build();
		self::$items[] = $item;

		return $item;
	}

	private static function getNextNumericId( array $entities ): int {
		$latestEntity = $entities[array_key_last( $entities )];
		return (int)substr( $latestEntity->getId()->getSerialization(), 1 ) + 1;
	}

	private function newGraphQLService(): GraphQLService {
		$termLookup = new InMemoryPrefetchingTermLookup();
		$termLookup->setData( self::$items );
		$this->setService( 'WikibaseRepo.PrefetchingTermLookup', $termLookup );

		$entityLookup = new InMemoryEntityLookup();
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );

		$revisionLookup = $this->createStub( EntityRevisionLookup::class );
		$revisionLookup->method( 'getLatestRevisionId' )->willReturnCallback(
			fn( ItemId $id ) => LatestRevisionIdResult::concreteRevision( 1, '20260101001122' )
		);
		$this->setService( 'WikibaseRepo.EntityRevisionLookup', $revisionLookup );

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
