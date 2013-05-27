<?php

namespace Wikibase\Test;
use Wikibase\MapValueHasher;

/**
 * Tests for the Wikibase\MapValueHasher class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapValueHasherTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstructor() {
		new MapValueHasher( true );
		$this->assertTrue( true );
	}

	public function testHash() {
		$hasher = new MapValueHasher();

		$map0 = array(
			'foo' => new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 1 ) ),
			'bar' => new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 2 ) ),
			42 => new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 ) ),
			new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 9001 ) ),
		);

		$hash = $hasher->hash( $map0 );

		$map1 = $map0;
		unset( $map1['foo'] );
		$map1[] = $map0['foo'];

		$this->assertEquals( $hash, $hasher->hash( $map1 ) );

		$map4 = new \ArrayObject( $map0 );
		$this->assertEquals( $hash, $hasher->hash( $map4 ) );

		$map2 = $map0;
		unset( $map2['foo'] );

		$this->assertNotEquals( $hash, $hasher->hash( $map2 ) );

		$map3 = $map0;
		$map3['foo'] = new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 5 ) );

		$this->assertNotEquals( $hash, $hasher->hash( $map3 ) );
	}

}