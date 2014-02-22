<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class EntitySerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityProvider
	 */
	public function testEntitySerializationRoundtrips( Entity $entity ) {
		$serializerFactory = new SerializerFactory( new DataValueSerializer() );
		$deserializerFactory = new DeserializerFactory(
			new DataValueDeserializer(),
			new BasicEntityIdParser()
		);

		$serialization = $serializerFactory->newEntitySerializer()->serialize( $entity );
		$newEntity = $deserializerFactory->newEntityDeserializer()->deserialize( $serialization );
		$this->assertTrue( $entity->equals( $newEntity ) );
	}

	public function entityProvider() {
		$entities = array();

		$entity = Item::newEmpty();
		$entity->setId( new ItemId( 'Q42' ) );
		$entities[] = array( $entity );

		$entity = Item::newEmpty();
		$entity->setLabels( array(
			'en' => 'Nyan Cat',
			'fr' => 'Nyan Cat'
		) );
		$entities[] = array( $entity );

		$entity = Item::newEmpty();
		$entity->setDescriptions( array(
			'en' => 'A Nyan Cat',
			'fr' => 'A Nyan Cat'
		) );
		$entities[] = array( $entity );

		$entity = Item::newEmpty();
		$entity->setAliases( 'en', array( 'Cat', 'My cat' ) );
		$entity->setAliases( 'fr', array( 'Cat' ) );
		$entities[] = array( $entity );

		$entity = Item::newEmpty();
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );
		$entity->setClaims( new Claims( array( $claim ) ) );
		$entities[] = array( $entity );

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'Nyan Cat' ) );
		$entities[] = array( $item );

		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		$entities[] = array( $property );

		return $entities;
	}
}
