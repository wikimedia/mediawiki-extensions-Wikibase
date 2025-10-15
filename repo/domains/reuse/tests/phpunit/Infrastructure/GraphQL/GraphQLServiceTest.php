<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use DataValues\StringValue;
use GraphQL\GraphQL;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\SiteLookup;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
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

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	public function testIdQuery(): void {
		$itemId = 'Q123';

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( NewItem::withId( $itemId )->build() );

		$this->assertEquals(
			[ 'data' => [ 'item' => [ 'id' => $itemId ] ] ],
			$this->newGraphQLService( $entityLookup )->query( "query { item(id: \"$itemId\") { id } }" )
		);
	}

	public function testLabelsQuery(): void {
		$itemId = 'Q123';
		$enLabel = 'potato';

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $itemId )
				->andLabel( 'en', $enLabel )
				->build()
		);

		$this->assertEquals(
			[ 'data' => [ 'item' => [ 'enLabel' => $enLabel, 'deLabel' => null ] ] ],
			$this->newGraphQLService( $entityLookup )->query( "
			query { item(id: \"$itemId\") {
				enLabel: label(languageCode: \"en\")
				deLabel: label(languageCode: \"de\")
			} }" )
		);
	}

	public function testDescriptionsQuery(): void {
		$itemId = 'Q123';
		$enDescription = 'root vegetable';

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $itemId )
				->andDescription( 'en', $enDescription )
				->build()
		);

		$this->assertEquals(
			[ 'data' => [ 'item' => [ 'enDescription' => $enDescription, 'deDescription' => null ] ] ],
			$this->newGraphQLService( $entityLookup )->query( "
			query { item(id: \"$itemId\") {
				enDescription: description(languageCode: \"en\")
				deDescription: description(languageCode: \"de\")
			} }" )
		);
	}

	public function testAliasesQuery(): void {
		$itemId = 'Q123';
		$enAliases = [ 'spud', 'tater' ];

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $itemId )
				->andAliases( 'en', $enAliases )
				->build()
		);

		$this->assertEquals(
			[ 'data' => [ 'item' => [ 'enAliases' => $enAliases, 'deAliases' => [] ] ] ],
			$this->newGraphQLService( $entityLookup )->query( "
			query { item(id: \"$itemId\") {
				enAliases: aliases(languageCode: \"en\")
				deAliases: aliases(languageCode: \"de\")
			} }" )
		);
	}

	public function testSitelinkQuery(): void {
		$itemId = 'Q123';
		$sitelinkSiteId = 'examplewiki';
		$otherSiteId = 'otherSiteId';
		$sitelinkTitle = 'Potato';

		$sitelinkSite = new MediaWikiSite();
		$sitelinkSite->setLinkPath( 'https://wiki.example/wiki/$1' );
		$expectedSitelinkUrl = "https://wiki.example/wiki/$sitelinkTitle";
		$sitelinkSite->setGlobalId( $sitelinkSiteId );

		$siteIdProvider = $this->createStub( SiteLinkGlobalIdentifiersProvider::class );
		$siteIdProvider->method( 'getList' )->willReturn( [ $sitelinkSiteId, $otherSiteId ] );

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $itemId )
				->andSiteLink( $sitelinkSiteId, $sitelinkTitle )
				->build()
		);

		$this->assertEquals(
			[
				'data' => [
					'item' => [
						'sitelink1' => [ 'title' => $sitelinkTitle, 'url' => $expectedSitelinkUrl ],
						'sitelink2' => null,
					],
				],
			],
			$this->newGraphQLService( $entityLookup, new HashSiteStore( [ $sitelinkSite ] ), $siteIdProvider )->query(
				"
				query { item(id: \"$itemId\") {
					sitelink1: sitelink(siteId: \"$sitelinkSiteId\") { title url }
					sitelink2: sitelink(siteId: \"$otherSiteId\") { title }
				} }"
			)
		);
	}

	public function testStatementsWithIdAndRankQuery(): void {
		$itemId = 'Q123';
		$statement = NewStatement::noValueFor( 'P1' )
			->withGuid( "$itemId\$bed933b7-4207-d679-7571-3630cfb49d7f" )
			->withRank( 1 )
			->build();
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( NewItem::withId( $itemId )->andStatement( $statement )->build() );

		$this->assertEquals(
			[
				'data' => [
					'item' => [
						'P1' => [
							[
								'id' => $statement->getGuid(),
								'rank' => 'normal',
								'property' => [
									'id' => $statement->getMainSnak()->getPropertyId()->getSerialization(),
									'dataType' => 'string',
								],
							],
						],
						'P3' => [],
					],
				],
			],
			$this->newGraphQLService( $entityLookup )->query( "
			query { item(id: \"$itemId\") {
				P1: statements(propertyId: \"P1\"){ id rank property{ id dataType } },
				P3: statements(propertyId: \"P3\"){ id rank property{ id dataType } },
			} }" )
		);
	}

	// Include the value in the qualifier during property value implementation.
	public function testStatementsWithQualifiersQuery(): void {
		$itemId = 'Q123';
		$statementPropertyId = 'P1';
		$itemStatementQualifierPropertyId = 'P24';
		$statement1 = NewStatement::noValueFor( $statementPropertyId )
			->withGuid( "$itemId\$bed933b7-4207-d679-7571-3630cfb49d7f" )
			->withQualifier( new NumericPropertyId( $itemStatementQualifierPropertyId ), new StringValue( 'stringValue' ) )
			->build();
		$qualifier = $statement1->getQualifiers()[0];
		$statement2 = NewStatement::noValueFor( $statementPropertyId )
			->withGuid( "$itemId\$bed933b7-4207-d679-7571-3630cfb49d8f" )
			->build();
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( NewItem::withId( $itemId )
			->andStatement( $statement1 )
			->andStatement( $statement2 )
			->build()
		);

		$this->assertEquals(
			[
				'data' => [
					'item' => [
						$statementPropertyId => [
							[
								'id' => $statement1->getGuid(),
								'qualifiers' => [
									[
										'property' => [
											'id' => $qualifier->getPropertyId()->getSerialization(),
											'dataType' => 'string',
										],
									],
								],
							],
							[
								'id' => $statement2->getGuid(),
								'qualifiers' => [],
							],
						],
					],
				],
			],
			$this->newGraphQLService( $entityLookup )->query( "
			query { item(id: \"$itemId\") {
			 $statementPropertyId: statements(propertyId: \"$statementPropertyId\"){
			 id qualifiers(propertyId: \"$itemStatementQualifierPropertyId\"){ property{ id dataType } } }
			} }" )
		);
	}

	public function testPredicatePropertyLabelsQuery(): void {
		$itemId = 'Q123';
		$statementProperty = new Property(
			new NumericPropertyId( 'P1' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'statement prop' ) ] ) ),
			'string',
		);
		$qualifierProperty = new Property(
			new NumericPropertyId( 'P2' ),
			new Fingerprint( new TermList( [ new Term( 'en', 'qualifier prop' ) ] ) ),
			'string',
		);
		$statement = NewStatement::noValueFor( $statementProperty->getId() )
			->withGuid( "$itemId\$bed933b7-4207-d679-7571-3630cfb49d8f" )
			->withQualifier( $qualifierProperty->getId(), 'some qualifier value' )
			->build();

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $itemId )
				->andStatement( $statement )
				->build()
		);

		$termLookup = new InMemoryPrefetchingTermLookup();
		$termLookup->setData( [ $statementProperty, $qualifierProperty ] );

		$this->assertEquals(
			[
				'data' => [
					'item' => [
						'statements' => [
							[
								'property' => [ 'label' => $statementProperty->getLabels()->getByLanguage( 'en' )->getText() ],
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
			$this->newGraphQLService( $entityLookup, termLookup: $termLookup )->query(
				"
				query { item(id: \"$itemId\") {
					statements(propertyId: \"{$statementProperty->getId()}\") {
						property {
							label(languageCode: \"en\")
						}
						qualifiers(propertyId: \"{$qualifierProperty->getId()}\") {
							property {
								label(languageCode: \"en\")
							}
						}
					}
				} }"
			)
		);
	}

	public function testGivenItemDoesNotExist_returnsNull(): void {
		$itemId = 'Q999999';

		$entityLookup = new InMemoryEntityLookup();

		$this->assertEquals(
			[ 'data' => [ 'item' => null ] ],
			$this->newGraphQLService( $entityLookup )->query( "query { item(id: \"$itemId\") { id } }" )
		);
	}

	public function testMultipleItemsAtOnce(): void {
		$item1Id = 'Q123';
		$item1Label = 'potato';
		$item2Id = 'Q321';
		$item2Label = 'garlic';

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $item1Id )
				->andLabel( 'en', $item1Label )
				->build()
		);
		$entityLookup->addEntity(
			NewItem::withId( $item2Id )
				->andLabel( 'en', $item2Label )
				->build()
		);

		$this->assertEquals(
			[
				'data' => [
					'item1' => [ 'label' => $item1Label ],
					'item2' => [ 'label' => $item2Label ],
				],
			],
			$this->newGraphQLService( $entityLookup )->query( "
			query {
				item1: item(id: \"$item1Id\") { label(languageCode: \"en\") }
				item2: item(id: \"$item2Id\") { label(languageCode: \"en\") }
			}" )
		);
	}

	public function testValidatesItemIdArg(): void {
		$id = 'P123';
		$result = $this->newGraphQLService( new InMemoryEntityLookup() )
			->query( "{ item(id: \"{$id}\") { id } }" );

		$this->assertSame(
			"Not a valid Item ID: \"$id\"",
			$result['errors'][0]['message']
		);
	}

	public function testValidatesSiteIdArg(): void {
		$siteId = 'not-a-valid-site-id';
		$itemId = 'Q123';

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( NewItem::withId( $itemId )->build() );

		$result = $this->newGraphQLService( $entityLookup )
			->query( "{ item(id: \"$itemId\") {
				sitelink(siteId: \"$siteId\") { title }
			 } }" );

		$this->assertSame(
			"Not a valid site ID: \"$siteId\"",
			$result['errors'][0]['message']
		);
	}

	/**
	 * @dataProvider languageCodeFieldProvider
	 */
	public function testValidatesLanguageCodes( string $field ): void {
		$itemId = 'Q123';
		$languageCode = 'invalid-language-code';
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( NewItem::withId( $itemId )->build() );

		$result = $this->newGraphQLService( $entityLookup )
			->query( "{ item(id: \"$itemId\") {
				$field(languageCode: \"$languageCode\")
			} }" );

		$this->assertSame(
			"Not a valid language code: \"$languageCode\"",
			$result['errors'][0]['message']
		);
	}

	public static function languageCodeFieldProvider(): array {
		return [ [ 'label' ], [ 'description' ], [ 'aliases' ] ];
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
