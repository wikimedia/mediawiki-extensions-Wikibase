<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\SiteLinkDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Deserializers\SiteLinkDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SiteLinkDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'Q42' ) )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		return new SiteLinkDeserializer( $entityIdDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'site' => 'test',
					'title' => 'Nyan Cat'
				)
			),
			array(
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array()
				)
			),
			array(
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array( 'Q42' )
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
					'site' => '42value'
				)
			),
			array(
				array(
					'title' => '42value'
				)
			),
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new SiteLink( 'enwiki', 'Nyan Cat' ),
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat'
				)
			),
			array(
				new SiteLink( 'enwiki', 'Nyan Cat', array(
					new ItemId( 'Q42' )
				) ),
				array(
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => array( 'Q42' )
				)
			),
		);
	}

	public function testDeserializeItemIdFilterPropertyId() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'P42' ) )
			->will( $this->returnValue( new PropertyId( 'P42' ) ) );
		$deserializer = new SiteLinkDeserializer( $entityIdDeserializerMock );

		$this->setExpectedException( '\Deserializers\Exceptions\InvalidAttributeException' );
		$deserializer->deserialize( array(
			'site' => 'frwikisource',
			'title' => 'Nyan Cat',
			'badges' => array( 'P42' )
		) );
	}

	public function testAssertBadgesIsArray() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$deserializer = new SiteLinkDeserializer( $entityIdDeserializerMock );

		$this->setExpectedException( '\Deserializers\Exceptions\InvalidAttributeException' );
		$deserializer->deserialize( array(
			'site' => 'frwikisource',
			'title' => 'Nyan Cat',
			'badges' => 'Q42'
		) );
	}

}
