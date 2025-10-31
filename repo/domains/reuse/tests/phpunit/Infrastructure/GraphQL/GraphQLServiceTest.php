<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use DataValues\DecimalValue;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnboundedQuantityValue;
use Generator;
use GraphQL\GraphQL;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\SiteLookup;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
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
	private static Property $stringTypeProperty;
	private static Property $itemTypeProperty;
	private static Property $globeCoordinateTypeProperty;
	private static Property $monolingualTextProperty;
	private static Property $quantityProperty;
	private static Property $timeProperty;
	private static Property $propertyTypeProperty;
	private static Property $propertyUsedAsValue;
	private static Property $qualifierProperty;
	private static Property $customEntityIdProperty;
	private static MediaWikiSite $sitelinkSite;
	private const ALLOWED_SITELINK_SITES = [ 'examplewiki', 'otherwiki' ];
	private const CUSTOM_ENTITY_DATA_TYPE = 'test-type';

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
			self::$stringTypeProperty,
			self::$qualifierProperty,
			self::$statementValueItem,
			self::$qualifierValueItem,
			self::$propertyUsedAsValue,
		] );

		$dataTypeLookup = new InMemoryDataTypeLookup();
		foreach ( [
			self::$qualifierProperty,
			self::$stringTypeProperty,
			self::$itemTypeProperty,
			self::$globeCoordinateTypeProperty,
			self::$monolingualTextProperty,
			self::$quantityProperty,
			self::$timeProperty,
			self::$propertyTypeProperty,
			self::$customEntityIdProperty,
		] as $property ) {
			$dataTypeLookup->setDataTypeForProperty( $property->getId(), $property->getDataTypeId() );
		}

		$this->assertEquals(
			$expectedResult,
			$this->newGraphQLService(
				$entityLookup,
				new HashSiteStore( [ self::$sitelinkSite ] ),
				$siteIdProvider,
				$termLookup,
				$dataTypeLookup,
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
		$statementWithNoReferencesPropertyId = $statementWithNoValuePropertyId;
		$statementWithSomeValuePropertyId = 'P5';
		$statementWithGlobeCoordinateValuePropertyId = 'P6';
		$statementWithMonolingualTextValuePropertyId = 'P7';
		$statementWithQuantityValuePropertyId = 'P8';
		$statementWithTimeValuePropertyId = 'P9';
		$statementWithPropertyValuePropertyId = 'P10';
		$statementWithCustomEntityIdValuePropertyId = 'P13';
		$statementWithItemValueQualifierPropertyId = $statementWithItemValuePropertyId; // also type wikibase-item so we can just reuse it.
		$statementReferencePropertyId = 'P11';
		$unusedPropertyId = 'P9999';
		$qualifierStringValue = 'qualifierStringValue';
		$statementStringValue = 'statementStringValue';
		$statementWithStringValue = NewStatement::forProperty( ( $statementWithStringValuePropertyId ) )
			->withSubject( $itemId )
			->withSomeGuid()
			->withRank( 1 )
			->withQualifier( new NumericPropertyId( $qualifierPropertyId ), new StringValue( $qualifierStringValue ) )
			->withReference( new Reference( [ new PropertySomeValueSnak( new NumericPropertyId( $statementReferencePropertyId ) ) ] ) )
			->withValue( $statementStringValue )
			->build();

		$statementWithItemValue = NewStatement::forProperty( ( $statementWithItemValuePropertyId ) )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( new ItemId( $itemValueItemId ) )
			->withQualifier( $statementWithItemValueQualifierPropertyId, self::$qualifierValueItem->getId() )
			->build();
		$globeCoordinateValue = new GlobeCoordinateValue( new LatLongValue( 52.516, 13.383 ) );
		$statementWithGlobeCoordinateValue = NewStatement::forProperty( $statementWithGlobeCoordinateValuePropertyId )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $globeCoordinateValue )
			->build();
		$monolingualTextValue = new MonolingualTextValue( 'en', 'potato' );
		$statementWithMonolingualTextValue = NewStatement::forProperty( $statementWithMonolingualTextValuePropertyId )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $monolingualTextValue )
			->build();
		$quantityValue = new QuantityValue(
			new DecimalValue( '+0.111' ),
			'https://wikibase.example/wiki/Q123',
			new DecimalValue( '+0.1150' ),
			new DecimalValue( '+0.1105' ),
		);
		$unboundedQuantityValue = new UnboundedQuantityValue(
			new DecimalValue( '+321' ),
			'https://wikibase.example/wiki/Q321',
		);
		$statementWithQuantityValue = NewStatement::forProperty( $statementWithQuantityValuePropertyId )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $quantityValue )
			->build();
		$statementWithUnboundedQuantityValue = NewStatement::forProperty( $statementWithQuantityValuePropertyId )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $unboundedQuantityValue )
			->build();
		$timeValue = new TimeValue(
			timestamp: '+2001-01-01T00:00:00Z',
			timezone: 60,
			before: 0,
			after: 1,
			precision: TimeValue::PRECISION_MONTH,
			calendarModel: 'http://www.wikidata.org/entity/Q1985727',
		);
		$statementWithTimeValue = NewStatement::forProperty( $statementWithTimeValuePropertyId )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $timeValue )
			->build();
		self::$propertyUsedAsValue = new Property(
			new NumericPropertyId( 'P789' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'property used as value' ) ] ) ),
			'string',
		);
		$statementWithPropertyValue = NewStatement::forProperty( $statementWithPropertyValuePropertyId )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( new EntityIdValue( self::$propertyUsedAsValue->getId() ) )
			->build();

		$statementWithNoValue = NewStatement::noValueFor( ( $statementWithNoValuePropertyId ) )
			->withSubject( $itemId )
			->withSomeGuid()
			->build();
		$statementWithSomeValue = NewStatement::someValueFor( ( $statementWithSomeValuePropertyId ) )
			->withSubject( $itemId )
			->withSomeGuid()
			->build();

		$customEntityId = $this->createMock( EntityId::class );
		$customEntityId->method( 'getSerialization' )
			->willReturn( 'T3' );
		$entityIdValue = new EntityIdValue( $customEntityId );
		$statementWithCustomEntityIdValue = NewStatement::forProperty( $statementWithCustomEntityIdValuePropertyId )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $entityIdValue )
			->build();

		self::$sitelinkSite = new MediaWikiSite();
		self::$sitelinkSite->setLinkPath( 'https://wiki.example/wiki/$1' );
		$expectedSitelinkUrl = "https://wiki.example/wiki/$sitelinkTitle";
		self::$sitelinkSite->setGlobalId( $sitelinkSiteId );

		self::$stringTypeProperty = new Property(
			new NumericPropertyId( $statementWithStringValuePropertyId ),
			new Fingerprint( new TermList( [ new Term( 'en', 'statement prop' ) ] ) ),
			'string',
		);
		self::$itemTypeProperty = new Property( new NumericPropertyId( $statementWithItemValuePropertyId ), null, 'wikibase-item' );
		self::$globeCoordinateTypeProperty = new Property(
			new NumericPropertyId( $statementWithGlobeCoordinateValuePropertyId ),
			null,
			'globe-coordinate',
		);
		self::$monolingualTextProperty = new Property(
			new NumericPropertyId( $statementWithMonolingualTextValuePropertyId ),
			null,
			'monolingualtext',
		);
		self::$quantityProperty = new Property(
			new NumericPropertyId( $statementWithQuantityValuePropertyId ),
			null,
			'quantity',
		);
		self::$timeProperty = new Property(
			new NumericPropertyId( $statementWithTimeValuePropertyId ),
			null,
			'time',
		);
		self::$propertyTypeProperty = new Property(
			new NumericPropertyId( $statementWithPropertyValuePropertyId ),
			null,
			'wikibase-property',
		);
		self::$qualifierProperty = new Property(
			new NumericPropertyId( $qualifierPropertyId ),
			new Fingerprint( new TermList( [ new Term( 'en', 'qualifier prop' ) ] ) ),
			'string',
		);
		self::$customEntityIdProperty = new Property(
			new NumericPropertyId( $statementWithCustomEntityIdValuePropertyId ),
			null,
			self::CUSTOM_ENTITY_DATA_TYPE
		);

		self::$item1 = NewItem::withId( $itemId )
			->andLabel( 'en', $enLabel )
			->andDescription( 'en', $enDescription )
			->andAliases( 'en', $enAliases )
			->andSiteLink( $sitelinkSiteId, $sitelinkTitle )
			->andStatement( $statementWithStringValue )
			->andStatement( $statementWithItemValue )
			->andStatement( $statementWithGlobeCoordinateValue )
			->andStatement( $statementWithMonolingualTextValue )
			->andStatement( $statementWithQuantityValue )
			->andStatement( $statementWithUnboundedQuantityValue )
			->andStatement( $statementWithTimeValue )
			->andStatement( $statementWithPropertyValue )
			->andStatement( $statementWithNoValue )
			->andStatement( $statementWithSomeValue )
			->andStatement( $statementWithCustomEntityIdValue )
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
		yield 'statement with references' => [
			"{ item(id: \"$itemId\") {
			 	$statementWithStringValuePropertyId: statements(propertyId: \"$statementWithStringValuePropertyId\") {
			 		references{
			 			parts{
							property { id dataType } 
							value { ...on StringValue { content } }
							valueType
						}
			 		}
				}
				$statementWithNoReferencesPropertyId: statements(propertyId: \"$statementWithNoReferencesPropertyId\") { 
					references { parts { valueType }
			 		}
				 }
			} }",
			[
				'data' => [
					'item' => [
						$statementWithStringValuePropertyId => [
							[
								'references' => [
									[
										'parts' => [
											[
												'property' => [
													'id' => $statementReferencePropertyId,
													'dataType' => null,
												],
												'value' => null,
												'valueType' => 'somevalue',
											],
										],
									],
								],
							],
						],
						$statementWithNoReferencesPropertyId => [ [ 'references' => [] ] ],
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
					value { ...on ItemValue { id } }
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
								'value' => [ 'id' => $itemValueItemId ],
								'valueType' => 'value',
							],
						],
					],
				],
			],
		];
		yield 'statement with globe-coordinate value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"$statementWithGlobeCoordinateValuePropertyId\") {
					value {
						... on GlobeCoordinateValue { latitude longitude precision globe }
					}
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								'value' => [
									'latitude' => $globeCoordinateValue->getLatitude(),
									'longitude' => $globeCoordinateValue->getLongitude(),
									'precision' => $globeCoordinateValue->getPrecision(),
									'globe' => $globeCoordinateValue->getGlobe(),
								],
							],
						],
					],
				],
			],
		];
		yield 'statement with monolingualtext value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"$statementWithMonolingualTextValuePropertyId\") {
					value {
						... on MonolingualTextValue { language text }
					}
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								'value' => [
									'language' => $monolingualTextValue->getLanguageCode(),
									'text' => $monolingualTextValue->getText(),
								],
							],
						],
					],
				],
			],
		];
		yield 'statement with quantity value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"$statementWithQuantityValuePropertyId\") {
					value {
						... on QuantityValue { amount unit lowerBound upperBound }
					}
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								'value' => [
									'amount' => $quantityValue->getAmount()->getValue(),
									'unit' => $quantityValue->getUnit(),
									'lowerBound' => $quantityValue->getLowerBound()->getValue(),
									'upperBound' => $quantityValue->getUpperBound()->getValue(),
								],
							],
							[
								'value' => [
									'amount' => $unboundedQuantityValue->getAmount()->getValue(),
									'unit' => $unboundedQuantityValue->getUnit(),
									'lowerBound' => null,
									'upperBound' => null,
								],
							],
						],
					],
				],
			],
		];
		yield 'statement with time value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"$statementWithTimeValuePropertyId\") {
					value {
						... on TimeValue { time timezone before after precision calendarModel }
					}
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								'value' => [
									'time' => $timeValue->getTime(),
									'timezone' => $timeValue->getTimezone(),
									'before' => $timeValue->getBefore(),
									'after' => $timeValue->getAfter(),
									'precision' => $timeValue->getPrecision(),
									'calendarModel' => $timeValue->getCalendarModel(),
								],
							],
						],
					],
				],
			],
		];
		yield 'statement with property value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"$statementWithPropertyValuePropertyId\") {
					value {
						... on PropertyValue {
							id
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
								'value' => [
									'id' => self::$propertyUsedAsValue->getId()->getSerialization(),
									'label' => self::$propertyUsedAsValue->getLabels()->getByLanguage( 'en' )->getText(),
								],
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
								'property' => [ 'label' => self::$stringTypeProperty->getLabels()->getByLanguage( 'en' )->getText() ],
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
							label(languageCode: \"en\")
						}
					}
					qualifiers(propertyId: \"{$statementWithItemValueQualifierPropertyId}\") {
						value {
							... on ItemValue {
								label(languageCode: \"en\")
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
									'label' => self::$statementValueItem->getLabels()->getByLanguage( 'en' )->getText(),
								],
								'qualifiers' => [
									[
										'value' => [
											'label' => self::$qualifierValueItem->getLabels()->getByLanguage( 'en' )->getText(),
										],
									],
								],
							],
						],
					],
				],
			],
		];
		yield 'entity id value for which there is no data type specific GraphQL type' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$statementWithCustomEntityIdValuePropertyId}\") {
					value {... on EntityValue { id } }
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[ 'value' => [ 'id' => $entityIdValue->getEntityId()->getSerialization() ] ],
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

		$propertyId = 'not-a-valid-property-id';
		yield 'validates property ID' => [
			"{ item(id: \"$itemId\") {
			statements(propertyId: \"$propertyId\") { id }
		 } }",
			"Not a valid Property ID: \"$propertyId\"",
		];

		$qualifierPropertyId = 'not-a-valid-property-id';
		$validPropertyId = 'P123';
		yield 'validates property ID for qualifiers' => [
			"{ item(id: \"$itemId\") {
			statements(propertyId: \"$validPropertyId\") {
				qualifiers(propertyId: \"$qualifierPropertyId\") {
					valueType
				} 
			 }
		 } }",
			"Not a valid Property ID: \"$propertyId\"",
		];
	}

	private function newGraphQLService(
		EntityLookup $entityLookup,
		?SiteLookup $siteLookup = null,
		?SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider = null,
		?PrefetchingTermLookup $termLookup = null,
		?PropertyDataTypeLookup $dataTypeLookup = null,
	): GraphQLService {
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );
		$this->setService( 'SiteLookup', $siteLookup ?? new HashSiteStore() );
		$this->setService( 'WikibaseRepo.PropertyDataTypeLookup', $dataTypeLookup ?? new InMemoryDataTypeLookup() );

		$this->setService(
			'WikibaseRepo.SiteLinkGlobalIdentifiersProvider',
			$siteLinkGlobalIdentifiersProvider ?? $this->createStub( SiteLinkGlobalIdentifiersProvider::class )
		);

		$this->setService(
			'WikibaseRepo.PrefetchingTermLookup',
			$termLookup ?? $this->createStub( PrefetchingTermLookup::class ),
		);

		$this->setTemporaryHook( 'WikibaseRepoDataTypes', function ( array &$dataTypes ): void {
			$dataTypes['PT:' . self::CUSTOM_ENTITY_DATA_TYPE] = [
				'value-type' => 'wikibase-entityid',
			];
		} );
		$this->resetServices();

		return WbReuse::getGraphQLService();
	}
}
