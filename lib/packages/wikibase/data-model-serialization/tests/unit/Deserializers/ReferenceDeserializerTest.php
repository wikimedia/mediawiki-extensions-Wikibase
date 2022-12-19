<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Deserializers\ReferenceDeserializer;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Deserializers\ReferenceDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class ReferenceDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		$snaksDeserializerMock = $this->createMock( Deserializer::class );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [] ) )
			->will( $this->returnValue( new SnakList() ) );

		return new ReferenceDeserializer( $snaksDeserializerMock );
	}

	public function deserializableProvider() {
		return [
			[
				[
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => [],
				],
			],
		];
	}

	public function nonDeserializableProvider() {
		return [
			[
				42,
			],
			[
				[],
			],
		];
	}

	public function deserializationProvider() {
		return [
			[
				new Reference(),
				[
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => [],
				],
			],
			[
				new Reference(),
				[
					'snaks' => [],
				],
			],
		];
	}

	public function testSnaksOrderDeserialization() {
		$snaksDeserializerMock = $this->createMock( Deserializer::class );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
					'P24' => [
						[
							'snaktype' => 'novalue',
							'property' => 'P24',
						],
					],
					'P42' => [
						[
							'snaktype' => 'somevalue',
							'property' => 'P42',
						],
						[
							'snaktype' => 'novalue',
							'property' => 'P42',
						],
					],
				]
			) )
			->will( $this->returnValue( new SnakList( [
				new PropertyNoValueSnak( new NumericPropertyId( 'P24' ) ),
				new PropertySomeValueSnak( new NumericPropertyId( 'P42' ) ),
				new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) ),
			] ) ) );

		$referenceDeserializer = new ReferenceDeserializer( $snaksDeserializerMock );

		$reference = new Reference( new SnakList( [
			new PropertySomeValueSnak( new NumericPropertyId( 'P42' ) ),
			new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) ),
			new PropertyNoValueSnak( new NumericPropertyId( 'P24' ) ),
		] ) );

		$serialization = [
			'hash' => '20726a1e99eab73834c0f4a25f3c5c2561993e6e',
			'snaks' => [
				'P24' => [
					[
						'snaktype' => 'novalue',
						'property' => 'P24',
					],
				],
				'P42' => [
					[
						'snaktype' => 'somevalue',
						'property' => 'P42',
					],
					[
						'snaktype' => 'novalue',
						'property' => 'P42',
					],
				],
			],
			'snaks-order' => [
				'P42',
				'P24',
			],
		];

		$this->assertTrue( $reference->equals( $referenceDeserializer->deserialize( $serialization ) ) );
	}

	/**
	 * @dataProvider invalidDeserializationProvider
	 */
	public function testInvalidSerialization( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function invalidDeserializationProvider() {
		return [
			[
				'hash' => 'da',
				'snaks' => [],
			],
		];
	}

	public function testGivenInvalidSnaksOrderAttribute_exceptionIsThrown() {
		$this->expectException( InvalidAttributeException::class );
		$this->buildDeserializer()->deserialize( [
			'hash' => 'foo',
			'snaks' => [],
			'snaks-order' => null,
		] );
	}

}
