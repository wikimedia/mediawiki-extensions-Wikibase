<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Model\ErrorReporter;
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
	public function testGetJsonItemForSuccess( array $itemSerialization, string $expectedOutput ): void {
		$presenter = new GetItemJsonPresenter();

		$this->assertEquals(
			$expectedOutput,
			$presenter->getJsonItem(
				GetItemResult::newSuccessResult( $itemSerialization, '20220307180000', 321 )
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

	/**
	 * @dataProvider errorReporterProvider
	 */
	public function testGetJsonItemForFailure( ErrorReporter $errorReporter, string $expectedOutput ): void {
		$presenter = new GetItemJsonPresenter();

		$this->assertEquals(
			$expectedOutput,
			$presenter->getJsonItem(
				GetItemResult::newFailureResult( $errorReporter )
			)
		);
	}

	public function errorReporterProvider(): Generator {
		yield 'converts error' => [
			new ErrorReporter( 'item-not-found', "Could not find an item with the ID Q123" ),
			'{"code":"item-not-found","message":"Could not find an item with the ID Q123"}'
		];
	}
}
