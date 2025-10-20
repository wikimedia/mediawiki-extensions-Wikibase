<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use DataValues\StringValue;
use Generator;
use GraphQL\GraphQL;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\SiteLookup;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\WbReuse;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GraphQLServiceTest extends MediaWikiIntegrationTestCase {

	private static Item $item1;
	private static Item $item2;
	private static Item $statementValueItem;
	private static Item $qualifierValueItem;
	private static Property $statementProperty;
	private static Property $qualifierProperty;
	private static MediaWikiSite $sitelinkSite;
	private const ALLOWED_SITELINK_SITES = [ 'examplewiki', 'otherwiki' ];

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testQuery( string $query, array $expectedResult ): void {
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( self::$item1 );
		$entityLookup->addEntity( self::$item2 );

		$siteIdProvider = $this->createStub( SiteLinkGlobalIdentifiersProvider::class );
		$siteIdProvider->method( 'getList' )->willReturn( self::ALLOWED_SITELINK_SITES );

		$termLookup = new InMemoryPrefetchingTermLookup();
		$termLookup->setData( [
			self::$statementProperty,
			self::$qualifierProperty,
			self::$statementValueItem,
			self::$qualifierValueItem,
		] );

		$this->assertEquals(
			$expectedResult,
			$this->newGraphQLService(
				$entityLookup,
				new HashSiteStore( [ self::$sitelinkSite ] ),
				$siteIdProvider,
				$termLookup,
			)->query( $query )
		);
	}

	public function queryProvider(): Generator {
		$statementValueItemEnLabel = 'statement value item';
		$itemValueItemId = 'Q4';
		self::$statementValueItem = NewItem::withId( $itemValueItemId )
			->andLabel( 'en', $statementValueItemEnLabel )
			->build();

		$qualifierValueItemEnLabel = 'statement value item';
		$qualifierValueItemId = 'Q5';
		self::$qualifierValueItem = NewItem::withId( $qualifierValueItemId )
			->andLabel( 'en', $qualifierValueItemEnLabel )
			->build();

		$itemId = 'Q123';
		$enLabel = 'potato';
		$enDescription = 'root vegetable';
		$enAliases = [ 'spud', 'tater' ];
		$sitelinkSiteId = self::ALLOWED_SITELINK_SITES[0];
		$otherSiteId = self::ALLOWED_SITELINK_SITES[1];
		$sitelinkTitle = 'Potato';
		$statementWithStringValuePropertyId = 'P1';
		$qualifierPropertyId = 'P2';
		$statementWithItemValuePropertyId = 'P3';
		$statementWithNoValuePropertyId = 'P4';
		$statementWithSomeValuePropertyId = 'P5';
		$statementWithItemValueQualifierPropertyId = $statementWithItemValuePropertyId; // also type wikibase-item so we can just reuse it.
		$unusedPropertyId = 'P9999';
		$qualifierStringValue = 'qualifierStringValue';
		$statementStringValue = 'statementStringValue';
		$statementWithStringValue = NewStatement::forProperty( ( $statementWithStringValuePropertyId ) )
			->withGuid( "$itemId\$bed933b7-4207-d679-7571-3630cfb49d7f" )
			->withRank( 1 )
			->withQualifier( new NumericPropertyId( $qualifierPropertyId ), new StringValue( $qualifierStringValue ) )
			->withValue( $statementStringValue )
			->build();
		$statementWithItemValue = NewStatement::forProperty( ( $statementWithItemValuePropertyId ) )
			->withGuid( "$itemId\$bed933b7-4207-d679-7571-3630cfb49d8f" )
			->withValue( new ItemId( $itemValueItemId ) )
			->withQualifier( $statementWithItemValueQualifierPropertyId, self::$qualifierValueItem->getId() )
			->build();

		$statementWithNoValue = NewStatement::noValueFor( ( $statementWithNoValuePropertyId ) )
			->withGuid( "$itemId\$bed933b7-4207-d679-7571-3630cfb49d9f" )
			->build();
		$statementWithSomeValue = NewStatement::someValueFor( ( $statementWithSomeValuePropertyId ) )
			->withGuid( "$itemId\$bed933b7-4207-d679-7571-3630cfb49d6f" )
			->build();

		self::$sitelinkSite = new MediaWikiSite();
		self::$sitelinkSite->setLinkPath( 'https://wiki.example/wiki/$1' );
		$expectedSitelinkUrl = "https://wiki.example/wiki/$sitelinkTitle";
		self::$sitelinkSite->setGlobalId( $sitelinkSiteId );

		self::$statementProperty = new Property(
			new NumericPropertyId( $statementWithStringValuePropertyId ),
			new Fingerprint( new TermList( [ new Term( 'en', 'statement prop' ) ] ) ),
			'string',
		);
		self::$qualifierProperty = new Property(
			new NumericPropertyId( $qualifierPropertyId ),
			new Fingerprint( new TermList( [ new Term( 'en', 'qualifier prop' ) ] ) ),
			'string',
		);

		self::$item1 = NewItem::withId( $itemId )
			->andLabel( 'en', $enLabel )
			->andDescription( 'en', $enDescription )
			->andAliases( 'en', $enAliases )
			->andSiteLink( $sitelinkSiteId, $sitelinkTitle )
			->andStatement( $statementWithStringValue )
			->andStatement( $statementWithItemValue )
			->andStatement( $statementWithNoValue )
			->andStatement( $statementWithSomeValue )
			->build();

		$item2Id = 'Q321';
		self::$item2 = NewItem::withId( $item2Id )
			->andLabel( 'en', 'another item' )
			->build();

		yield 'id only' => [
			"{ item(id: \"$itemId\") { id } }",
			[ 'data' => [ 'item' => [ 'id' => $itemId ] ] ],
		];
		yield 'label' => [
			"{ item(id: \"$itemId\") {
				enLabel: label(languageCode: \"en\")
				deLabel: label(languageCode: \"de\")
			} }",
			[ 'data' => [ 'item' => [ 'enLabel' => $enLabel, 'deLabel' => null ] ] ],
		];
		yield 'description' => [
			"{ item(id: \"$itemId\") {
				enDescription: description(languageCode: \"en\")
				deDescription: description(languageCode: \"de\")
			} }",
			[ 'data' => [ 'item' => [ 'enDescription' => $enDescription, 'deDescription' => null ] ] ],
		];
		yield 'aliases' => [
			"{ item(id: \"$itemId\") {
				enAliases: aliases(languageCode: \"en\")
				deAliases: aliases(languageCode: \"de\")
			} }",
			[ 'data' => [ 'item' => [ 'enAliases' => $enAliases, 'deAliases' => [] ] ] ],
		];
		yield 'sitelink' => [
			"{ item(id: \"$itemId\") {
				sitelink1: sitelink(siteId: \"$sitelinkSiteId\") { title url }
				sitelink2: sitelink(siteId: \"$otherSiteId\") { title }
			} }",
			[
				'data' => [
					'item' => [
						'sitelink1' => [ 'title' => $sitelinkTitle, 'url' => $expectedSitelinkUrl ],
						'sitelink2' => null,
					],
				],
			],
		];
		yield 'statement with id and rank' => [
			"{ item(id: \"$itemId\") {
				$statementWithStringValuePropertyId: statements(propertyId: \"$statementWithStringValuePropertyId\") {
					id
					rank
					property { id dataType }
				}
				$unusedPropertyId: statements(propertyId: \"$unusedPropertyId\") { id }
			} }",
			[
				'data' => [
					'item' => [
						$statementWithStringValuePropertyId => [
							[
								'id' => $statementWithStringValue->getGuid(),
								'rank' => 'normal',
								'property' => [
									'id' => $statementWithStringValuePropertyId,
									'dataType' => 'string',
								],
							],
						],
						$unusedPropertyId => [],
					],
				],
			],
		];
		yield 'statement with qualifier' => [
			"{ item(id: \"$itemId\") {
			 	statements(propertyId: \"$statementWithStringValuePropertyId\") {
			 		$qualifierPropertyId: qualifiers(propertyId: \"$qualifierPropertyId\") {
						property { id dataType }
			 			value { ...on StringValue { content } }
			 			valueType
			 		}
			 		$unusedPropertyId: qualifiers(propertyId: \"$unusedPropertyId\") {
			 			property { id }
			 		}
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								$qualifierPropertyId => [
									[
										'property' => [
											'id' => $qualifierPropertyId,
											'dataType' => 'string',
										],
										'value' => [
											'content' => $qualifierStringValue,
										],
										'valueType' => 'value',
									],
								],
								$unusedPropertyId => [],
							],
						],
					],
				],
			],
		];
		yield 'statements with StringValue and ItemValue' => [
			"{ item(id: \"$itemId\") {
				$statementWithStringValuePropertyId: statements(propertyId: \"$statementWithStringValuePropertyId\") {
					value { ...on StringValue { content } }
					valueType
				}
				$statementWithItemValuePropertyId: statements(propertyId: \"$statementWithItemValuePropertyId\") {
					value { ...on ItemValue { content { id } } }
					valueType
				}
			} }",
			[
				'data' => [
					'item' => [
						$statementWithStringValuePropertyId => [
							[
								'value' => [
									'content' => $statementStringValue,
								],
								'valueType' => 'value',
							],
						],
						$statementWithItemValuePropertyId => [
							[
								'value' => [
									'content' => [ 'id' => $itemValueItemId ],
								],
								'valueType' => 'value',
							],
						],
					],
				],
			],
		];
		yield 'statements with novalue and somevalue' => [
			"{ item(id: \"$itemId\") {
				$statementWithSomeValuePropertyId: statements(propertyId: \"$statementWithSomeValuePropertyId\") {
					value { ...on StringValue { content } }
					valueType
				}
				$statementWithNoValuePropertyId: statements(propertyId: \"$statementWithNoValuePropertyId\") {
					valueType
				}
			} }",
			[
				'data' => [
					'item' => [
						$statementWithSomeValuePropertyId => [
							[
								'value' => null,
								'valueType' => 'somevalue',
							],
						],
						$statementWithNoValuePropertyId => [
							[
								'valueType' => 'novalue',
							],
						],
					],
				],
			],
		];
		yield 'labels of predicate properties' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$statementWithStringValuePropertyId}\") {
					property {
						label(languageCode: \"en\")
					}
					qualifiers(propertyId: \"{$qualifierPropertyId}\") {
						property {
							label(languageCode: \"en\")
						}
					}
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								'property' => [ 'label' => self::$statementProperty->getLabels()->getByLanguage( 'en' )->getText() ],
								'qualifiers' => [
									[
										'property' => [
											'label' => self::$qualifierProperty->getLabels()->getByLanguage( 'en' )->getText(),
										],
									],
								],
							],
						],
					],
				],
			],
		];
		yield 'labels of item values' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$statementWithItemValuePropertyId}\") {
					value {
						... on ItemValue {
							content { label(languageCode: \"en\") }
						}
					}
					qualifiers(propertyId: \"{$statementWithItemValueQualifierPropertyId}\") {
						value {
							... on ItemValue {
								content { label(languageCode: \"en\") }
							}
						}
					}
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								'value' => [
									'content' => [
										'label' => self::$statementValueItem->getLabels()->getByLanguage( 'en' )->getText(),
									],
								],
								'qualifiers' => [
									[
										'value' => [
											'content' => [
												'label' => self::$qualifierValueItem->getLabels()->getByLanguage( 'en' )->getText(),
											],
										],
									],
								],
							],
						],
					],
				],
			],
		];
		yield 'multiple items at once' => [
			"{
				item1: item(id: \"$itemId\") { label(languageCode: \"en\") }
				item2: item(id: \"$item2Id\") { label(languageCode: \"en\") }
			}",
			[
				'data' => [
					'item1' => [ 'label' => self::$item1->getLabels()->getByLanguage( 'en' )->getText() ],
					'item2' => [ 'label' => self::$item2->getLabels()->getByLanguage( 'en' )->getText() ],
				],
			],
		];
		yield 'item does not exist' => [
			'{ item(id: "Q9999999") { id } }',
			[ 'data' => [ 'item' => null ] ],
		];
	}

	/**
	 * @dataProvider errorsProvider
	 */
	public function testErrors( string $query, string $expectedErrorMessage ): void {
		$itemId = 'Q123'; // same as the one in errorsProvider()
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( NewItem::withId( $itemId )->build() );

		$result = $this->newGraphQLService( $entityLookup )->query( $query );

		$this->assertSame( $expectedErrorMessage, $result['errors'][0]['message'] );
	}

	public static function errorsProvider(): Generator {
		$itemId = 'Q123'; // same as the one in testErrors()

		yield 'validates item ID' => [
			'{ item(id: "P123") { id } }',
			'Not a valid Item ID: "P123"',
		];

		$siteId = 'not-a-valid-site-id';
		yield 'validates site ID' => [
			"{ item(id: \"$itemId\") {
				sitelink(siteId: \"$siteId\") { title }
			 } }",
			"Not a valid site ID: \"$siteId\"",
		];

		$languageCode = 'not-a-valid-language-code';
		foreach ( [ 'label', 'description', 'aliases' ] as $field ) {
			yield "validates $field language code" => [
				"{ item(id: \"$itemId\") {
					$field(languageCode: \"$languageCode\")
				} }",
				"Not a valid language code: \"$languageCode\"",
			];
		}
	}

	private function newGraphQLService(
		EntityLookup $entityLookup,
		?SiteLookup $siteLookup = null,
		?SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider = null,
		?PrefetchingTermLookup $termLookup = null,
	): GraphQLService {
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );
		$this->setService( 'SiteLookup', $siteLookup ?? new HashSiteStore() );

		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturn( 'string' );
		$this->setService( 'WikibaseRepo.PropertyDataTypeLookup', $dataTypeLookup );

		$this->setService(
			'WikibaseRepo.SiteLinkGlobalIdentifiersProvider',
			$siteLinkGlobalIdentifiersProvider ?? $this->createStub( SiteLinkGlobalIdentifiersProvider::class )
		);

		$this->setService(
			'WikibaseRepo.PrefetchingTermLookup',
			$termLookup ?? $this->createStub( PrefetchingTermLookup::class ),
		);

		return WbReuse::getGraphQLService();
	}
}
