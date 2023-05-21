<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsSerializerTest extends TestCase {

	/**
	 * @dataProvider labelsProvider
	 */
	public function testSerialize( Labels $labels, ArrayObject $serialization ): void {
		$this->assertEquals(
			$serialization,
			( new LabelsSerializer() )->serialize( $labels )
		);
	}

	public static function labelsProvider(): Generator {
		yield 'empty' => [
			new Labels(),
			new ArrayObject( [] ),
		];

		yield 'multiple labels' => [
			new Labels(
				new Label( 'en', 'potato' ),
				new Label( 'ko', '감자' ),
			),
			new ArrayObject( [
				'en' => 'potato',
				'ko' => '감자',
			] ),
		];
	}

}
