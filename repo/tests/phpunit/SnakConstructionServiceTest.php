<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\DataValueFactory;
use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Repo\SnakConstructionService;
use Wikibase\SnakFactory;

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
		$snakFactory = new SnakFactory();
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeFactory = new DataTypeFactory();
		$dataValueFactory = new DataValueFactory( new DataValueDeserializer( array(
			'string' => 'DataValues\StringValue',
		) ) );

		$dataTypeFactory->registerDataType( new DataType( 'string', 'string', array() ) );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'p1' ), 'string' );

		$service = new SnakConstructionService(
			$snakFactory,
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
				'Wikibase\DataModel\Services\Lookup\PropertyNotFoundException'
			),
			'somevalue/badprop' => array(
				66, 'somevalue', null,
				'Wikibase\DataModel\Snak\PropertySomeValueSnak',
				'Wikibase\DataModel\Services\Lookup\PropertyNotFoundException'
			),
			'value/badprop' => array(
				66, 'value', '"hello"',
				'Wikibase\DataModel\Snak\PropertyValueSnak',
				'Wikibase\DataModel\Services\Lookup\PropertyNotFoundException'
			),
			'value/badvalue' => array(
				1, 'value', array( 'foo' ),
				'Wikibase\DataModel\Snak\PropertyValueSnak',
				'InvalidArgumentException'
			),
		);
	}

}
