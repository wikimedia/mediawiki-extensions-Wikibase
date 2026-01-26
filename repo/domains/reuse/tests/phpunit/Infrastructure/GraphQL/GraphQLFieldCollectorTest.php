<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\GraphQL;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLFieldCollector;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\GraphQLFieldCollector
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GraphQLFieldCollectorTest extends TestCase {

	/**
	 * @dataProvider queryProvider
	 */
	public function testQuery( string $query, array $expectedResult, ?string $operationName = null ): void {
		$fieldTracker = new GraphQLFieldCollector();

		$this->assertEquals(
			$expectedResult,
			$fieldTracker->getRequestedFieldPaths( $query, $operationName )
		);
	}

	public function queryProvider(): Generator {
		yield 'simple query' => [
			'{ item(id: "Q1") {
				id
				label(languageCode: "en")
			} }',
			[ 'item', 'item_id', 'item_label' ],
		];

		yield 'query with inline fragment' => [
			'{ item(id: "Q1") {
				... on LabelProvider {
					label(languageCode: "en")
				}
			} }',
			[ 'item', 'item_label' ],
		];

		yield 'query with fragment spread' => [
			'fragment EnglishLabel on LabelProvider {
				label(languageCode: "en")
			}
			{ item(id: "Q1") {
				... EnglishLabel
			} }',
			[ 'item', 'item_label' ],
		];

		yield 'query with redundant field in fragment spread' => [
			'fragment EnglishLabel on LabelProvider {
				label(languageCode: "en")
			}
			{ item(id: "Q1") {
				label(languageCode: "en")
				... EnglishLabel
			} }',
			[ 'item', 'item_label' ],
		];

		yield 'multiple usages of the same field via alias' => [
			'{ item(id: "Q1") {
				enLabel: label(languageCode: "en")
				deLabel: label(languageCode: "de")
			} }',
			[ 'item', 'item_label', 'item_label' ],
		];

		yield 'field used redundantly with same name' => [
			'{ item(id: "Q1") {
				label(languageCode: "en")
				label(languageCode: "en")
			} }',
			[ 'item', 'item_label' ],
		];

		yield 'multiple operations' => [
			'query Query1 {
				item(id: "Q1") { id }
			}
			query Query2 {
				itemsById(ids: ["Q1"]) { id }
			}',
			[ 'itemsById', 'itemsById_id' ],
			'Query2',
		];

		yield 'multiple operations in request but none selected -> returns empty list' => [
			'query Query1 {
				item(id: "Q1") { id }
			}
			query Query2 {
				itemsById(ids: ["Q1"]) { id }
			}',
			[],
		];
	}
}
