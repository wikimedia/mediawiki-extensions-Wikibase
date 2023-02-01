<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Serialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Serialization\DescriptionsSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\DescriptionsSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DescriptionsSerializerTest extends TestCase {

	/**
	 * @dataProvider descriptionsProvider
	 */
	public function testSerialize( Descriptions $descriptions, ArrayObject $serialization ): void {
		$this->assertEquals(
			$serialization,
			( new DescriptionsSerializer() )->serialize( $descriptions )
		);
	}

	public function descriptionsProvider(): Generator {
		yield 'empty' => [
			new Descriptions(),
			new ArrayObject( [] ),
		];

		yield 'multiple descriptions' => [
			new Descriptions(
				new Description( 'en', 'third planet from the Sun in the Solar System' ),
				new Description( 'ar', 'الكوكب الثالث في المجموعة الشمسية' ),
			),
			new ArrayObject( [
				'en' => 'third planet from the Sun in the Solar System',
				'ar' => 'الكوكب الثالث في المجموعة الشمسية',
			] ),
		];
	}

}
