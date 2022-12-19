<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\ReferenceSerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class ReferenceSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$snakListSerializerMock = $this->createMock( Serializer::class );
		$snakListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SnakList( [] ) ) )
			->will( $this->returnValue( [] ) );

		return new ReferenceSerializer( $snakListSerializerMock );
	}

	public function serializableProvider() {
		return [
			[
				new Reference(),
			],
			[
				new Reference( new SnakList( [
					new PropertyNoValueSnak( 42 ),
				] ) ),
			],
		];
	}

	public function nonSerializableProvider() {
		return [
			[
				5,
			],
			[
				[],
			],
			[
				new SnakList( [] ),
			],
		];
	}

	public function serializationProvider() {
		return [
			[
				[
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => [],
					'snaks-order' => [],
				],
				new Reference(),
			],
		];
	}

	public function testSnaksOrderSerialization() {
		$snakListSerializerMock = $this->createMock( Serializer::class );
		$snakListSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SnakList( [
				new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) ),
				new PropertySomeValueSnak( new NumericPropertyId( 'P24' ) ),
				new PropertyNoValueSnak( new NumericPropertyId( 'P24' ) ),
			] ) ) )
			->will( $this->returnValue( [] ) );

		$referenceSerializer = new ReferenceSerializer( $snakListSerializerMock );

		$reference = new Reference( new SnakList( [
			new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) ),
			new PropertySomeValueSnak( new NumericPropertyId( 'P24' ) ),
			new PropertyNoValueSnak( new NumericPropertyId( 'P24' ) ),
		] ) );

		$this->assertEquals(
			[
				'hash' => $reference->getHash(),
				'snaks' => [],
				'snaks-order' => [
					'P42',
					'P24',
				],
			],
			$referenceSerializer->serialize( $reference )
		);
	}

}
