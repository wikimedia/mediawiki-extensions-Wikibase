<?php

namespace Wikibase\DataModel\Tests\Snak;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\DerivedPropertyValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers \Wikibase\DataModel\Snak\DerivedPropertyValueSnak
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class DerivedPropertyValueSnakTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider validConstructorArgumentsProvider
	 */
	public function testConstructor( $propertyId, DataValue $dataValue, array $derivedDataValues ) {
		$snak = new DerivedPropertyValueSnak( $propertyId, $dataValue, $derivedDataValues );
		$this->assertInstanceOf( PropertyValueSnak::class, $snak );
	}

	public function validConstructorArgumentsProvider() {
		return [
			'No extras' => [
				new NumericPropertyId( 'P1' ),
				new StringValue( 'a' ),
				[],
			],
			'2 extras' => [
				new NumericPropertyId( 'P9001' ),
				new StringValue( 'bc' ),
				[ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ],
			],
			'numeric id' => [
				42,
				new StringValue( 'foo' ),
				[],
			],
		];
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException(
		$propertyId,
		DataValue $dataValue,
		array $derivedDataValues
	) {
		$this->expectException( InvalidArgumentException::class );
		new DerivedPropertyValueSnak( $propertyId, $dataValue, $derivedDataValues );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			'fail - Integer key' => [
				new NumericPropertyId( 'P9001' ),
				new StringValue( 'bc' ),
				[ new StringValue( 'foo' ) ],
			],
			'fail - not a value' => [
				new NumericPropertyId( 'P9001' ),
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
			new NumericPropertyId( 'P1' ),
			new StringValue( 'a' ),
			[ 'foo' => new StringValue( 'foo' ) ]
		);
		$hash = $snak->getHash();

		// @codingStandardsIgnoreStart
		$expected = sha1( 'C:48:"Wikibase\DataModel\Snak\DerivedPropertyValueSnak":58:{a:2:{i:0;s:2:"P1";i:1;C:22:"DataValues\StringValue":1:{a}}}' );
		// @codingStandardsIgnoreEnd
		$this->assertSame( $expected, $hash );
	}

	public function testGetDerivedDataValues() {
		$derivedValues = [ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ];

		$snak = new DerivedPropertyValueSnak(
			new NumericPropertyId( 'P9001' ),
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
			new NumericPropertyId( 'P9001' ),
			new StringValue( 'bc' ),
			$derivedValues
		);

		$this->assertEquals( $foo, $snak->getDerivedDataValue( 'foo' ) );
		$this->assertEquals( $bar, $snak->getDerivedDataValue( 'bar' ) );
	}

	public function testSerializationDoesNotContainDerivedValues() {
		$snak = new DerivedPropertyValueSnak(
			new NumericPropertyId( 'P9001' ),
			new StringValue( 'bc' ),
			[ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ]
		);

		$this->assertSame(
			'a:2:{i:0;s:5:"P9001";i:1;O:22:"DataValues\StringValue":1:{i:0;s:2:"bc";}}',
			$snak->serialize(),
		);
	}

	public function testSnakWithDerivedValuesEqualsSnakWithoutDerivedValues() {
		$property = new NumericPropertyId( 'P9001' );
		$value = new StringValue( 'bc' );
		$derivedValues = [ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ];

		$emptyDerivedSnak = new DerivedPropertyValueSnak( $property, $value, [] );
		$derivedSnak = new DerivedPropertyValueSnak( $property, $value, $derivedValues );

		$this->assertTrue( $emptyDerivedSnak->equals( $derivedSnak ) );
	}

	public function testDerivedSnakDoesNoteEqualPropertyValueSnak() {
		$property = new NumericPropertyId( 'P9001' );
		$value = new StringValue( 'bc' );
		$derivedValues = [ 'foo' => new StringValue( 'foo' ), 'bar' => new StringValue( 'bar' ) ];

		$propertySnak = new PropertyValueSnak( $property, $value );
		$emptyDerivedSnak = new DerivedPropertyValueSnak( $property, $value, [] );
		$derivedSnak = new DerivedPropertyValueSnak( $property, $value, $derivedValues );

		$this->assertFalse( $propertySnak->equals( $emptyDerivedSnak ) );
		$this->assertFalse( $propertySnak->equals( $derivedSnak ) );
	}

	public function testNewPropertyValueSnak() {
		$property = new NumericPropertyId( 'P9001' );
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
