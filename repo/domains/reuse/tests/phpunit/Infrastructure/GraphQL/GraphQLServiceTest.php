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

	public function testGivenItemDoesNotExist_returnsNull(): void {
		$itemId = 'Q999999';

		$entityLookup = new InMemoryEntityLookup();

		$this->assertEquals(
			[ 'data' => [ 'item' => null ] ],
			$this->newGraphQLService( $entityLookup )->query( "query { item(id: \"$itemId\") { id } }" )
		);
	}

	private function newGraphQLService( EntityLookup $entityLookup ): GraphQLService {
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );
		return WbReuse::getGraphQLService();
	}
}
