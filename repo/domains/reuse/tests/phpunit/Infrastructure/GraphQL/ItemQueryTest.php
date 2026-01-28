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
use DataValues\UnDeserializableValue;
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
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\QueryContext;
use Wikibase\Repo\Domains\Reuse\WbReuse;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemQueryTest extends MediaWikiIntegrationTestCase {

	/** @var Property[] */
	private static array $properties = [];

	/** @var Item[] */
	private static array $items = [];
	private static ?ItemId $redirectSource = null;
	private static ?ItemId $redirectTarget = null;
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
	public function testQuery( string $query, array $expectedResult, array $variables = [], ?string $operationName = null ): void {
		$entityLookup = new InMemoryEntityLookup();
		foreach ( self::$items as $item ) {
			$entityLookup->addEntity( $item );
		}

		$siteIdProvider = $this->createStub( SiteLinkGlobalIdentifiersProvider::class );
		$siteIdProvider->method( 'getSiteIds' )->willReturn( self::ALLOWED_SITELINK_SITES );

		$termLookup = new InMemoryPrefetchingTermLookup();
		$termLookup->setData( [ ...self::$items, ...self::$properties ] );

		$dataTypeLookup = new InMemoryDataTypeLookup();
		foreach ( self::$properties as $property ) {
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
			)->query( $query, $variables, $operationName )
		);
	}

	public function queryProvider(): Generator {
		$enLabel = 'potato';
		$enDescription = 'root vegetable';
		$enAliases = [ 'spud', 'tater' ];
		$sitelinkSiteId = self::ALLOWED_SITELINK_SITES[0];
		$otherSiteId = self::ALLOWED_SITELINK_SITES[1];
		$sitelinkTitle = 'Potato';
		$unusedPropertyId = 'P9999';

		$item = $this->createItem(
			NewItem::withLabel( 'en', $enLabel )
				->andDescription( 'en', $enDescription )
				->andAliases( 'en', $enAliases )
				->andSiteLink( $sitelinkSiteId, $sitelinkTitle )
		);
		$itemId = $item->getId();

		self::$sitelinkSite = new MediaWikiSite();
		self::$sitelinkSite->setLinkPath( 'https://wiki.example/wiki/$1' );
		$expectedSitelinkUrl = "https://wiki.example/wiki/$sitelinkTitle";
		self::$sitelinkSite->setGlobalId( $sitelinkSiteId );

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

		$stringProperty = $this->createProperty( 'string', 'string property' );
		$qualifierProperty = $this->createProperty( 'string', 'qualifier prop' );
		$referenceProperty = $this->createProperty( 'string', 'reference prop' );
		$statementStringValue = 'statementStringValue';
		$qualifierStringValue = 'qualifierStringValue';
		$statementWithStringValue = NewStatement::forProperty( $stringProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withQualifier( $qualifierProperty->getId(), new StringValue( $qualifierStringValue ) )
			->withReference( new Reference( [ new PropertySomeValueSnak( $referenceProperty->getId() ) ] ) )
			->withValue( $statementStringValue )
			->build();
		$item->getStatements()->addStatement( $statementWithStringValue );
		yield 'statement with id and rank' => [
			"{ item(id: \"$itemId\") {
				{$stringProperty->getId()}: statements(propertyId: \"{$stringProperty->getId()}\") {
					id
					rank
					property { id dataType }
				}
				$unusedPropertyId: statements(propertyId: \"$unusedPropertyId\") { id }
			} }",
			[
				'data' => [
					'item' => [
						$stringProperty->getId()->getSerialization() => [
							[
								'id' => $statementWithStringValue->getGuid(),
								'rank' => 'NORMAL',
								'property' => [
									'id' => $stringProperty->getId()->getSerialization(),
									'dataType' => 'string',
								],
							],
						],
						$unusedPropertyId => [],
					],
				],
			],
		];

		$itemProperty = $this->createProperty( 'wikibase-item' );
		$itemUsedAsStatementValue = $this->createItem(
			NewItem::withLabel( 'en', 'statement value item label' )
				->andDescription( 'en', 'statement value item description' )
		);
		$itemUsedAsQualifierValue = $this->createItem( NewItem::withLabel( 'en', 'qualifier value item label' ) );
		$statementWithItemValue = NewStatement::forProperty( $itemProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $itemUsedAsStatementValue->getId() )
			->withQualifier( $itemProperty->getId(), $itemUsedAsQualifierValue->getId() )
			->build();
		$item->getStatements()->addStatement( $statementWithItemValue );
		yield 'statements with StringValue and ItemValue' => [
			"{ item(id: \"$itemId\") {
				{$stringProperty->getId()}: statements(propertyId: \"{$stringProperty->getId()}\") {
					value { ...on StringValue { content } }
					valueType
				}
				{$itemProperty->getId()}: statements(propertyId: \"{$itemProperty->getId()}\") {
					value { ...on ItemValue { id } }
					valueType
				}
			} }",
			[
				'data' => [
					'item' => [
						$stringProperty->getId()->getSerialization() => [
							[
								'value' => [
									'content' => $statementStringValue,
								],
								'valueType' => 'VALUE',
							],
						],
						$itemProperty->getId()->getSerialization() => [
							[
								'value' => [ 'id' => $itemUsedAsStatementValue->getId() ],
								'valueType' => 'VALUE',
							],
						],
					],
				],
			],
		];
		yield 'statement with references' => [
			"{ item(id: \"$itemId\") {
			 	{$stringProperty->getId()}: statements(propertyId: \"{$stringProperty->getId()}\") {
			 		references {
			 			parts {
							property { id dataType }
							value { ...on StringValue { content } }
							valueType
						}
			 		}
				}
				statementWithNoReferences: statements(propertyId: \"{$itemProperty->getId()}\") {
					references { parts { valueType } }
				}
			} }",
			[
				'data' => [
					'item' => [
						$stringProperty->getId()->getSerialization() => [
							[
								'references' => [
									[
										'parts' => [
											[
												'property' => [
													'id' => $referenceProperty->getId()->getSerialization(),
													'dataType' => 'string',
												],
												'value' => null,
												'valueType' => 'SOME_VALUE',
											],
										],
									],
								],
							],
						],
						'statementWithNoReferences' => [ [ 'references' => [] ] ],
					],
				],
			],
		];
		yield 'statement with qualifier' => [
			"{ item(id: \"$itemId\") {
			 	statements(propertyId: \"{$stringProperty->getId()}\") {
			 		{$qualifierProperty->getId()}: qualifiers(propertyId: \"{$qualifierProperty->getId()}\") {
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
								$qualifierProperty->getId()->getSerialization() => [
									[
										'property' => [
											'id' => $qualifierProperty->getId()->getSerialization(),
											'dataType' => 'string',
										],
										'value' => [
											'content' => $qualifierStringValue,
										],
										'valueType' => 'VALUE',
									],
								],
								$unusedPropertyId => [],
							],
						],
					],
				],
			],
		];

		$globeCoordinateProperty = $this->createProperty( 'globe-coordinate' );
		$globeCoordinateValue = new GlobeCoordinateValue( new LatLongValue( 52.516, 13.383 ) );
		$statementWithGlobeCoordinateValue = NewStatement::forProperty( $globeCoordinateProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $globeCoordinateValue )
			->build();
		$item->getStatements()->addStatement( $statementWithGlobeCoordinateValue );
		yield 'statement with globe-coordinate value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$globeCoordinateProperty->getId()}\") {
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

		$monolingualTextProperty = $this->createProperty( 'monolingualtext' );
		$monolingualTextValue = new MonolingualTextValue( 'en', 'potato' );
		$statementWithMonolingualTextValue = NewStatement::forProperty( $monolingualTextProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $monolingualTextValue )
			->build();
		$item->getStatements()->addStatement( $statementWithMonolingualTextValue );
		yield 'statement with monolingualtext value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$monolingualTextProperty->getId()}\") {
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

		$quantityProperty = $this->createProperty( 'quantity' );
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
		$statementWithQuantityValue = NewStatement::forProperty( $quantityProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $quantityValue )
			->build();
		$statementWithUnboundedQuantityValue = NewStatement::forProperty( $quantityProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $unboundedQuantityValue )
			->build();
		$item->getStatements()->addStatement( $statementWithQuantityValue );
		$item->getStatements()->addStatement( $statementWithUnboundedQuantityValue );
		yield 'statement with quantity value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$quantityProperty->getId()}\") {
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

		$timeProperty = $this->createProperty( 'time' );
		$timeValue = new TimeValue(
			timestamp: '+2001-01-01T00:00:00Z',
			timezone: 60,
			before: 0,
			after: 1,
			precision: TimeValue::PRECISION_MONTH,
			calendarModel: 'http://www.wikidata.org/entity/Q1985727',
		);
		$statementWithTimeValue = NewStatement::forProperty( $timeProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $timeValue )
			->build();
		$item->getStatements()->addStatement( $statementWithTimeValue );
		yield 'statement with time value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$timeProperty->getId()}\") {
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

		$propertyProperty = $this->createProperty( 'wikibase-property' );
		$propertyUsedAsValue = $this->createProperty( 'string', 'property used as value' );
		$statementWithPropertyValue = NewStatement::forProperty( $propertyProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( new EntityIdValue( $propertyUsedAsValue->getId() ) )
			->build();
		$item->getStatements()->addStatement( $statementWithPropertyValue );
		yield 'statement with property value' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$propertyProperty->getId()}\") {
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
									'id' => $propertyUsedAsValue->getId()->getSerialization(),
									'label' => $propertyUsedAsValue->getLabels()->getByLanguage( 'en' )->getText(),
								],
							],
						],
					],
				],
			],
		];

		$noValueSomeValueProperty = $this->createProperty( 'string' );
		$statementWithSomeValue = NewStatement::someValueFor( $noValueSomeValueProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->build();
		$statementWithNoValue = NewStatement::noValueFor( $noValueSomeValueProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->build();
		$item->getStatements()->addStatement( $statementWithSomeValue );
		$item->getStatements()->addStatement( $statementWithNoValue );
		yield 'statements with novalue and somevalue' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$noValueSomeValueProperty->getId()}\") {
					value { ...on StringValue { content } }
					valueType
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								'value' => null,
								'valueType' => 'SOME_VALUE',
							],
							[
								'value' => null,
								'valueType' => 'NO_VALUE',
							],
						],
					],
				],
			],
		];

		$unknownTypeProperty = $this->createProperty( 'unknown-type', 'unknown type property' );
		$unknownValueData = [ 'some' => 'data' ];
		$statementWithUnknownType = NewStatement::forProperty( $unknownTypeProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			// Ideally we would just stub DataValue here, but that's not possible because it extends Serializable,
			// which is deprecated and emits a warning.
			->withValue( new UnDeserializableValue( $unknownValueData, null, 'test value' ) )
			->build();
		$item->getStatements()->addStatement( $statementWithUnknownType );
		yield 'statement with unknown value type' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$unknownTypeProperty->getId()}\") {
					value { ...on UnknownValue { content } }
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[ 'value' => [ 'content' => $unknownValueData ] ],
						],
					],
				],
			],
		];

		$deletedProperty = 'P999';
		$valueUsedInStatementWithDeletedProperty = new StringValue( 'deleted value' );
		$statementWithDeletedProperty = NewStatement::forProperty( $deletedProperty )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $valueUsedInStatementWithDeletedProperty )
			->build();
		$item->getStatements()->addStatement( $statementWithDeletedProperty );
		yield 'statement with deleted property' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"$deletedProperty\") {
					value { ...on UnknownValue { content } }
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [
							[ 'value' => [ 'content' => $valueUsedInStatementWithDeletedProperty->getArrayValue() ] ],
						],
					],
				],
			],
		];
		yield 'labels of predicate properties' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$stringProperty->getId()}\") {
					property {
						label(languageCode: \"en\")
					}
					qualifiers(propertyId: \"{$qualifierProperty->getId()}\") {
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
								'property' => [ 'label' => $stringProperty->getLabels()->getByLanguage( 'en' )->getText() ],
								'qualifiers' => [
									[
										'property' => [
											'label' => $qualifierProperty->getLabels()->getByLanguage( 'en' )->getText(),
										],
									],
								],
							],
						],
					],
				],
			],
		];
		yield 'labels and descriptions of item values' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$itemProperty->getId()}\") {
					value {
						... on ItemValue {
							label(languageCode: \"en\")
							description(languageCode: \"en\")
						}
					}
					qualifiers(propertyId: \"{$itemProperty->getId()}\") {
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
									'label' => $itemUsedAsStatementValue->getLabels()->getByLanguage( 'en' )->getText(),
									'description' => $itemUsedAsStatementValue->getDescriptions()->getByLanguage( 'en' )->getText(),
								],
								'qualifiers' => [
									[
										'value' => [
											'label' => $itemUsedAsQualifierValue->getLabels()->getByLanguage( 'en' )->getText(),
										],
									],
								],
							],
						],
					],
				],
			],
		];

		$customEntityTypeProperty = $this->createProperty( self::CUSTOM_ENTITY_DATA_TYPE );
		$customEntityId = $this->createMock( EntityId::class );
		$customEntityId->method( 'getSerialization' )
			->willReturn( 'T3' );
		$entityIdValue = new EntityIdValue( $customEntityId );
		$statementWithCustomEntityIdValue = NewStatement::forProperty( $customEntityTypeProperty->getId() )
			->withSubject( $itemId )
			->withSomeGuid()
			->withValue( $entityIdValue )
			->build();
		$item->getStatements()->addStatement( $statementWithCustomEntityIdValue );
		yield 'entity id value for which there is no data type specific GraphQL type' => [
			"{ item(id: \"$itemId\") {
				statements(propertyId: \"{$customEntityTypeProperty->getId()}\") {
					value { ... on EntityValue { id } }
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

		$otherItem = $this->createItem( NewItem::withLabel( 'en', 'another item' ) );
		yield 'multiple items at once' => [
			"{
				item1: item(id: \"$itemId\") { label(languageCode: \"en\") }
				item2: item(id: \"{$otherItem->getId()}\") { label(languageCode: \"en\") }
			}",
			[
				'data' => [
					'item1' => [ 'label' => $item->getLabels()->getByLanguage( 'en' )->getText() ],
					'item2' => [ 'label' => $otherItem->getLabels()->getByLanguage( 'en' )->getText() ],
				],
			],
		];
		yield 'query containing a variable' => [
			'query WithVariable($id: ItemId!) {
				item(id: $id) { id }
			}',
			[ 'data' => [ 'item' => [ 'id' => $itemId->getSerialization() ] ] ],
			[ 'id' => $itemId->getSerialization() ],
		];
		yield 'specific operation' => [
			"query Query1 {
				item(id: \"$itemId\") { id }
			}
			query Query2 {
				item(id: \"{$otherItem->getId()}\") { id }
			}",
			[ 'data' => [ 'item' => [ 'id' => $otherItem->getId()->getSerialization() ] ] ],
			[],
			'Query2',
		];

		yield 'simple itemsById query' => [
			"{ itemsById(ids: [ \"$itemId\", \"{$otherItem->getId()}\" ] ) {
				id
				label(languageCode: \"en\")
			} }",
			[
				'data' => [
					'itemsById' => [
						[
							'id' => $itemId,
							'label' => $item->getLabels()->getByLanguage( 'en' )->getText(),
						],
						[
							'id' => $otherItem->getId(),
							'label' => $otherItem->getLabels()->getByLanguage( 'en' )->getText(),
						],
					],
				],
			],
		];

		self::$redirectSource = new ItemId( 'Q9999999' );
		self::$redirectTarget = $item->getId();
		$itemWithRedirectValue = self::createItem( NewItem::withLabel( 'en', 'redirect value test' ) );
		$itemWithRedirectValue->getStatements()->addStatement(
			NewStatement::forProperty( $itemProperty->getId() )
				->withSubject( $itemWithRedirectValue->getId() )
				->withSomeGuid()
				->withValue( self::$redirectSource )
				->build()
		);
		yield 'redirected item value' => [
			"{ item(id: \"{$itemWithRedirectValue->getId()}\") {
				statements(propertyId: \"{$itemProperty->getId()}\") {
					value {
						...on ItemValue {
						label(languageCode: \"en\")
						description(languageCode: \"en\")
						}
					}
				}
			} }",
			[
				'data' => [
					'item' => [
						'statements' => [ [
							'value' => [
								'label' => $item->getLabels()->getByLanguage( 'en' )->getText(),
								'description' => $item->getDescriptions()->getByLanguage( 'en' )->getText(),
							],
						] ],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider errorsProvider
	 */
	public function testErrors( string $query, string $expectedErrorMessage, ?array $expectedData = null ): void {
		$itemId = 'Q123'; // same as the one in errorsProvider()
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( NewItem::withId( $itemId )->build() );

		$result = $this->newGraphQLService( $entityLookup )->query( $query );

		$this->assertSame( $expectedErrorMessage, $result['errors'][0]['message'] );
		if ( $expectedData ) {
			$this->assertSame( $expectedData, $result['data'] );
		}
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

		$tooManyItems = 51;
		$percentageOverMaxComplexity = ceil(
			$tooManyItems * GraphQLService::LOAD_ITEM_COMPLEXITY / GraphQLService::MAX_QUERY_COMPLEXITY * 100
		) - 100;

		$tooManyItemFieldsQuery = '{';
		for ( $i = 0; $i < $tooManyItems; $i++ ) {
			$tooManyItemFieldsQuery .= "item$i: item(id: \"$itemId\") { id }";
		}
		$tooManyItemFieldsQuery .= '}';
		yield 'rejects queries using the `item` field too many times' => [
			$tooManyItemFieldsQuery,
			"The query complexity is $percentageOverMaxComplexity% over the limit.",
		];

		$makeItemListArg = fn( int $number ) => implode(
			', ',
			array_fill( 0, $number, "\"$itemId\"" )
		);
		yield 'rejects queries requesting too many items in itemsById field' => [
			'{ itemsById(ids: [' . $makeItemListArg( $tooManyItems ) . ']) { id } }',
			"The query complexity is $percentageOverMaxComplexity% over the limit.",
		];

		$itemsById = (int)floor( $tooManyItems / 2 );
		$itemFieldUses = ceil( $tooManyItems / 2 );
		$complexQuery = '{
			itemsById(ids: [' . $makeItemListArg( $itemsById ) . ']) { id }';
		for ( $i = 0; $i < $itemFieldUses; $i++ ) {
			$complexQuery .= "\nitem$i: item(id: \"$itemId\") { id }";
		}
		$complexQuery .= '}';
		yield 'rejects queries requesting too many items using both itemsById and item fields' => [
			$complexQuery,
			"The query complexity is $percentageOverMaxComplexity% over the limit.",
		];

		yield 'item does not exist - item field' => [
			'{ item(id: "Q9999999") { id } }',
			'Item "Q9999999" does not exist.',
			[ 'item' => null ],
		];

		yield 'item does not exist - itemsById field' => [
			"{ itemsById(ids: [\"Q666666\", \"$itemId\"]) { id } }",
			'Item "Q666666" does not exist.',
			[ 'itemsById' => [ null, [ 'id' => $itemId ] ] ],
		];
	}

	/**
	 * @dataProvider redirectProvider
	 */
	public function testRedirect( string $query, array $expectedResult, EntityLookup $lookup ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newGraphQLService( $lookup )->query( $query )
		);
	}

	public function redirectProvider(): Generator {
		$redirectSourceId = new ItemId( 'Q99' );
		$redirectTargetId = new ItemId( 'Q100' );
		$redirectTarget = new Item( $redirectTargetId );

		$lookupMock = $this->createStub( EntityLookup::class );
		$lookupMock->expects( $this->once() )
			->method( 'getEntity' )
			->with( $redirectSourceId )
			->willReturn( $redirectTarget );

		yield 'single redirected item' => [
			"{ item(id: \"{$redirectSourceId}\") { id } }",
			[
				'data' => [ 'item' => [ 'id' => $redirectTargetId ] ],
				'extensions' => [
					QueryContext::KEY_MESSAGE => QueryContext::MESSAGE_REDIRECTS,
					QueryContext::KEY_REDIRECTS => [ "$redirectSourceId" => "$redirectTargetId" ],
				],
			],
			$lookupMock,
		];

		$itemId = new ItemId( 'Q97' );
		$item = new Item( $itemId );
		$redirectSource1 = new ItemId( 'Q98' );
		$redirectSource2 = new ItemId( 'Q99' );
		$redirectTargetId1 = new ItemId( 'Q100' );
		$redirectTargetId2 = new ItemId( 'Q101' );
		$redirectTarget1 = new Item( $redirectTargetId1 );
		$redirectTarget2 = new Item( $redirectTargetId2 );

		$lookupMock = $this->createStub( EntityLookup::class );
		$lookupMock->expects( $this->exactly( 3 ) )
			->method( 'getEntity' )
			->withConsecutive( [ $itemId ], [ $redirectSource1 ], [ $redirectSource2 ] )
			->willReturnOnConsecutiveCalls( $item, $redirectTarget1, $redirectTarget2 );

		yield 'redirects in itemsById' => [
			"{ itemsById(ids: [ \"$itemId\", \"$redirectSource1\", \"$redirectSource2\" ] ) { id } }",
			[
				'data' => [
					'itemsById' => [
						[ 'id' => "$itemId" ],
						[ 'id' => "$redirectTargetId1" ],
						[ 'id' => "$redirectTargetId2" ],
					],
				],
				'extensions' => [
					QueryContext::KEY_MESSAGE => QueryContext::MESSAGE_REDIRECTS,
					QueryContext::KEY_REDIRECTS => [
						"$redirectSource1" => "$redirectTargetId1",
						"$redirectSource2" => "$redirectTargetId2",
					],
				],
			],
			$lookupMock,
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

		$revisionLookup = $this->createStub( EntityRevisionLookup::class );
		$revisionLookup->method( 'getLatestRevisionId' )->willReturnCallback(
			fn( ItemId $id ) => $id->equals( self::$redirectSource )
				? LatestRevisionIdResult::redirect( 321, self::$redirectTarget )
				: LatestRevisionIdResult::concreteRevision( 1, '20260101001122' )
		);
		$this->setService( 'WikibaseRepo.EntityRevisionLookup', $revisionLookup );

		$this->setTemporaryHook( 'WikibaseRepoDataTypes', function ( array &$dataTypes ): void {
			$dataTypes['PT:' . self::CUSTOM_ENTITY_DATA_TYPE] = [
				'value-type' => 'wikibase-entityid',
			];
		} );
		$this->resetServices();

		return WbReuse::getGraphQLService();
	}
}
