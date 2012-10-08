<?php

namespace Wikibase\Test;
use Wikibase\MapHasher as MapHasher;
use Wikibase\MapValueHasher as MapValueHasher;

/**
 * Tests for the Wikibase\MapValueHasher class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapValueHasherTest extends \MediaWikiTestCase {

	public function constructorProvider() {
		return $this->arrayWrap( array( false, true ) );
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $arg0 ) {
		$hasher = new MapValueHasher( $arg0 );

		$this->assertInstanceOf( '\Wikibase\MapHasher', $hasher );
	}

	public function testHash() {
		$hasher = new MapValueHasher();

		$map0 = array(
			'foo' => new \Wikibase\PropertyNoValueSnak( 1 ),
			'bar' => new \Wikibase\PropertyNoValueSnak( 2 ),
			42 => new \Wikibase\PropertyNoValueSnak( 42 ),
			new \Wikibase\PropertyNoValueSnak( 9001 ),
		);

		$hash = $hasher->hash( $map0 );

		$map1 = $map0;
		unset( $map1['foo'] );
		$map1[] = $map0['foo'];

		$this->assertEquals( $hash, $hasher->hash( $map1 ) );

		$map2 = $map0;
		unset( $map2['foo'] );

		$this->assertNotEquals( $hash, $hasher->hash( $map2 ) );

		$map3 = $map0;
		$map3['foo'] = new \Wikibase\PropertyNoValueSnak( 5 );

		$this->assertNotEquals( $hash, $hasher->hash( $map3 ) );
	}

}