<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer
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

	public static function descriptionsProvider(): Generator {
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
