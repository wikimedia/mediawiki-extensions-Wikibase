<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Domains\Reuse\WbReuse;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLService
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValidationTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider queryProvider
	 */
	public function testQuery( string $query, string $expectedResult ): void {
		$graphQLService = WbReuse::getGraphQLService();
		$result = $graphQLService->query( $query );

		$this->assertEquals(
			$expectedResult,
			$result['errors'][0]['message']
		);
	}

	public function queryProvider(): Generator {
		yield 'invalid query - syntactically invalid: missing closing brace' => [
			'{ Item(id: "Q1" ) { id }',
			'Invalid query - Syntax Error: Expected Name, found <EOF>',
		];

		yield 'invalid query - empty query' => [
			'',
			'message' => "The 'query' field is required and must not be empty",
		];

		yield 'invalid query - unknown field' => [
			'{ fieldDoesNotExist }',
			'Cannot query field "fieldDoesNotExist" on type "Query".',
		];
	}
}
