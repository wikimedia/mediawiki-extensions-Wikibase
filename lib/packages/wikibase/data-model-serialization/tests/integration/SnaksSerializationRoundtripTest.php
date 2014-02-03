<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\SnaksDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Serializers\SnaksSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Snak\Snaks;

/**
 * @covers Wikibase\DataModel\Serializers\SnaksSerializer
 * @covers Wikibase\DataModel\Deserializers\SnaksDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class SnaksSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider snaksProvider
	 */
	public function testSnakSerializationRoundtrips( Snaks $snak ) {
		$serializer = new SnaksSerializer( new SnakSerializer( new DataValueSerializer() ) );
		$deserializer = new SnaksDeserializer( new SnakDeserializer(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		) );

		$serialization = $serializer->serialize( $snak );
		$newSnaks = $deserializer->deserialize( $serialization );
		$this->assertEquals( $snak, $newSnaks );
	}

	public function snaksProvider() {
		return array(
			array(
				new SnakList( array() )
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 )
				) )
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 ),
					new PropertyNoValueSnak( 43 )
				) )
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( 42 ),
					new PropertySomeValueSnak( 42 ),
					new PropertyNoValueSnak( 43 ),
				) )
			),
		);
	}
}
