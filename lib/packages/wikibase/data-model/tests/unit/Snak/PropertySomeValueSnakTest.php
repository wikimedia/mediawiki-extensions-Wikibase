<?php

namespace Wikibase\DataModel\Tests\Snak;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers Wikibase\DataModel\Snak\PropertySomeValueSnak
 * @covers Wikibase\DataModel\Snak\SnakObject
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class PropertySomeValueSnakTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider validConstructorArgumentsProvider
	 */
	public function testConstructor( $propertyId ) {
		$snak = new PropertySomeValueSnak( $propertyId );
		$this->assertInstanceOf( 'Wikibase\DataModel\Snak\PropertySomeValueSnak', $snak );
	}

	public function validConstructorArgumentsProvider() {
		return array(
			array( 1 ),
			array( new PropertyId( 'P1' ) ),
			array( new PropertyId( 'P9001' ) ),
		);
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException( $propertyId ) {
		new PropertySomeValueSnak( $propertyId );
	}

	public function invalidConstructorArgumentsProvider() {
		return array(
			array( null ),
			array( 0.1 ),
			array( 'Q1' ),
			array( new ItemId( 'Q1' ) ),
		);
	}

	public function testGetPropertyId() {
		$snak = new PropertySomeValueSnak( new PropertyId( 'P1' ) );
		$propertyId = $snak->getPropertyId();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\PropertyId', $propertyId );
	}

	public function testGetHash() {
		$snak = new PropertySomeValueSnak( new PropertyId( 'P1' ) );
		$hash = $snak->getHash();
		$this->assertInternalType( 'string', $hash );
		$this->assertEquals( 40, strlen( $hash ) );
	}

	/**
	 * This test is a safeguard to make sure hashes are not changed unintentionally.
	 */
	public function testHashStability() {
		$snak = new PropertySomeValueSnak( new PropertyId( 'P1' ) );
		$hash = $snak->getHash();

		$expected = sha1( 'C:45:"Wikibase\DataModel\Snak\PropertySomeValueSnak":4:{i:1;}' );
		$this->assertSame( $expected, $hash );
	}

	public function testEquals() {
		$snak1 = new PropertySomeValueSnak( new PropertyId( 'P1' ) );
		$snak2 = new PropertySomeValueSnak( new PropertyId( 'P1' ) );
		$this->assertTrue( $snak1->equals( $snak2 ) );
		$this->assertTrue( $snak2->equals( $snak1 ) );
	}

	/**
	 * @dataProvider notEqualsProvider
	 */
	public function testGivenDifferentSnaks_EqualsReturnsFalse( Snak $snak1, Snak $snak2 ) {
		$this->assertFalse( $snak1->equals( $snak2 ) );
		$this->assertFalse( $snak2->equals( $snak1 ) );
	}

	public function notEqualsProvider() {
		$p1 = new PropertyId( 'P1' );

		return array(
			array(
				new PropertySomeValueSnak( $p1 ),
				new PropertySomeValueSnak( new PropertyId( 'P2' ) )
			),
			array(
				new PropertySomeValueSnak( $p1 ),
				new PropertyNoValueSnak( $p1 )
			),
		);
	}

	public function testSerialize() {
		$snak = new PropertySomeValueSnak( new PropertyId( 'P1' ) );
		$this->assertSame( 'i:1;', $snak->serialize() );
	}

	public function testUnserialize() {
		$snak = new PropertySomeValueSnak( new PropertyId( 'P1' ) );
		$snak->unserialize( 'i:2;' );
		$expected = new PropertySomeValueSnak( new PropertyId( 'P2' ) );
		$this->assertTrue( $snak->equals( $expected ) );
	}

}
