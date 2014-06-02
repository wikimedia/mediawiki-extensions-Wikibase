<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\Lib\Serializers\ItemSerializer;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Property;

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
	 * @see SerializerBaseTest::getClass
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\DispatchingEntitySerializer';
	}

	/**
	 * @return ItemSerializer
	 */
	protected function getInstance() {
		$factory = new SerializerFactory();

		$class = $this->getClass();
		return new $class( $factory );
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
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q17' ) );
		$item->addSiteLink( new SimpleSiteLink( 'test', 'Foo' ) );

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
