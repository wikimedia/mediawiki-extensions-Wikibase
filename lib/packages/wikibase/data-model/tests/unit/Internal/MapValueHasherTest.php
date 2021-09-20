<?php

namespace Wikibase\DataModel\Tests\Internal;

use ArrayObject;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Internal\MapValueHasher;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers \Wikibase\DataModel\Internal\MapValueHasher
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapValueHasherTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		new MapValueHasher( true );
		$this->assertTrue( true );
	}

	public function testHash() {
		$hasher = new MapValueHasher();

		$map0 = [
			'foo' => new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) ),
			'bar' => new PropertyNoValueSnak( new NumericPropertyId( 'P2' ) ),
			42 => new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) ),
			new PropertyNoValueSnak( new NumericPropertyId( 'P9001' ) ),
		];

		$hash = $hasher->hash( $map0 );

		$map1 = $map0;
		unset( $map1['foo'] );
		$map1[] = $map0['foo'];

		$this->assertSame( $hash, $hasher->hash( $map1 ) );

		$map4 = new ArrayObject( $map0 );
		$this->assertSame( $hash, $hasher->hash( $map4 ) );

		$map2 = $map0;
		unset( $map2['foo'] );

		$this->assertNotEquals( $hash, $hasher->hash( $map2 ) );

		$map3 = $map0;
		$map3['foo'] = new PropertyNoValueSnak( new NumericPropertyId( 'P5' ) );

		$this->assertNotEquals( $hash, $hasher->hash( $map3 ) );
	}

	public function testHashThrowsExceptionOnInvalidArgument() {
		$hasher = new MapValueHasher();

		$this->expectException( InvalidArgumentException::class );
		$hasher->hash( null );
	}

}
