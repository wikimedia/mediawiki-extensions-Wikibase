<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\DataValueFactory;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\SnakConstructionService;
use Wikibase\SnakFactory;

/**
 * @covers Wikibase\Lib\SnakConstructionService
 *
 * @group Wikibase
 * @group WikibaseLib
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
		$dataValueFactory = DataValueFactory::singleton();

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
	public function testNewSnak( $propertyId, $snakType, $rawValue, $expectedSnakClass, $expectedValue, $expectedException ) {
		if ( is_int( $propertyId ) ) {
			$propertyId = PropertyId::newFromNumber( $propertyId );
		}

		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
		}

		$service = $this->newSnakConstructionService();

		$snak = $service->newSnak( $propertyId, $snakType, $rawValue );

		$this->assertInstanceOf( $expectedSnakClass, $snak );

		if ( $expectedValue ) {
			$this->assertEmpty( $expectedValue, $snak->getValue() );
		}
	}

	public function newSnakProvider() {
		return array(
			'novalue' => array( 1, 'novalue', null, 'Wikibase\PropertyNoValueSnak', null, null ),
			'somevalue' => array( 1, 'somevalue', null, 'Wikibase\PropertySomeValueSnak', null, null ),
			'value' => array( 1, 'value', '"hello"', 'Wikibase\PropertyValueSnak', null, null ),

			'novalue/badprop' => array( 66, 'novalue', null, 'Wikibase\PropertyNoValueSnak', null, 'Wikibase\Lib\PropertyNotFoundException' ),
			'somevalue/badprop' => array( 66, 'somevalue', null, 'Wikibase\PropertySomeValueSnak', null, 'Wikibase\Lib\PropertyNotFoundException' ),
			'value/badprop' => array( 66, 'value', '"hello"', 'Wikibase\PropertyValueSnak', null, 'Wikibase\Lib\PropertyNotFoundException' ),

			'value/badvalue' => array( 1, 'value', array( "foo" ), 'Wikibase\PropertyValueSnak', null, 'DataValues\IllegalValueException' ),
		);
	}

}
