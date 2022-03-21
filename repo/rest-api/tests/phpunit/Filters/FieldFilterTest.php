<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Filters\FieldFilter;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Filters\FieldFilter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FieldFilterTest extends TestCase {

	/**
	 * @dataProvider filterDataProvider
	 */
	public function testFilter( array $itemSerialization, array $fields, array $expectedResult ): void {
		$fieldFilter = new FieldFilter( $fields );
		$this->assertSame( $expectedResult, $fieldFilter->filter( $itemSerialization ) );
	}

	public function filterDataProvider(): \Generator {
		$itemSerialization = [
			"id" => "Q123",
			"type" => "item",
			"labels" => [
				"en" => [ "language" => "en", "value" => "an-english-label" ]
			],
			"statements" => [
				[
					"mainsnak" => [
						"snaktype" => "value",
						"property" => "P1",
						"hash" => "03b2fc82370ce68da9a1c9603fbf958a5a855177",
						"datavalue" => [ "value" => "á", "type" => "string" ]
					]
				]
			]
		];

		yield "labels only" => [
			$itemSerialization,
			[ "labels" ],

			[
				"id" => "Q123",
				"labels" => [
					"en" => [ "language" => "en", "value" => "an-english-label" ]
				]
			]
		];

		yield "type and labels" => [
			$itemSerialization,
			[ "type", "labels" ],
			[
				"id" => "Q123",
				"type" => "item",
				"labels" => [
					"en" => [ "language" => "en", "value" => "an-english-label" ]
				]
			]
		];

		yield "type, labels, and statements" => [
			$itemSerialization,
			[ "type", "labels", "statements" ],
			[
				"id" => "Q123",
				"type" => "item",
				"labels" => [
					"en" => [ "language" => "en", "value" => "an-english-label" ]
				],
				"statements" => [
					[
						"mainsnak" => [
							"snaktype" => "value",
							"property" => "P1",
							"hash" => "03b2fc82370ce68da9a1c9603fbf958a5a855177",
							"datavalue" => [ "value" => "á", "type" => "string" ]
						]
					]
				]
			]
		];

		yield "type and statements" => [
			$itemSerialization,
			[ "type", "statements" ],
			[
				"id" => "Q123",
				"type" => "item",
				"statements" => [
					[
						"mainsnak" => [
							"snaktype" => "value",
							"property" => "P1",
							"hash" => "03b2fc82370ce68da9a1c9603fbf958a5a855177",
							"datavalue" => [ "value" => "á", "type" => "string" ]
						]
					]
				]
			]
		];
	}
}
