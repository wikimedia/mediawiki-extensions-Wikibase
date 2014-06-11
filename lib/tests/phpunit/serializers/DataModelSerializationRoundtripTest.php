<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DataModelSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	private $guidCounter = 0;

	/**
	 * @dataProvider entityProvider
	 */
	public function testRoundtrip( Entity $expectedEntity, $indexTags = false ) {
		$options = new SerializationOptions();
		$options->setIndexTags( $indexTags );

		$legacySerializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$entityType = $expectedEntity->getType();
		$legacySerializer = $legacySerializerFactory->newSerializerForEntity( $entityType, $options );
		$legacyUnserializer = $legacySerializerFactory->newUnserializerForEntity( $entityType, $options );

		// XXX: What's the point of requiring this in the constructor?
		$dataValueSerializer = new DataValueSerializer();
		$serializerFactory = new \Wikibase\DataModel\SerializerFactory( $dataValueSerializer );
		$serializer = $serializerFactory->newEntitySerializer();

		// FIXME: How to set any options in the new serializers?

		// XXX: What's the point of requiring this in the constructor?
		$dataValueDeserializer = new DataValueDeserializer( array(
			'string' => 'DataValues\StringValue',
		) );
		// XXX: What's the point of requiring this in the constructor?
		$entityIdParser = new BasicEntityIdParser();
		$deserializerFactory = new \Wikibase\DataModel\DeserializerFactory( $dataValueDeserializer, $entityIdParser );
		$deserializer = $deserializerFactory->newEntityDeserializer();

		// Old encoder -> new decoder -> new encoder -> old decoder.
		$serialization = $legacySerializer->getSerialized( $expectedEntity );
		$entity = $deserializer->deserialize( $serialization );
		$serialization = $serializer->serialize( $entity );
		$actualEntity = $legacyUnserializer->newFromSerialization( $serialization );

		// XXX: It doesn't make much sense to compare in both directions but it can't hurt, right?
		$this->assertTrue( $actualEntity->equals( $expectedEntity ) );
		$this->assertTrue( $expectedEntity->equals( $actualEntity ) );

		// New encoder -> old decoder -> old encoder -> new decoder.
		$serialization = $serializer->serialize( $expectedEntity );
		$entity = $legacyUnserializer->newFromSerialization( $serialization );
		$serialization = $legacySerializer->getSerialized( $entity );
		$actualEntity = $deserializer->deserialize( $serialization );

		// XXX: It doesn't make much sense to compare in both directions but it can't hurt, right?
		$this->assertTrue( $actualEntity->equals( $expectedEntity ) );
		$this->assertTrue( $expectedEntity->equals( $actualEntity ) );
	}

	public function entityProvider() {
		$tests = array();

		// TODO: Add false, but then the test fails.
		foreach ( array( false ) as $indexTags ) {
			foreach ( $this->getEntities() as $entity ) {
				$tests[] = array( $entity, $indexTags );
			}
		}

		return $tests;
	}

	private function getEntities() {
		$entities = array();

		$property = Property::newFromType( 'string' );
		$property->setId( new PropertyId( 'P1' ) );
		$entities[] = $property;

		$property = Property::newFromType( 'INVALID' );
		$property->setId( new PropertyId( 'P999999999999' ) );
		$entities[] = $property;

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q1' ) );
		$entities[] = $item;

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q2' ) );
		$item->setLabel( 'de', 'de-label' );
		$item->setLabel( 'en', 'en-label' );
		$item->setDescription( 'de', 'de-description' );
		$item->setDescription( 'en', 'en-description' );
		$item->addAliases( 'de', array( 'de-alias1', 'de-alias2' ) );
		$item->addAliases( 'en', array( 'en-alias1', 'en-alias2' ) );
		$entities[] = $item;

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q3' ) );
		$item->addSiteLink( new SiteLink( 'dewiki', 'dewiki-pagename' ) );
		$badges = array(
			new ItemId( 'Q301' ),
			new ItemId( 'Q302' ),
		);
		$item->addSiteLink( new SiteLink( 'enwiki', 'enwiki-pagename', $badges ) );
		$entities[] = $item;

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q4' ) );
		$item->addClaim( $this->makeClaim( new PropertyNoValueSnak(
			new PropertyId( 'P401' )
		) ) );
		$item->addClaim( $this->makeClaim( new PropertySomeValueSnak(
			new PropertyId( 'P402' )
		) ) );
		$item->addClaim( $this->makeClaim( new PropertyValueSnak(
			new PropertyId( 'P402' ),
			new StringValue( 'stringvalue' )
		) ) );
		$entities[] = $item;

		return $entities;
	}

	protected function makeClaim( Snak $mainSnak, $guid = null ) {
		$claim = new Claim( $mainSnak );

		if ( $guid === null ) {
			$guid = 'DataModelSerializationRoundtripTest$' . $this->guidCounter;
			$this->guidCounter++;
		}
		$claim->setGuid( $guid );

		return $claim;
	}

}
