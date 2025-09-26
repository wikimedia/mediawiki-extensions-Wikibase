<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\WbReuse;

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

	private function newGraphQLService( EntityLookup $entityLookup ): GraphQLService {
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );
		return WbReuse::getGraphQLService();
	}
}
