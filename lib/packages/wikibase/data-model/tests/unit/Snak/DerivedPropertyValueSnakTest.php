<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\DerivedPropertyValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Snak\DerivedPropertyValueSnak
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class DerivedPropertyValueSnakTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider validConstructorArgumentsProvider
	 */
	public function testConstructor( $propertyId, DataValue $dataValue, array $derivedDataValues ) {
		$snak = new DerivedPropertyValueSnak( $propertyId, $dataValue, $derivedDataValues );
		$this->assertInstanceOf( 'Wikibase\DataModel\Snak\PropertyValueSnak', $snak );
	}

	public function validConstructorArgumentsProvider() {
		return [
			'No extras' => [
				new PropertyId( 'P1' ),
				new StringValue( 'a' ),
				[],
			],
			'2 extras' => [
				new PropertyId( 'P9001' ),
				new StringValue( 'bc' ),
				[ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ],
			],
			'numeric id' => [
				42,
				new StringValue( 'foo' ),
				[]
			]
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException(
		$propertyId,
		DataValue $dataValue,
		array $derivedDataValues
	) {
		new DerivedPropertyValueSnak( $propertyId, $dataValue, $derivedDataValues );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			'fail - Integer key' => [
				new PropertyId( 'P9001' ),
				new StringValue( 'bc' ),
				[ new StringValue( 'foo' ) ],
			],
			'fail - not a value' => [
				new PropertyId( 'P9001' ),
				new StringValue( 'bc' ),
				[ 'foo' => 'bar' ],
			],
		];
	}

	/**
	 * This test is a safeguard to make sure hashes are not changed unintentionally.
	 */
	public function testHashStability() {
		$snak = new DerivedPropertyValueSnak(
			new PropertyId( 'P1' ),
			new StringValue( 'a' ),
			[ 'foo' => new StringValue( 'foo' ) ]
		);
		$hash = $snak->getHash();

		// @codingStandardsIgnoreStart
		$expected = sha1( 'C:48:"Wikibase\DataModel\Snak\DerivedPropertyValueSnak":53:{a:2:{i:0;i:1;i:1;C:22:"DataValues\StringValue":1:{a}}}' );
		// @codingStandardsIgnoreEnd
		$this->assertSame( $expected, $hash );
	}

	public function testGetDerivedDataValues() {
		$derivedValues = [ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ];

		$snak = new DerivedPropertyValueSnak(
			new PropertyId( 'P9001' ),
			new StringValue( 'bc' ),
			$derivedValues
		);

		$this->assertEquals( $derivedValues, $snak->getDerivedDataValues() );
	}

	public function testGetDerivedDataValue() {
		$foo = new StringValue( 'foo' );
		$bar = new StringValue( 'bar' );
		$derivedValues = [ 'foo' => $foo, 'bar' => $bar ];

		$snak = new DerivedPropertyValueSnak(
			new PropertyId( 'P9001' ),
			new StringValue( 'bc' ),
			$derivedValues
		);

		$this->assertEquals( $foo, $snak->getDerivedDataValue( 'foo' ) );
		$this->assertEquals( $bar, $snak->getDerivedDataValue( 'bar' ) );
	}

	public function testSerializationDoesNotContainDerivedValues() {
		$snak = new DerivedPropertyValueSnak(
			new PropertyId( 'P9001' ),
			new StringValue( 'bc' ),
			[ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ]
		);

		$this->assertEquals(
			'a:2:{i:0;i:9001;i:1;C:22:"DataValues\StringValue":2:{bc}}',
			$snak->serialize()
		);
	}

	public function testSnakWithDerivedValuesEqualsSnakWithoutDerivedValues() {
		$property = new PropertyId( 'P9001' );
		$value = new StringValue( 'bc' );
		$derivedValues = [ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ];

		$emptyDerivedSnak = new DerivedPropertyValueSnak( $property, $value, [] );
		$derivedSnak = new DerivedPropertyValueSnak( $property, $value, $derivedValues );

		$this->assertTrue( $emptyDerivedSnak->equals( $derivedSnak ) );
	}

	public function testDerivedSnakDoesNoteEqualPropertyValueSnak() {
		$property = new PropertyId( 'P9001' );
		$value = new StringValue( 'bc' );
		$derivedValues = [ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ];

		$propertySnak = new PropertyValueSnak( $property, $value );
		$emptyDerivedSnak = new DerivedPropertyValueSnak( $property, $value, [] );
		$derivedSnak = new DerivedPropertyValueSnak( $property, $value, $derivedValues );

		$this->assertFalse( $propertySnak->equals( $emptyDerivedSnak ) );
		$this->assertFalse( $propertySnak->equals( $derivedSnak ) );
	}

	public function testNewPropertyValueSnak() {
		$property = new PropertyId( 'P9001' );
		$value = new StringValue( 'bc' );
		$derivedValues = [ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ];

		$propertySnak = new PropertyValueSnak( $property, $value );
		$derivedSnak = new DerivedPropertyValueSnak( $property, $value, $derivedValues );

		$this->assertEquals(
			$propertySnak,
			$derivedSnak->newPropertyValueSnak()
		);
	}

}
