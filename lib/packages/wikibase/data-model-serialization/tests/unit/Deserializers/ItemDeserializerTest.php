<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\ItemDeserializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Deserializers\ItemDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$claimsDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );

		$siteLinkDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );

		$siteLinkDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => array()
			) ) )
			->will( $this->returnValue( new SiteLink( 'enwiki', 'Nyan Cat' ) ) );

		return new ItemDeserializer( $entityIdDeserializerMock, $claimsDeserializerMock, $siteLinkDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'type' => 'item'
				)
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				array(
					'type' => 'property'
				)
			),
		);
	}

	public function deserializationProvider() {
		$provider = array(
			array(
				new Item(),
				array(
					'type' => 'item'
				)
			),
		);

		$item = new Item();
		$item->addSiteLink( new SiteLink( 'enwiki', 'Nyan Cat' ) );
		$provider[] = array(
			$item,
			array(
				'type' => 'item',
				'sitelinks' => array(
					'enwiki' => array(
						'site' => 'enwiki',
						'title' => 'Nyan Cat',
						'badges' => array()
					)
				)
			)
		);

		return $provider;
	}

}
