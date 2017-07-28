<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class EntitySerializationRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityProvider
	 */
	public function testEntitySerializationRoundtrips( EntityDocument $entity ) {
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
		$entities = [];

		$entity = new Item( new ItemId( 'Q42' ) );
		$entities[] = [ $entity ];

		$entity = new Item();
		$entity->setLabel( 'en', 'Nyan Cat' );
		$entity->setLabel( 'fr', 'Nyan Cat' );
		$entities[] = [ $entity ];

		$entity = new Item();
		$entity->setDescription( 'en', 'Nyan Cat' );
		$entity->setDescription( 'fr', 'Nyan Cat' );
		$entities[] = [ $entity ];

		$entity = new Item();
		$entity->setAliases( 'en', [ 'Cat', 'My cat' ] );
		$entity->setAliases( 'fr', [ 'Cat' ] );
		$entities[] = [ $entity ];

		$entity = new Item();
		$entity->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'guid' );
		$entities[] = [ $entity ];

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Nyan Cat' );
		$entities[] = [ $item ];

		$entities[] = [ Property::newFromType( 'string' ) ];

		return $entities;
	}

}
