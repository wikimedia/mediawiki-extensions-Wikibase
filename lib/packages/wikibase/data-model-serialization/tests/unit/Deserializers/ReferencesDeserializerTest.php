<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\ReferencesDeserializer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;

/**
 * @covers Wikibase\DataModel\Deserializers\ReferencesDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferencesDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$referenceDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$referenceDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => array()
			) ) )
			->will( $this->returnValue( new Reference() ) );

		$referenceDeserializerMock->expects( $this->any() )
			->method( 'isDeserializerFor' )
			->with( $this->equalTo( array(
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => array()
			) ) )
			->will( $this->returnValue( true ) );

		return new ReferencesDeserializer( $referenceDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array()
			),
			array(
				array(
					array(
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => array()
					)
				)
			),
			array(
				array(
					array(
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => array()
					),
					array(
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => array()
					),
				)
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				42
			),
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
}
