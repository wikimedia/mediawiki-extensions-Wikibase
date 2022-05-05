<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementsJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsSuccessResponse;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementsJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsJsonPresenterTest extends TestCase {

	/**
	 * @dataProvider statementsSerializationProvider
	 */
	public function testGetJsonForSuccess( array $itemSerialization, string $expectedOutput ): void {
		$presenter = new GetItemStatementsJsonPresenter();

		$this->assertJsonStringEqualsJsonString(
			$expectedOutput,
			$presenter->getJson(
				new GetItemStatementsSuccessResponse( $itemSerialization, '20220307180000', 321 )
			)
		);
	}

	public function statementsSerializationProvider(): Generator {
		yield 'converts empty top-level array to object' => [
			[],
			'{}'
		];

		yield 'converts empty qualifiers object' => [
			[ 'P123' => [
					[ 'qualifiers' => [] ],
					[ 'qualifiers' => [] ],
				],
				'P321' => [
					[ 'qualifiers' => [] ],
				],
			],
			'{"P123":[{"qualifiers":{}},{"qualifiers":{}}],"P321":[{"qualifiers":{}}]}'
		];
	}

}
