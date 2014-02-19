<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Serializers\ItemSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ItemSerializerTest extends SerializerBaseTest {

	public function buildSerializer() {
		$claimsSerializerMock = $this->getMock( '\Serializers\Serializer' );

		$siteLinkSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$siteLinkSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SiteLink( 'enwiki', 'Nyan Cat' ) ) )
			->will( $this->returnValue( array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => array()
			) ) );

		return new ItemSerializer( $claimsSerializerMock, $siteLinkSerializerMock );
	}

	public function serializableProvider() {
		return array(
			array(
				Item::newEmpty()
			),
		);
	}

	public function nonSerializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				Property::newEmpty()
			),
		);
	}

	public function serializationProvider() {
		$provider = array(
			array(
				array(
					'type' => 'item'
				),
				Item::newEmpty()
			),
		);

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'Nyan Cat' ) );
		$provider[] = array(
			array(
				'type' => 'item',
				'sitelinks' => array(
					'enwiki' => array(
						'site' => 'enwiki',
						'title' => 'Nyan Cat',
						'badges' => array()
					)
				)
			),
			$item
		);

		return $provider;
	}
}
