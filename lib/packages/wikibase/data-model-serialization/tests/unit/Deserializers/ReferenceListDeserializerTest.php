<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\ReferenceListDeserializer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;

/**
 * @covers Wikibase\DataModel\Deserializers\ReferenceListDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceListDeserializerTest extends PHPUnit_Framework_TestCase {

	private function buildDeserializer() {
		$referenceDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );

		$referenceDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => array()
			) ) )
			->will( $this->returnValue( new Reference() ) );

		return new ReferenceListDeserializer( $referenceDeserializerMock );
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
		);
	}

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$this->assertReferenceListEquals(
			$object,
			$this->buildDeserializer()->deserialize( $serialization )
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new ReferenceList(),
				array()
			),
			array(
				new ReferenceList( array(
					new Reference()
				) ),
				array(
					array(
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => array()
					)
				)
			),
		);
	}

	/**
	 * @param ReferenceList $expected
	 * @param ReferenceList $actual
	 */
	public function assertReferenceListEquals( ReferenceList $expected, ReferenceList $actual ) {
		$this->assertTrue( $actual->equals( $expected ), 'The two ReferenceList are different' );
	}

}
