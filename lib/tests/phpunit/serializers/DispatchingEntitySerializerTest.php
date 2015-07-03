<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\DispatchingEntitySerializer;
use Wikibase\Lib\Serializers\LibSerializerFactory;

/**
 * @covers Wikibase\Lib\Serializers\DispatchingEntitySerializer
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DispatchingEntitySerializerTest extends EntitySerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getInstance
	 *
	 * @return DispatchingEntitySerializer
	 */
	protected function getInstance() {
		return new DispatchingEntitySerializer( new LibSerializerFactory() );
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @return array
	 */
	public function validProvider() {
		return array(
			array( $this->getItemInstance() ),
			array( $this->getPropertyInstance() ),
		);
	}

	/**
	 * @return Entity
	 */
	protected function getEntityInstance() {
		return $this->getInstance();
	}

	/**
	 * @return Entity
	 */
	protected function getItemInstance() {
		$item = new Item( new ItemId( 'Q17' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'test', 'Foo' );

		return $item;
	}

	/**
	 * @return Entity
	 */
	protected function getPropertyInstance() {
		$property = Property::newFromType( 'wibbly' );
		$property->setId( new PropertyId( 'P17' ) );

		return $property;
	}

}
