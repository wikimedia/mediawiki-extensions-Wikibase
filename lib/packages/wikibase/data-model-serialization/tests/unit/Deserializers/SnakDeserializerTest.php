<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Deserializers\SnakDeserializer
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class SnakDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'P42' ) )
			->will( $this->returnValue( new PropertyId( 'P42' ) ) );

		return new SnakDeserializer(
			new DataValueDeserializer( array (
				'string' => 'DataValues\StringValue',
			) ),
			$entityIdDeserializerMock
		);
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				)
			),
			array(
				array(
					'snaktype' => 'somevalue',
					'property' => 'P42'
				)
			),
			array(
				array(
					'snaktype' => 'value',
					'property' => 'P42',
					'datavalue' => array(
						'type' => 'string',
						'value' => 'hax'
					)
				)
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				42
			),
			array(
				array()
			),
			array(
				array(
					'id' => 'P10'
				)
			),
			array(
				array(
					'snaktype' => '42value'
				)
			),
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new PropertyNoValueSnak( 42 ),
				array(
					'snaktype' => 'novalue',
					'property' => 'P42',
					'hash' => '5c33520fbfb522444868b4168a35d4b919370018'
				)
			),
			array(
				new PropertySomeValueSnak( 42 ),
				array(
					'snaktype' => 'somevalue',
					'property' => 'P42'
				)
			),
			array(
				new PropertyValueSnak( 42, new StringValue( 'hax' ) ),
				array(
					'snaktype' => 'value',
					'property' => 'P42',
					'datavalue' => array(
						'type' => 'string',
						'value' => 'hax'
					)
				)
			),
			array(
				new PropertyNoValueSnak( 42 ),
				array(
					'snaktype' => 'novalue',
					'property' => 'P42',
					'hash' => 'not a valid hash'
				)
			),
		);
	}

	/**
	 * @dataProvider invalidDeserializationProvider
	 */
	public function testInvalidSerialization( $serialization ) {
		$this->setExpectedException( '\Deserializers\Exceptions\DeserializationException' );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function invalidDeserializationProvider() {
		return array(
			array(
				array(
					'snaktype' => 'somevalue'
				)
			),
			array(
				array(
					'snaktype' => 'value',
					'property' => 'P42'
				)
			),
		);
	}

	public function testDeserializePropertyIdFilterItemId() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'Q42' ) )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );
		$deserializer = new SnakDeserializer( new DataValueDeserializer(), $entityIdDeserializerMock );

		$this->setExpectedException( '\Deserializers\Exceptions\InvalidAttributeException' );
		$deserializer->deserialize( array(
			'snaktype' => 'somevalue',
			'property' => 'Q42'
		) );
	}

	public function testGivenInvalidDataValue_unDeserializableValueIsConstructed() {
		$serialization = array(
			'snaktype' => 'value',
			'property' => 'P42',
			'datavalue' => array(
				'type' => 'string',
				'value' => 1337
			)
		);

		$snak = $this->buildDeserializer()->deserialize( $serialization );

		$this->assertInstanceOf( 'Wikibase\DataModel\Snak\PropertyValueSnak', $snak );
		$this->assertSnakHasUnDeserializableValue( $snak );
	}

	public function testGivenInvalidDataValue_unDeserializableValueWithErrorText() {
		$serialization = array(
			'snaktype' => 'value',
			'property' => 'P42',
			'datavalue' => array(
				'type' => 'string',
				'value' => 1337,
				'error' => 'omg, an error!'
			)
		);

		$snak = $this->buildDeserializer()->deserialize( $serialization );

		$expectedValue = new UnDeserializableValue( 1337, 'string', 'omg, an error!' );

		$this->assertTrue( $snak->getDataValue()->equals( $expectedValue ) );
	}

	private function assertSnakHasUnDeserializableValue( PropertyValueSnak $snak ) {
		$this->assertEquals( new PropertyId( 'P42' ), $snak->getPropertyId() );

		$dataValue = $snak->getDataValue();

		/**
		 * @var UnDeserializableValue $dataValue
		 */
		$this->assertInstanceOf( 'DataValues\UnDeserializableValue', $dataValue );

		$this->assertEquals( $dataValue->getTargetType(), 'string' );
		$this->assertEquals( $dataValue->getValue(), 1337 );
	}

}
