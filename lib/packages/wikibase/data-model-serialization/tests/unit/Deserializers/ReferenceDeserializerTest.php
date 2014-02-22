<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\ReferenceDeserializer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Deserializers\ReferenceDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$snaksDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array() ) )
			->will( $this->returnValue( new SnakList() ) );

		return new ReferenceDeserializer( $snaksDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array()
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
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new Reference(),
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array()
				)
			),
			array(
				new Reference(),
				array(
					'snaks' => array()
				)
			),
		);
	}
}