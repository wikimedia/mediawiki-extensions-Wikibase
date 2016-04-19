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
		return new EntityFactory( array(
			'item' => Item::class,
			'property' => Property::class,
		) );
	}

	public function testGetEntityTypes() {
		$types = $this->getEntityFactory()->getEntityTypes();

		$this->assertInternalType( 'array', $types );
		$this->assertTrue( in_array( 'item', $types ), 'must contain item type' );
		$this->assertTrue( in_array( 'property', $types ), 'must contain property type' );
	}

	public function provideIsEntityType() {
		$tests = array();

		foreach ( $this->getEntityFactory()->getEntityTypes() as $type ) {
			$tests[] = array( $type, true );
		}

		$tests[] = array( 'this-does-not-exist', false );

		return $tests;
	}

	/**
	 * @dataProvider provideIsEntityType
	 * @param string $type
	 * @param bool $expected
	 */
	public function testIsEntityType( $type, $expected ) {
		$entityFactory = $this->getEntityFactory();

		$this->assertEquals( $expected, $entityFactory->isEntityType( $type ) );
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
