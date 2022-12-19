<?php

namespace Wikibase\DataModel\Tests\Snak;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers \Wikibase\DataModel\Snak\PropertyNoValueSnak
 * @covers \Wikibase\DataModel\Snak\SnakObject
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class PropertyNoValueSnakTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider validConstructorArgumentsProvider
	 */
	public function testConstructor( $propertyId ) {
		$snak = new PropertyNoValueSnak( $propertyId );
		$this->assertInstanceOf( PropertyNoValueSnak::class, $snak );
	}

	public function validConstructorArgumentsProvider() {
		return [
			[ 1 ],
			[ new NumericPropertyId( 'P1' ) ],
			[ new NumericPropertyId( 'P9001' ) ],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException( $propertyId ) {
		$this->expectException( InvalidArgumentException::class );
		new PropertyNoValueSnak( $propertyId );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			[ null ],
			[ 0.1 ],
			[ 'Q1' ],
			[ new ItemId( 'Q1' ) ],
		];
	}

	public function testGetPropertyId() {
		$snak = new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) );
		$propertyId = $snak->getPropertyId();
		$this->assertInstanceOf( NumericPropertyId::class, $propertyId );
	}

	public function testGetHash() {
		$snak = new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) );
		$hash = $snak->getHash();
		$this->assertIsString( $hash );
		$this->assertSame( 40, strlen( $hash ) );
	}

	/**
	 * This test is a safeguard to make sure hashes are not changed unintentionally.
	 */
	public function testHashStability() {
		$snak = new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) );
		$hash = $snak->getHash();

		$expected = sha1( 'C:43:"Wikibase\DataModel\Snak\PropertyNoValueSnak":2:{P1}' );
		$this->assertSame( $expected, $hash );
	}

	public function testEquals() {
		$snak1 = new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) );
		$snak2 = new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) );
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
		$p1 = new NumericPropertyId( 'P1' );

		return [
			[
				new PropertyNoValueSnak( $p1 ),
				new PropertyNoValueSnak( new NumericPropertyId( 'P2' ) ),
			],
			[
				new PropertyNoValueSnak( $p1 ),
				new PropertySomeValueSnak( $p1 ),
			],
		];
	}

	public function provideDataToSerialize() {
		$p2 = new NumericPropertyId( 'P2' );
		$p2foo = new NumericPropertyId( 'foo:P2' );

		return [
			'string' => [
				'P2',
				new PropertyNoValueSnak( $p2 ),
			],
			'foreign' => [
				'foo:P2',
				new PropertyNoValueSnak( $p2foo ),
			],
		];
	}

	/**
	 * @dataProvider provideDataToSerialize
	 */
	public function testSerialize( $expected, Snak $snak ) {
		$serialized = $snak->serialize();
		$this->assertSame( $expected, $serialized );

		$snak2 = new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) );
		$snak2->unserialize( $serialized );
		$this->assertTrue( $snak->equals( $snak2 ), 'round trip' );
	}

	public function provideDataToUnserialize() {
		$p2 = new NumericPropertyId( 'P2' );
		$p2foo = new NumericPropertyId( 'foo:P2' );

		return [
			'legacy' => [ new PropertyNoValueSnak( $p2 ), 'i:2;' ],
			'current' => [ new PropertyNoValueSnak( $p2 ), 'P2' ],
			'foreign' => [ new PropertyNoValueSnak( $p2foo ), 'foo:P2' ],
		];
	}

	/**
	 * @dataProvider provideDataToUnserialize
	 */
	public function testUnserialize( $expected, $serialized ) {
		$snak = new PropertyNoValueSnak( new NumericPropertyId( 'P1' ) );
		$snak->unserialize( $serialized );
		$this->assertTrue( $snak->equals( $expected ) );
	}

}
