<?php

namespace Wikibase\Test;

use Wikibase\Entity;
use Wikibase\EntityFactory;
use Wikibase\Item;
use Wikibase\Property;

/**
 * @covers Wikibase\EntityFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityFactoryTest extends EntityTestCase {

	public function testGetEntityTypes() {
		$types = EntityFactory::singleton()->getEntityTypes();
		$this->assertInternalType( 'array', $types );

		$this->assertTrue( in_array( Item::ENTITY_TYPE, $types ), "must contain item type" );
		$this->assertTrue( in_array( Property::ENTITY_TYPE, $types ), "must contain property type" );

		// TODO
		// $this->assertTrue( in_array( Query::ENTITY_TYPE, $types ), "must contain query type" );
	}

	public static function provideIsEntityType() {
		$types = EntityFactory::singleton()->getEntityTypes();

		$tests = array();

		foreach ( $types as $type ) {
			$tests[] = array ( $type, true );
		}

		$tests[] = array ( 'this-does-not-exist', false );

		return $tests;
	}

	/**
	 * @dataProvider provideIsEntityType
	 */
	public function testIsEntityType( $type, $expected ) {
		$this->assertEquals( $expected, EntityFactory::singleton()->isEntityType( $type ) );
	}

	public static function provideNewEmpty() {
		return array(
			array( Item::ENTITY_TYPE, '\Wikibase\Item' ),
			array( Property::ENTITY_TYPE, '\Wikibase\Property' ),
		);
	}

	/**
	 * @dataProvider provideNewEmpty
	 */
	public function testNewEmpty( $type, $class ) {
		$entity = EntityFactory::singleton()->newEmpty( $type );

		$this->assertInstanceOf( $class, $entity );
		$this->assertTrue( $entity->isEmpty(), "should be empty" );
	}

	public static function provideNewFromArray() {
		return array(
			array( // #0
				Item::ENTITY_TYPE,
				array(
					'label' => array(
						'en' => 'Foo',
						'de' => 'FOO',
					)
				),
				'\Wikibase\Item' ),
		);
	}

	public static function provideNewFromBlob() {
		$tests = array();

		foreach ( self::provideNewFromArray() as $arrayTest ) {
			$tests[] = array(
				$arrayTest[0],
				json_encode( $arrayTest[1] ),
				null,
				$arrayTest[1],
				$arrayTest[2],
			);
		}

		return $tests;
	}

}
