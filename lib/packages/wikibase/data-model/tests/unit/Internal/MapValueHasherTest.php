<?php

namespace Wikibase\DataModel\Tests\Internal;

use ArrayObject;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Internal\MapValueHasher;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Internal\MapValueHasher
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapValueHasherTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		new MapValueHasher( true );
		$this->assertTrue( true );
	}

	public function testHash() {
		$hasher = new MapValueHasher();

		$map0 = array(
			'foo' => new PropertyNoValueSnak( new PropertyId( 'P1' ) ),
			'bar' => new PropertyNoValueSnak( new PropertyId( 'P2' ) ),
			42 => new PropertyNoValueSnak( new PropertyId( 'P42' ) ),
			new PropertyNoValueSnak( new PropertyId( 'P9001' ) ),
		);

		$hash = $hasher->hash( $map0 );

		$map1 = $map0;
		unset( $map1['foo'] );
		$map1[] = $map0['foo'];

		$this->assertEquals( $hash, $hasher->hash( $map1 ) );

		$map4 = new ArrayObject( $map0 );
		$this->assertEquals( $hash, $hasher->hash( $map4 ) );

		$map2 = $map0;
		unset( $map2['foo'] );

		$this->assertNotEquals( $hash, $hasher->hash( $map2 ) );

		$map3 = $map0;
		$map3['foo'] = new PropertyNoValueSnak( new PropertyId( 'P5' ) );

		$this->assertNotEquals( $hash, $hasher->hash( $map3 ) );
	}

	public function testHashThrowsExceptionOnInvalidArgument() {
		$hasher = new MapValueHasher();

		$this->setExpectedException( 'InvalidArgumentException' );
		$hasher->hash( null );
	}

}
