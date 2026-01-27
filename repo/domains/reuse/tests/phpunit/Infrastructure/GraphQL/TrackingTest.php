<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService;
use Wikibase\Repo\Domains\Reuse\WbReuse;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TrackingTest extends MediaWikiIntegrationTestCase {

	private const EXISTING_ITEM_ID = 'Q123';

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testTracking( string $query, array $metrics ): void {
		$statsHelper = StatsFactory::newUnitTestingHelper();

		$this->newGraphQLService( $statsHelper->getStatsFactory() )
			->query( $query );

		foreach ( $metrics as $metric ) {
			$this->assertSame( 1, $statsHelper->count( $metric ) );
		}
	}

	public function queryProvider(): Generator {
		yield 'success' => [
			'{ item(id: "' . self::EXISTING_ITEM_ID . '") { id } }',
			[ 'wikibase_graphql_hit_total{status="success"}' ],
		];

		yield 'error - malformed query' => [
			'{ fieldDoesNotExist }',
			[ 'wikibase_graphql_hit_total{status="error"}' ],
		];

		yield 'partial success - item not found' => [
			'{ item(id: "Q9999") { id } }',
			[ 'wikibase_graphql_hit_total{status="partial_success"}' ],
		];
	}

	private function newGraphQLService( StatsFactory $stats ): GraphQLService {
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity( NewItem::withId( self::EXISTING_ITEM_ID )->build() );
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );
		$this->setService( 'StatsFactory', $stats );
		$this->resetServices();

		return WbReuse::getGraphQLService();
	}
}
