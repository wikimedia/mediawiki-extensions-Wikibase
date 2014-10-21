<?php

namespace Tests\Wikibase\DataModel;

use DataValues\BooleanValue;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use DataValues\UnknownValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory as LegacySerializerFactory;

/**
 * @todo Add tests with $options->setIndexTags( true ).
 *
 * @group Wikibase
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

	/**
	 * @dataProvider entityProvider
	 */
	public function testEmptyArraySerialization( Entity $entity ) {
		$serializer = $this->getSerializer();
		$serialization = $serializer->serialize( $entity );

		$this->assertArrayHasKey( 'labels', $serialization );
		$this->assertArrayHasKey( 'descriptions', $serialization );
		$this->assertArrayHasKey( 'aliases', $serialization );

		if ( $entity->getType() === 'item' ) {
			$this->assertArrayHasKey( 'sitelinks', $serialization );
		}
	}

	public function testQualifiersAndSnaksOrder() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q1' ) );
		$this->addStatementsWithQualifiersAndReferences( $item );

		$legacySerializer = $this->getLegacySerializer( $item );
		$legacySerialization = $legacySerializer->getSerialized( $item );
		$legacyQualifiersOrder = $legacySerialization['claims']['P601'][0]['qualifiers-order'];
		$legacySnaksOrder = $legacySerialization['claims']['P601'][0]['references'][0]['snaks-order'];

		$serializer = $this->getSerializer();
		$serialization = $serializer->serialize( $item );
		$qualifiersOrder = $serialization['claims']['P601'][0]['qualifiers-order'];
		$snaksOrder = $serialization['claims']['P601'][0]['references'][0]['snaks-order'];

		$this->assertOrderArrayEquals( $legacyQualifiersOrder, $qualifiersOrder );
		$this->assertOrderArrayEquals( $legacySnaksOrder, $snaksOrder );
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
		$this->addStatementsWithoutQualifiers( $item );
		$this->addStatementsWithQualifiers( $item );
		$this->addStatementsWithRanks( $item );
		$this->addStatementsWithQualifiersAndReferences( $item );
		$entities[] = $item;

		return $entities;
	}

	private function addFingerprint( Item $item ) {
		$item->setLabel( 'de', 'de-label' );
		$item->setLabel( 'en', 'en-label' );

		$item->setDescription( 'de', 'de-description' );
		$item->setDescription( 'en', 'en-description' );

		$item->addAliases( 'de', array( 'de-alias1', 'de-alias2' ) );
		$item->addAliases( 'en', array( 'en-alias1', 'en-alias2' ) );
	}

	private function addSiteLinks( Item $item ) {
		$badges = array(
			new ItemId( 'Q301' ),
			new ItemId( 'Q302' ),
		);

		$item->addSiteLink( new SiteLink( 'dewiki', 'dewiki-pagename' ) );
		$item->addSiteLink( new SiteLink( 'enwiki', 'enwiki-pagename', $badges ) );
	}

	private function getSnaks( $baseId = 'P' ) {
		return array(
			new PropertyNoValueSnak(
				new PropertyId( $baseId . '1' )
			),
			new PropertySomeValueSnak(
				new PropertyId( $baseId . '2' )
			),
			new PropertyValueSnak(
				new PropertyId( $baseId . '3' ),
				new BooleanValue( true )
			),
			new PropertyValueSnak(
				new PropertyId( $baseId . '4' ),
				new StringValue( 'string-value' )
			),
			new PropertyValueSnak(
				new PropertyId( $baseId . '5' ),
				new UnDeserializableValue( 'undeserializable-data', 'time', 'array expected' )
			),
			new PropertyValueSnak(
				new PropertyId( $baseId . '6' ),
				new UnknownValue( 'unknown-value' )
			),
		);
	}

	private function addStatementsWithoutQualifiers( Item $item ) {
		foreach ( $this->getSnaks( 'P40' ) as $mainSnak ) {
			$statement = new Statement( new Claim( $mainSnak ) );
			$this->setGuid( $statement );
			$item->getStatements()->addStatement( $statement );
		}
	}

	private function addStatementsWithQualifiers( Item $item ) {
		$mainSnak = new PropertyNoValueSnak(
			new PropertyId( 'P501' )
		);
		$qualifiers = new SnakList( $this->getSnaks( 'P51' ) );
		$statement = new Statement( new Claim( $mainSnak, $qualifiers ) );
		$this->setGuid( $statement );
		$item->getStatements()->addStatement( $statement );
	}

	private function addStatementsWithRanks( Item $item ) {
		$ranks = array(
			'1' => Statement::RANK_PREFERRED,
			'2' => Statement::RANK_NORMAL,
			'3' => Statement::RANK_DEPRECATED,
		);
		foreach ( $ranks as $id => $rank ) {
			$mainSnak = new PropertyNoValueSnak(
				new PropertyId( 'P70' . $id )
			);
			$statement = new Statement( new Claim( $mainSnak ) );
			$this->setGuid( $statement );
			$statement->setRank( $rank );
			$item->getStatements()->addStatement( $statement );
		}
	}

	private function addStatementsWithQualifiersAndReferences( Item $item ) {
		$mainSnak = new PropertyNoValueSnak(
			new PropertyId( 'P601' )
		);
		$qualifiers = new SnakList( $this->getSnaks( 'P61' ) );
		$reference = new Reference( new SnakList( $this->getSnaks( 'P62' ) ) );
		$references = new ReferenceList( array( $reference ) );
		$statement = new Statement( new Claim( $mainSnak, $qualifiers ), $references );
		$this->setGuid( $statement );
		$item->getStatements()->addStatement( $statement );
	}

	private function setGuid( Statement $statement ) {
		$statement->setGuid( 'DataModelSerializationRoundtripTest$' . $this->guidCounter );
		$this->guidCounter++;
	}

	private function getLegacySerializer( Entity $entity ) {
		$options = new SerializationOptions();

		$legacySerializerFactory = new LegacySerializerFactory();
		$entityType = $entity->getType();
		return $legacySerializerFactory->newSerializerForEntity( $entityType, $options );
	}

	private function getLegacyUnserializer( Entity $entity ) {
		$options = new SerializationOptions();

		$legacySerializerFactory = new LegacySerializerFactory();
		$entityType = $entity->getType();
		return $legacySerializerFactory->newUnserializerForEntity( $entityType, $options );
	}

	private function getSerializer() {
		$dataValueSerializer = new DataValueSerializer();
		$serializerFactory = new SerializerFactory( $dataValueSerializer );
		return $serializerFactory->newEntitySerializer();
	}

	private function getDeserializer() {
		$dataValueDeserializer = new DataValueDeserializer( array(
			'boolean' => 'DataValues\BooleanValue',
			'string' => 'DataValues\StringValue',
			'unknown' => 'DataValues\UnknownValue',
		) );
		$entityIdParser = new BasicEntityIdParser();
		$deserializerFactory = new DeserializerFactory( $dataValueDeserializer, $entityIdParser );
		return $deserializerFactory->newEntityDeserializer();
	}

	private function assertOrderArrayEquals( array $expected, array $actual ) {
		$this->assertNotEmpty( $actual );
		$this->assertContainsOnly( 'string', $actual );
		$this->assertEquals( $expected, $actual );
	}

	private function assertSymmetric( Entity $a, Entity $b ) {
		// Comparing both directions looks awkward but is crucial to makes sure it's symmetric.
		$this->assertTrue( $a->equals( $b ) );
		$this->assertTrue( $b->equals( $a ) );
	}

}
