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
		$dataTypeFactory = new DataTypeFactory( array( 'string' => 'string' ) );
		$dataValueFactory = new DataValueFactory( new DataValueDeserializer( array(
			'string' => StringValue::class,
		) ) );

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
		return array(
			'novalue' => array(
				1, 'novalue', null,
				PropertyNoValueSnak::class,
			),
			'somevalue' => array(
				1, 'somevalue', null,
				PropertySomeValueSnak::class,
			),
			'value' => array(
				1, 'value', '"hello"',
				PropertyValueSnak::class,
			),
			'novalue/badprop' => array(
				66, 'novalue', null,
				PropertyNoValueSnak::class,
				PropertyDataTypeLookupException::class
			),
			'somevalue/badprop' => array(
				66, 'somevalue', null,
				PropertySomeValueSnak::class,
				PropertyDataTypeLookupException::class
			),
			'value/badprop' => array(
				66, 'value', '"hello"',
				PropertyValueSnak::class,
				PropertyDataTypeLookupException::class
			),
			'value/badvalue' => array(
				1, 'value', array( 'foo' ),
				PropertyValueSnak::class,
				InvalidArgumentException::class
			),
		);
	}

}
