<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataTypeFactory;
use DataValues\DataValueFactory;
use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Repo\SnakConstructionService;

/**
 * @covers Wikibase\Repo\SnakConstructionService
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Snak
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SnakConstructionServiceTest extends \PHPUnit_Framework_TestCase {

	public function newSnakConstructionService() {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeFactory = new DataTypeFactory( array( 'string' => 'string' ) );
		$dataValueFactory = new DataValueFactory( new DataValueDeserializer( array(
			'string' => 'DataValues\StringValue',
		) ) );

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'p1' ), 'string' );

		$service = new SnakConstructionService(
			$dataTypeLookup,
			$dataTypeFactory,
			$dataValueFactory
		);

		return $service;
	}

	/**
	 * @dataProvider newSnakProvider
	 */
	public function testNewSnak( $propertyId, $snakType, $rawValue, $expectedSnakClass, $expectedException = null ) {
		if ( is_int( $propertyId ) ) {
			$propertyId = PropertyId::newFromNumber( $propertyId );
		}

		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
		}

		$service = $this->newSnakConstructionService();

		$snak = $service->newSnak( $propertyId, $snakType, $rawValue );

		$this->assertInstanceOf( $expectedSnakClass, $snak );
	}

	public function newSnakProvider() {
		return array(
			'novalue' => array(
				1, 'novalue', null,
				'Wikibase\DataModel\Snak\PropertyNoValueSnak',
			),
			'somevalue' => array(
				1, 'somevalue', null,
				'Wikibase\DataModel\Snak\PropertySomeValueSnak',
			),
			'value' => array(
				1, 'value', '"hello"',
				'Wikibase\DataModel\Snak\PropertyValueSnak',
			),
			'novalue/badprop' => array(
				66, 'novalue', null,
				'Wikibase\DataModel\Snak\PropertyNoValueSnak',
				'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException'
			),
			'somevalue/badprop' => array(
				66, 'somevalue', null,
				'Wikibase\DataModel\Snak\PropertySomeValueSnak',
				'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException'
			),
			'value/badprop' => array(
				66, 'value', '"hello"',
				'Wikibase\DataModel\Snak\PropertyValueSnak',
				'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException'
			),
			'value/badvalue' => array(
				1, 'value', array( 'foo' ),
				'Wikibase\DataModel\Snak\PropertyValueSnak',
				'InvalidArgumentException'
			),
		);
	}

}
