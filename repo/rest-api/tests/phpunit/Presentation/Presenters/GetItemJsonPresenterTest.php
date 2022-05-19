<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation\Presenters;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemDataSerializer;
use Wikibase\Repo\RestApi\Presentation\Presenters\GetItemJsonPresenter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;

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
	public function testGetJsonForSuccess( array $itemSerialization, string $expectedOutput ): void {
		$itemData = $this->createStub( ItemData::class );

		$serializer = $this->createMock( ItemDataSerializer::class );
		$serializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $itemData )
			->willReturn( $itemSerialization );

		$presenter = new GetItemJsonPresenter( $serializer );

		$this->assertJsonStringEqualsJsonString(
			$expectedOutput,
			$presenter->getJson(
				new GetItemSuccessResponse( $itemData, '20220307180000', 321 )
			)
		);
	}

	public function itemSerializationProvider(): Generator {
		yield 'converts empty top-level object fields' => [
			[ 'labels' => [], 'descriptions' => [], 'aliases' => [], 'statements' => [], 'sitelinks' => [] ],
			'{"labels":{},"descriptions":{},"aliases":{},"statements":{},"sitelinks":{}}'
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
			'{"statements":{"P123":[{"qualifiers":{}},{"qualifiers":{}}],"P321":[{"qualifiers":{}}]}}'
		];
	}

}
