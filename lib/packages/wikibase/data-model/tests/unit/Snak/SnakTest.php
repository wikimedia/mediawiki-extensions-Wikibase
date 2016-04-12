<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\NumberValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers Wikibase\DataModel\Snak\PropertyNoValueSnak
 * @covers Wikibase\DataModel\Snak\PropertySomeValueSnak
 * @covers Wikibase\DataModel\Snak\PropertyValueSnak
 * @covers Wikibase\DataModel\Snak\Snak
 * @uses Wikibase\DataModel\Snak\SnakObject
 * @uses DataValues\NumberValue
 * @uses DataValues\StringValue
 * @uses Wikibase\DataModel\Entity\EntityId
 * @uses Wikibase\DataModel\Entity\PropertyId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @license GPL-2.0+
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
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\EntityId', $snak->getPropertyId() );
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

}
