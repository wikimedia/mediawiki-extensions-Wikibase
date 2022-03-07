<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation;

use Generator;
use PHPUnit\Framework\TestCase;
use stdClass;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemResult;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemJsonPresenterTest extends TestCase {

	/**
	 * @dataProvider itemSerializationProvider
	 */
	public function testGetJsonEncodableItem( array $itemSerialization, array $expectedOutput ): void {
		$presenter = new GetItemJsonPresenter();

		$this->assertEquals(
			$expectedOutput,
			$presenter->getJsonEncodableItem(
				new GetItemResult( $itemSerialization, '20220307180000', 321 )
			)
		);
	}

	public function itemSerializationProvider(): Generator {
		yield 'converts empty top-level object fields' => [
			[ 'labels' => [], 'descriptions' => [], 'aliases' => [], 'statements' => [], 'sitelinks' => [] ],
			[
				'labels' => new stdClass(),
				'descriptions' => new stdClass(),
				'aliases' => new stdClass(),
				'statements' => new stdClass(),
				'sitelinks' => new stdClass(),
			],
		];

		yield 'converts empty qualifiers object' => [
			[ 'statements' => [
				'P123' => [
					[ 'qualifiers' => [] ],
					[ 'qualifiers' => [] ],
				],
				'P321' => [
					[ 'qualifiers' => [] ],
				],
			] ],
			[ 'statements' => [
				'P123' => [
					[ 'qualifiers' => new stdClass() ],
					[ 'qualifiers' => new stdClass() ],
				],
				'P321' => [
					[ 'qualifiers' => new stdClass() ],
				],
			] ]
		];
	}

}
