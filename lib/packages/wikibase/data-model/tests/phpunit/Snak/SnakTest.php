<?php

namespace Wikibase\Test;

use Wikibase\Snak;

/**
 * Unit tests for classes that implement Wikibase\Snak.
 *
 * @file
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakTest extends \PHPUnit_Framework_TestCase {

	public function snakProvider() {
		$snaks = array();

		$id42 = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 );

		$snaks[] = new \Wikibase\PropertyNoValueSnak( $id42 );

		$snaks[] = new \Wikibase\PropertySomeValueSnak( $id42 );

		$values = array();

		$values[] = new \DataValues\StringValue( 'Ohi there!' );
		$values[] = new \DataValues\NumberValue( 42 );
		$values[] = new \DataValues\QuantityValue( 4.2, 'm', 1 );

		foreach ( $values as $value ) {
			$snaks[] = new \Wikibase\PropertyValueSnak( $id42, $value );
		}

		$argLists = array();

		foreach ( $snaks as $snak ) {
			$argLists[] = array( $snak );
		}

		return $argLists;
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetType( Snak $snak ) {
		$this->assertInternalType( 'string', $snak->getType() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetPropertyId( Snak $snak ) {
		$this->assertInstanceOf( '\Wikibase\EntityId', $snak->getPropertyId() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testSerialize( Snak $snak ) {
		$serialization = serialize( $snak );
		$this->assertInternalType( 'string', $serialization );

		$newInstance = unserialize( $serialization );
		$this->assertInstanceOf( get_class( $snak ), $newInstance );

		$this->assertEquals( $snak, $newInstance );
		$this->assertEquals( $snak->getPropertyId(), $newInstance->getPropertyId() );
		$this->assertEquals( $snak->getType(), $newInstance->getType() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testGetHash( Snak $snak ) {
		$hash = $snak->getHash();
		$this->assertInternalType( 'string', $hash );
		$this->assertEquals( $hash, $snak->getHash() );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testEquals( Snak $snak ) {
		$this->assertTrue( $snak->equals( $snak ) );
		$this->assertFalse( $snak->equals( '~=[,,_,,]:3' ) );
	}

	public function testEqualsMoar() {
		$id42 = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 );

		$snak = new \Wikibase\PropertyNoValueSnak( $id42 );

		$this->assertFalse( $snak->equals( new \Wikibase\PropertySomeValueSnak( $id42 ) ) );

		$this->assertFalse( $snak->equals( new \Wikibase\PropertyValueSnak(
			$id42,
			new \DataValues\StringValue( 'Ohi there!' )
		) ) );

		$id43 = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 43 );

		$this->assertFalse( $snak->equals( new \Wikibase\PropertyNoValueSnak( $id43 ) ) );
	}

	/**
	 * @dataProvider snakProvider
	 * @param Snak $snak
	 */
	public function testToArrayRoundtrip( Snak $snak ) {
		$serialization = serialize( $snak->toArray() );
		$array = $snak->toArray();

		$this->assertInternalType( 'array', $array, 'toArray should return array' );

		foreach ( array( $array, unserialize( $serialization ) ) as $data ) {
			$copy = \Wikibase\SnakObject::newFromArray( $data );

			$this->assertInstanceOf( '\Wikibase\Snak', $copy, 'newFromArray should return object implementing Snak' );
			$this->assertEquals( $snak->getHash(), $copy->getHash(), 'newFromArray should return object with same Hash used previously' );

			$this->assertTrue( $snak->equals( $copy ), 'getArray newFromArray roundtrip should work' );
		}
	}

}
