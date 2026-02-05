<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLErrorType;
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
	private const EXPLODING_ITEM_ID = 'Q666';

	/**
	 * @dataProvider queryProvider
	 */
	public function testHitTracking( string $query, array $metrics ): void {
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

		yield 'error - invalid query' => [
			'{ fieldDoesNotExist }',
			[ 'wikibase_graphql_hit_total{status="error"}' ],
		];

		yield 'partial success - item not found' => [
			'{ item(id: "Q9999") { id } }',
			[ 'wikibase_graphql_hit_total{status="partial_success"}' ],
		];
	}

	/**
	 * @dataProvider errorQueryProvider
	 */
	public function testErrorTracking( string $query, array $expectedMetrics ): void {
		$statsHelper = StatsFactory::newUnitTestingHelper();

		$this->newGraphQLService( $statsHelper->getStatsFactory() )
			->query( $query );

		foreach ( $expectedMetrics as $metric ) {
			$this->assertSame( 1, $statsHelper->count( $metric ) );
		}

		$otherMetrics = array_diff( $statsHelper->getAllFormatted(), $expectedMetrics );
		foreach ( $otherMetrics as $metric ) {
			$this->assertStringStartsNotWith(
				'wikibase_graphql_error_total',
				$metric,
				"$metric was not expected to be tracked",
			);
		}
	}

	public function errorQueryProvider(): Generator {
		yield 'no errors' => [
			'{ item(id: "' . self::EXISTING_ITEM_ID . '") { id } }',
			[],
		];

		yield 'invalid query - syntactically invalid' => [
			'{ fieldDoesNotExist',
			[ 'wikibase_graphql_error_total{type="' . GraphQLErrorType::INVALID_QUERY->name . '"}' ],
		];

		yield 'invalid query - unknown field' => [
			'{ fieldDoesNotExist }',
			[ 'wikibase_graphql_error_total{type="' . GraphQLErrorType::INVALID_QUERY->name . '"}' ],
		];

		$tooManyItems = implode( ',', array_fill( 0, 51, '"Q1"' ) );
		yield 'query too complex' => [
			"{ itemsById(ids: [$tooManyItems]) { id } }",
			[ 'wikibase_graphql_error_total{type="' . GraphQLErrorType::QUERY_TOO_COMPLEX->name . '"}' ],
		];

		yield 'item not found' => [
			'{ item(id: "Q999999") { id } }',
			[ 'wikibase_graphql_error_total{type="' . GraphQLErrorType::ITEM_NOT_FOUND->name . '"}' ],
		];

		yield 'unknown error' => [
			'{ item(id: "' . self::EXPLODING_ITEM_ID . '") { id } }',
			[ 'wikibase_graphql_error_total{type="' . GraphQLErrorType::UNKNOWN->name . '"}' ],
		];

		yield 'multiple occurrences of the same error -> reported only once' => [
			'{ itemsById(ids: ["Q99999", "Q98765"]) { id } }',
			[ 'wikibase_graphql_error_total{type="' . GraphQLErrorType::ITEM_NOT_FOUND->name . '"}' ],
		];

		// TODO add a test for multiple errors once there is another non-fatal type like ITEM_NOT_FOUND
	}

	/**
	 * @dataProvider fieldUsageQueryProvider
	 */
	public function testFieldUsageTracking( string $query, array $expectedMetrics ): void {
		$statsHelper = StatsFactory::newUnitTestingHelper();

		$this->newGraphQLService( $statsHelper->getStatsFactory() )
			->query( $query );

		foreach ( $expectedMetrics as $metricName => $count ) {
			$this->assertSame( $count, $statsHelper->count( $metricName ) );
		}

		$otherMetrics = array_diff( $statsHelper->getAllFormatted(), $expectedMetrics );
		foreach ( $otherMetrics as $metric ) {
			$this->assertStringStartsNotWith(
				'wikibase_graphql_field_usage_total',
				$metric,
				"$metric was not expected to be tracked",
			);
		}
	}

	public function fieldUsageQueryProvider(): Generator {
		yield 'only errors, no field usage tracked' => [
			'{ fieldDoesNotExist }',
			[],
		];

		yield 'tracks field usage on partial success' => [
			'{ item(id: "Q999999") { id } }',
			[
				'wikibase_graphql_field_usage_total{field="item"}' => 1,
				'wikibase_graphql_field_usage_total{field="item_id"}' => 1,
			],
		];

		yield 'tracks field usage on success' => [
			'{
				item1: item(id: "' . self::EXISTING_ITEM_ID . '") { id }
				item2: item(id: "' . self::EXISTING_ITEM_ID . '") { id }
			}',
			[
				'wikibase_graphql_field_usage_total{field="item"}' => 2,
				'wikibase_graphql_field_usage_total{field="item_id"}' => 2,
			],
		];
	}

	private function newGraphQLService( StatsFactory $stats ): GraphQLService {
		$entityLookup = $this->createStub( EntityLookup::class );
		$entityLookup->method( 'getEntity' )->willReturnCallback( function ( ItemId $id ) {
			return match ( $id->getSerialization() ) {
				self::EXISTING_ITEM_ID => NewItem::withId( self::EXISTING_ITEM_ID )->build(),
				self::EXPLODING_ITEM_ID => throw new RuntimeException( 'unexpected error' ),
				default => null,
			};
		} );
		$this->setService( 'WikibaseRepo.EntityLookup', $entityLookup );
		$this->setService( 'StatsFactory', $stats );
		$this->resetServices();

		return WbReuse::getGraphQLService();
	}
}
