<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\Serializers\FingerprintSerializer;
use Wikibase\DataModel\SiteLink;
use stdClass;

/**
 * @covers Wikibase\DataModel\Serializers\ItemSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class ItemSerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$claimsSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimsSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnCallback( function( Claims $claims ) {
				if ( $claims->isEmpty() ) {
					return array();
				}

				return array(
					'P42' => array(
						array(
							'mainsnak' => array(
								'snaktype' => 'novalue',
								'property' => 'P42'
							),
							'type' => 'statement',
							'rank' => 'normal'
						)
					)
				);
			} ) );

		$siteLinkSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$siteLinkSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SiteLink( 'enwiki', 'Nyan Cat' ) ) )
			->will( $this->returnValue( array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => array()
			) ) );

		$fingerprintSerializer = new FingerprintSerializer( false );

		return new ItemSerializer( $fingerprintSerializer, $claimsSerializerMock, $siteLinkSerializerMock, false );
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
					'type' => 'item',
					'labels' => array(),
					'descriptions' => array(),
					'aliases' => array(),
					'sitelinks' => array(),
					'claims' => array(),
				),
				Item::newEmpty()
			),
		);

		$entity = Item::newEmpty();
		$entity->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			array(
				'type' => 'item',
				'claims' => array(
					'P42' => array(
						array(
							'mainsnak' => array(
								'snaktype' => 'novalue',
								'property' => 'P42'
							),
							'type' => 'statement',
							'rank' => 'normal'
						)
					)
				),
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
				'sitelinks' => array(),
			),
			$entity
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
				),
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
			),
			$item
		);

		return $provider;
	}

	public function testItemSerializerWithOptionObjectsForMaps() {
		$claimsSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimsSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new Claims() ) )
			->will( $this->returnValue( array() ) );

		$siteLinkSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$siteLinkSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new SiteLink( 'enwiki', 'Nyan Cat' ) ) )
			->will( $this->returnValue( array(
				'site' => 'enwiki',
				'title' => 'Nyan Cat',
				'badges' => array()
			) ) );

		$fingerprintSerializer = new FingerprintSerializer( false );

		$serializer = new ItemSerializer( $fingerprintSerializer, $claimsSerializerMock, $siteLinkSerializerMock, true );

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'Nyan Cat' ) );

		$sitelinks = new stdClass();
		$sitelinks->enwiki = array(
			'site' => 'enwiki',
			'title' => 'Nyan Cat',
			'badges' => array(),
		);
		$serial = array(
			'type' => 'item',
			'labels' => array(),
			'descriptions' => array(),
			'aliases' => array(),
			'claims' => array(),
			'sitelinks' => $sitelinks,
		);
		$this->assertEquals( $serial, $serializer->serialize( $item ) );
	}

}
