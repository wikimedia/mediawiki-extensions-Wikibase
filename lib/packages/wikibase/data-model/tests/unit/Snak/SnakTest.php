<?php

namespace Wikibase\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakObject;

/**
 * Unit tests for classes that implement Wikibase\Snak.
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

		$id42 = new PropertyId( 'p42' );

		$snaks[] = new PropertyNoValueSnak( $id42 );

		$snaks[] = new PropertySomeValueSnak( $id42 );

		$values = array();

		$values[] = new StringValue( 'Ohi there!' );
		$values[] = new NumberValue( 42 );

		foreach ( $values as $value ) {
			$snaks[] = new PropertyValueSnak( $id42, $value );
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
		$id42 = new PropertyId( 'p42' );

		$snak = new PropertyNoValueSnak( $id42 );

		$this->assertFalse( $snak->equals( new PropertySomeValueSnak( $id42 ) ) );

		$this->assertFalse( $snak->equals( new PropertyValueSnak(
			$id42,
			new StringValue( 'Ohi there!' )
		) ) );

		$id43 = new PropertyId( 'p43' );

		$this->assertFalse( $snak->equals( new PropertyNoValueSnak( $id43 ) ) );
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
			$copy = SnakObject::newFromArray( $data );

			$this->assertInstanceOf( '\Wikibase\Snak', $copy, 'newFromArray should return object implementing Snak' );
			$this->assertEquals( $snak->getHash(), $copy->getHash(), 'newFromArray should return object with same Hash used previously' );

			$this->assertTrue( $snak->equals( $copy ), 'getArray newFromArray roundtrip should work' );
		}
	}

}
