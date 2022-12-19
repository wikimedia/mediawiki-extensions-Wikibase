<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\ReferenceSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReferenceSerializerTest extends TestCase {

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialize( Reference $reference, array $expectedSerialization ): void {
		$this->assertEquals(
			$expectedSerialization,
			$this->newSerializer()->serialize( $reference )
		);
	}

	public function serializationProvider(): Generator {
		$ref1 = new Reference( [
			new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ),
		] );
		yield 'reference with one prop value pair' => [
			$ref1,
			[
				'hash' => $ref1->getHash(),
				'parts' => [
					[ 'property' => 'P123 property', 'value' => 'P123 value' ],
				],
			],
		];

		$ref2 = new Reference( [
			new PropertyNoValueSnak( new NumericPropertyId( 'P234' ) ),
			new PropertyNoValueSnak( new NumericPropertyId( 'P345' ) ),
		] );
		yield 'reference with multiple prop value pairs' => [
			$ref2,
			[
				'hash' => $ref2->getHash(),
				'parts' => [
					[ 'property' => 'P234 property', 'value' => 'P234 value' ],
					[ 'property' => 'P345 property', 'value' => 'P345 value' ],
				],
			],
		];
	}

	private function newSerializer(): ReferenceSerializer {
		$propertyValuePairSerializer = $this->createStub( PropertyValuePairSerializer::class );
		$propertyValuePairSerializer->method( 'serialize' )
			->willReturnCallback(
				fn( Snak $snak ) => [
					'property' => $snak->getPropertyId() . ' property',
					'value' => $snak->getPropertyId() . ' value',
				]
			);

		return new ReferenceSerializer( $propertyValuePairSerializer );
	}

}
