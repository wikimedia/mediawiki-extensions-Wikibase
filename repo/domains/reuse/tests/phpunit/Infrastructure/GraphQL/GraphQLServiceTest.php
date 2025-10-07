<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\GraphQL;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\SiteLookup;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
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
	): GraphQLService {
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );
		$this->setService( 'SiteLookup', $siteLookup ?? new HashSiteStore() );
		$this->setService(
			'WikibaseRepo.SiteLinkGlobalIdentifiersProvider',
			$siteLinkGlobalIdentifiersProvider ?? $this->createStub( SiteLinkGlobalIdentifiersProvider::class )
		);

		return WbReuse::getGraphQLService();
	}
}
