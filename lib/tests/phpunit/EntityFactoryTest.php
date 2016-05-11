<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityFactory;

/**
 * @covers Wikibase\EntityFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityFactoryTest extends \MediaWikiTestCase {

	private function getEntityFactory() {
		$instantiators = array(
			'item' => function() {
				return new Item();
			},
			'property' => function() {
				return Property::newFromType( 'string' );
			},
		);

		return new EntityFactory( $instantiators );
	}

	public function provideNewEmpty() {
		return array(
			array( 'item', Item::class ),
			array( 'property', Property::class ),
		);
	}

	/**
	 * @dataProvider provideNewEmpty
	 */
	public function testNewEmpty( $type, $class ) {
		$entity = $this->getEntityFactory()->newEmpty( $type );

		$this->assertInstanceOf( $class, $entity );
		$this->assertTrue( $entity->isEmpty(), 'should be empty' );
	}

}
