<?php

namespace Tests\Wikibase\DataModel;

use DataValues\BooleanValue;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use DataValues\UnknownValue;
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
 * @todo Add test for qualifiers.
 * @todo Add test for references.
 * @todo Add test for ranks.
 * @todo Add tests with $options->setIndexTags( true ).
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DataModelSerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	private $guidCounter = 0;

	/**
	 * @dataProvider entityProvider
	 */
	public function testLegacySerializerRoundtrip( Entity $expectedEntity ) {
		$legacySerializer = $this->getLegacySerializer( $expectedEntity );
		$deserializer = $this->getDeserializer();

		// Old encoder -> new decoder
		$serialization = $legacySerializer->getSerialized( $expectedEntity );
		$actualEntity = $deserializer->deserialize( $serialization );

		$this->assertSymmetric( $expectedEntity, $actualEntity );
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testLegacyUnserializerRoundtrip( Entity $expectedEntity ) {
		$legacyUnserializer = $this->getLegacyUnserializer( $expectedEntity );
		$serializer = $this->getSerializer();

		// New encoder -> old decoder
		$serialization = $serializer->serialize( $expectedEntity );
		$actualEntity = $legacyUnserializer->newFromSerialization( $serialization );

		$this->assertSymmetric( $expectedEntity, $actualEntity );
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testFullLegacySerializerLegacyUnserializerRoundtrip( Entity $expectedEntity ) {
		$legacySerializer = $this->getLegacySerializer( $expectedEntity );
		$legacyUnserializer = $this->getLegacyUnserializer( $expectedEntity );
		$serializer = $this->getSerializer();
		$deserializer = $this->getDeserializer();

		// Old encoder -> new decoder -> new encoder -> old decoder
		$serialization = $legacySerializer->getSerialized( $expectedEntity );
		$entity = $deserializer->deserialize( $serialization );
		$serialization = $serializer->serialize( $entity );
		$actualEntity = $legacyUnserializer->newFromSerialization( $serialization );

		$this->assertSymmetric( $expectedEntity, $actualEntity );
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testFullLegacyUnserializerLegacySerializerRoundtrip( Entity $expectedEntity ) {
		$legacySerializer = $this->getLegacySerializer( $expectedEntity );
		$legacyUnserializer = $this->getLegacyUnserializer( $expectedEntity );
		$serializer = $this->getSerializer();
		$deserializer = $this->getDeserializer();

		// New encoder -> old decoder -> old encoder -> new decoder
		$serialization = $serializer->serialize( $expectedEntity );
		$entity = $legacyUnserializer->newFromSerialization( $serialization );
		$serialization = $legacySerializer->getSerialized( $entity );
		$actualEntity = $deserializer->deserialize( $serialization );

		$this->assertSymmetric( $expectedEntity, $actualEntity );
	}

	public function entityProvider() {
		$tests = array();

		foreach ( $this->getEntities() as $entity ) {
			$tests[] = array( $entity );
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
		$this->addFingerprint( $item );
		$entities[] = $item;

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q3' ) );
		$this->addSiteLinks( $item );
		$entities[] = $item;

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q4' ) );
		$this->addClaims( $item );
		$entities[] = $item;

		return $entities;
	}

	private function addFingerprint( Entity $entity ) {
		$entity->setLabel( 'de', 'de-label' );
		$entity->setLabel( 'en', 'en-label' );

		$entity->setDescription( 'de', 'de-description' );
		$entity->setDescription( 'en', 'en-description' );

		$entity->addAliases( 'de', array( 'de-alias1', 'de-alias2' ) );
		$entity->addAliases( 'en', array( 'en-alias1', 'en-alias2' ) );
	}

	private function addSiteLinks( Item $item ) {
		$badges = array(
			new ItemId( 'Q301' ),
			new ItemId( 'Q302' ),
		);

		$item->addSiteLink( new SiteLink( 'dewiki', 'dewiki-pagename' ) );
		$item->addSiteLink( new SiteLink( 'enwiki', 'enwiki-pagename', $badges ) );
	}

	private function addClaims( Item $item ) {
		$this->addClaim( $item, new PropertyNoValueSnak(
			new PropertyId( 'P401' )
		) );

		$this->addClaim( $item, new PropertySomeValueSnak(
			new PropertyId( 'P402' )
		) );

		$this->addClaim( $item, new PropertyValueSnak(
			new PropertyId( 'P403' ),
			new BooleanValue( true )
		) );
		$this->addClaim( $item, new PropertyValueSnak(
			new PropertyId( 'P404' ),
			new StringValue( 'stringvalue' )
		) );
		$this->addClaim( $item, new PropertyValueSnak(
			new PropertyId( 'P405' ),
			new UnDeserializableValue( 'undeserializable-data', 'string', 'undeserializable-error' )
		) );
		$this->addClaim( $item, new PropertyValueSnak(
			new PropertyId( 'P406' ),
			new UnknownValue( 'unknown-value' )
		) );
	}

	private function addClaim( Item $item, Snak $mainSnak ) {
		$claim = new Claim( $mainSnak );

		$claim->setGuid( 'DataModelSerializationRoundtripTest$' . $this->guidCounter );
		$this->guidCounter++;

		$item->addClaim( $claim );
	}

	private function getLegacySerializer( Entity $entity ) {
		$options = new SerializationOptions();

		$legacySerializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$entityType = $entity->getType();
		return $legacySerializerFactory->newSerializerForEntity( $entityType, $options );
	}

	private function getLegacyUnserializer( Entity $entity ) {
		$options = new SerializationOptions();

		$legacySerializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$entityType = $entity->getType();
		return $legacySerializerFactory->newUnserializerForEntity( $entityType, $options );
	}

	private function getSerializer() {
		$dataValueSerializer = new DataValueSerializer();
		$serializerFactory = new \Wikibase\DataModel\SerializerFactory( $dataValueSerializer );
		return $serializerFactory->newEntitySerializer();
	}

	private function getDeserializer() {
		$dataValueDeserializer = new DataValueDeserializer( array(
			'string' => 'DataValues\StringValue',
		) );
		$entityIdParser = new BasicEntityIdParser();
		$deserializerFactory = new \Wikibase\DataModel\DeserializerFactory( $dataValueDeserializer, $entityIdParser );
		return $deserializerFactory->newEntityDeserializer();
	}

	private function assertSymmetric( Entity $a, Entity $b ) {
		// Comparing both directions looks awkward but is crucial to makes sure it's symmetric.
		$this->assertTrue( $a->equals( $b ) );
		$this->assertTrue( $b->equals( $a ) );
	}

}
