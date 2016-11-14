<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataTypeFactory;
use DataValues\DataValueFactory;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\StringValue;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\SnakFactory;

/**
 * @covers Wikibase\Repo\SnakFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Snak
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SnakFactoryTest extends PHPUnit_Framework_TestCase {

	public function newInstance() {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeFactory = new DataTypeFactory( [ 'string' => 'string' ] );
		$dataValueFactory = new DataValueFactory( new DataValueDeserializer( [
			'string' => StringValue::class,
		] ) );

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'p1' ), 'string' );

		$service = new SnakFactory(
			$dataTypeLookup,
			$dataTypeFactory,
			$dataValueFactory
		);

		return $service;
	}

	/**
	 * @dataProvider newSnakProvider
	 */
	public function testNewSnak(
		$propertyId,
		$snakType,
		$rawValue,
		$expectedSnakClass,
		$expectedException = null
	) {
		if ( is_int( $propertyId ) ) {
			$propertyId = PropertyId::newFromNumber( $propertyId );
		}

		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
		}

		$service = $this->newInstance();

		$snak = $service->newSnak( $propertyId, $snakType, $rawValue );

		$this->assertInstanceOf( $expectedSnakClass, $snak );
	}

	public function newSnakProvider() {
		return [
			'novalue' => [
				1, 'novalue', null,
				PropertyNoValueSnak::class,
			],
			'somevalue' => [
				1, 'somevalue', null,
				PropertySomeValueSnak::class,
			],
			'value' => [
				1, 'value', '"hello"',
				PropertyValueSnak::class,
			],
			'novalue/badprop' => [
				66, 'novalue', null,
				PropertyNoValueSnak::class,
				PropertyDataTypeLookupException::class
			],
			'somevalue/badprop' => [
				66, 'somevalue', null,
				PropertySomeValueSnak::class,
				PropertyDataTypeLookupException::class
			],
			'value/badprop' => [
				66, 'value', '"hello"',
				PropertyValueSnak::class,
				PropertyDataTypeLookupException::class
			],
			'value/badvalue' => [
				1, 'value', [ 'foo' ],
				PropertyValueSnak::class,
				InvalidArgumentException::class
			],
		];
	}

}
