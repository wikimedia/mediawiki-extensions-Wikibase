<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Specials;

use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\GraphQLPrototype\GraphQLQueryService;

/**
 * @covers \Wikibase\Repo\GraphQLPrototype\GraphQLQueryService
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GraphQLQueryServiceTest extends MediaWikiIntegrationTestCase {

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testQuery( string $query, array $expectedResult ): void {
		$queryService = new GraphQLQueryService();

		$this->assertEquals(
			$expectedResult,
			$queryService->query( $query )
		);
	}

	public static function queryProvider() {
		yield 'get id' => [
			'query { item(id: "Q71") { id } }',
			[ 'data' => [ 'item' => [ 'id' => 'Q71' ] ] ],
		];
	}

}
