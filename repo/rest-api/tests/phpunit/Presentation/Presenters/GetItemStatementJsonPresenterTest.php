<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation\Presenters;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementSuccessResponse;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\GetItemStatementJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementJsonPresenterTest extends TestCase {

	/**
	 * @dataProvider statementSerializationProvider
	 */
	public function testGetJsonForSuccess( array $serialization, string $expectedOutput ): void {
		$presenter = new GetItemStatementJsonPresenter();

		$this->assertJsonStringEqualsJsonString(
			$expectedOutput,
			$presenter->getJson(
				new GetItemStatementSuccessResponse( $serialization, '20220307180000', 321 )
			)
		);
	}

	public function statementSerializationProvider(): Generator {
		yield 'converts empty qualifiers array to object' => [
			[
				'id' => 'Q1$943B784F-FFBC-43A9-B7AA-79C3546AA0EF',
				'qualifiers' => []
			],
			'{ "id": "Q1$943B784F-FFBC-43A9-B7AA-79C3546AA0EF", "qualifiers": {} }'
		];

		yield 'serializes when no qualifiers exist' => [
			[
				'id' => 'Q1$943B784F-FFBC-43A9-B7AA-79C3546AA0EF',
				'type' => 'statement',
				'rank' => 'normal'
			],
			'{
				"id": "Q1$943B784F-FFBC-43A9-B7AA-79C3546AA0EF",
				"type": "statement",
				"rank": "normal"
			}'
		];
	}
}
