<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Serializers\FingerprintSerializer;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\SiteLink;
use stdClass;

/**
 * @covers Wikibase\DataModel\Serializers\EntitySerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class FingerprintSerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$claimsSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimsSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new Claims() ) )
			->will( $this->returnValue( array() ) );

		$siteLinkSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$siteLinkSerializerMock->expects( $this->any() )
			->method( 'serialize' );

		$entitySerializer = new FingerprintSerializer( false );

		return new ItemSerializer( $entitySerializer, $claimsSerializerMock, $siteLinkSerializerMock, false );
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
				new PropertyNoValueSnak( 42 )
			),
		);
	}

	public function serializationProvider() {
		$argumentLists = array();

		$argumentLists[] = array(
			array(
				'type' => 'item',
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			Item::newEmpty()
		);

		$entity = Item::newEmpty();
		$entity->setId( new ItemId( 'Q42' ) );
		$argumentLists[] = array(
			array(
				'type' => 'item',
				'id' => 'Q42',
				'labels' => array(),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = Item::newEmpty();
		$entity->setLabels( array(
			'en' => 'Nyan Cat',
			'fr' => 'Nyan Cat'
		) );
		$argumentLists[] = array(
			array(
				'type' => 'item',
				'labels' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'Nyan Cat'
					),
					'fr' => array(
						'language' => 'fr',
						'value' => 'Nyan Cat'
					)
				),
				'descriptions' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = Item::newEmpty();
		$entity->setDescriptions( array(
			'en' => 'A Nyan Cat',
			'fr' => 'A Nyan Cat'
		) );
		$argumentLists[] = array(
			array(
				'type' => 'item',
				'descriptions' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'A Nyan Cat'
					),
					'fr' => array(
						'language' => 'fr',
						'value' => 'A Nyan Cat'
					)
				),
				'labels' => array(),
				'aliases' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		$entity = Item::newEmpty();
		$entity->setAliases( 'en', array( 'Cat', 'My cat' ) );
		$entity->setAliases( 'fr', array( 'Cat' ) );
		$argumentLists[] = array(
			array(
				'type' => 'item',
				'aliases' => array(
					'en' => array(
						array(
							'language' => 'en',
							'value' => 'Cat'
						),
						array(
							'language' => 'en',
							'value' => 'My cat'
						)
					),
					'fr' => array(
						array(
							'language' => 'fr',
							'value' => 'Cat'
						)
					)
				),
				'labels' => array(),
				'descriptions' => array(),
				'claims' => array(),
				'sitelinks' => array(),
			),
			$entity
		);

		return $argumentLists;
	}

	public function testDescriptionWithOptionObjectsForMaps() {
		$entitySerializer = new FingerprintSerializer( true );

		$entity = Item::newEmpty();
		$entity->setDescriptions( array(
			'en' => 'A Nyan Cat',
		) );

		$result = array();

		$descriptions = new stdClass();
		$descriptions->en = array(
			'language' => 'en',
			'value' => 'A Nyan Cat'
		);
		$serial = array( 'descriptions' => $descriptions );
		$entitySerializer->addDescriptionsToSerialization( $entity, $result );
		$this->assertEquals( $serial, $result );
	}

	public function testAliasesWithOptionObjectsForMaps() {
		$entitySerializer = new FingerprintSerializer( true );

		$entity = Item::newEmpty();
		$entity->setAliases( 'fr', array( 'Cat' ) );

		$result = array();

		$aliases = new stdClass();
		$aliases->fr = array( array(
			'language' => 'fr',
			'value' => 'Cat'
		) );
		$serial = array( 'aliases' => $aliases );
		$entitySerializer->addAliasesToSerialization( $entity, $result );
		$this->assertEquals( $serial, $result );
	}

}
