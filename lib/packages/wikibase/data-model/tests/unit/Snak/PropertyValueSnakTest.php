<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers Wikibase\DataModel\Snak\PropertyValueSnak
 * @covers Wikibase\DataModel\Snak\SnakObject
 * @uses DataValues\StringValue
 * @uses Wikibase\DataModel\Entity\EntityId
 * @uses Wikibase\DataModel\Entity\PropertyId
 * @uses DataValues\DataValueObject
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class PropertyValueSnakTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider validConstructorArgumentsProvider
	 */
	public function testConstructor( $propertyId, DataValue $dataValue ) {
		$snak = new PropertyValueSnak( $propertyId, $dataValue );
		$this->assertInstanceOf( 'Wikibase\DataModel\Snak\PropertyValueSnak', $snak );
	}

	public function validConstructorArgumentsProvider() {
		return array(
			array( 1, new StringValue( 'a' ) ),
			array( new PropertyId( 'P1' ), new StringValue( 'a' ) ),
			array( new PropertyId( 'P9001' ), new StringValue( 'bc' ) ),
		);
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException(
		$propertyId,
		DataValue $dataValue
	) {
		new PropertyValueSnak( $propertyId, $dataValue );
	}

	public function invalidConstructorArgumentsProvider() {
		$stringValue = new StringValue( 'a' );

		return array(
			array( null, $stringValue ),
			array( 0.1, $stringValue ),
			array( 'Q1', $stringValue ),
			array( new ItemId( 'Q1' ), $stringValue ),
		);
	}

	public function testGetPropertyId() {
		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) );
		$propertyId = $snak->getPropertyId();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\PropertyId', $propertyId );
	}

	public function testGetHash() {
		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) );
		$hash = $snak->getHash();
		$this->assertInternalType( 'string', $hash );
		$this->assertEquals( 40, strlen( $hash ) );
	}

	/**
	 * This test is a safeguard to make sure hashes are not changed unintentionally.
	 */
	public function testHashStability() {
		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) );
		$hash = $snak->getHash();

		// @codingStandardsIgnoreStart
		$expected = sha1( 'C:41:"Wikibase\DataModel\Snak\PropertyValueSnak":53:{a:2:{i:0;i:1;i:1;C:22:"DataValues\StringValue":1:{a}}}' );
		// @codingStandardsIgnoreEnd
		$this->assertSame( $expected, $hash );
	}

	public function testEquals() {
		$snak1 = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) );
		$snak2 = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) );
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
		return array(
			array(
				new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) ),
				new PropertyValueSnak( new PropertyId( 'P2' ), new StringValue( 'a' ) )
			),
			array(
				new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) ),
				new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'b' ) )
			),
		);
	}

	public function testSerialize() {
		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) );
		$this->assertSame( 'a:2:{i:0;i:1;i:1;C:22:"DataValues\StringValue":1:{a}}', $snak->serialize() );
	}

	public function testUnserialize() {
		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) );
		$snak->unserialize( 'a:2:{i:0;i:2;i:1;C:22:"DataValues\StringValue":1:{b}}' );
		$expected = new PropertyValueSnak( new PropertyId( 'P2' ), new StringValue( 'b' ) );
		$this->assertTrue( $snak->equals( $expected ) );
	}

	public function testGetDataValue() {
		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) );
		$dataValue = $snak->getDataValue();
		$this->assertInstanceOf( 'DataValues\DataValue', $dataValue );
		$this->assertTrue( $dataValue->equals( new StringValue( 'a' ) ) );
	}

}
