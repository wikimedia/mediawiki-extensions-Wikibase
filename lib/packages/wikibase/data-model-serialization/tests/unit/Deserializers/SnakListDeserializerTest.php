<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\SnakListDeserializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Deserializers\SnakListDeserializer
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class SnakListDeserializerTest extends PHPUnit_Framework_TestCase {

	private function buildDeserializer() {
		$snakDeserializerMock = $this->getMock( 'Deserializers\Deserializer' );

		$snakDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
					'snaktype' => 'novalue',
					'property' => 'P42'
			) ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );

		return new SnakListDeserializer( $snakDeserializerMock );
	}

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = $this->buildDeserializer();
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$deserializer->deserialize( $nonDeserializable );
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				42
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

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$this->assertEquals( $object, $this->buildDeserializer()->deserialize( $serialization ) );
	}

	public function deserializationProvider() {
		return array(
			array(
				new SnakList(),
				array()
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 )
				) ),
				array(
					'P42' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				)
			),
		);
	}

}
