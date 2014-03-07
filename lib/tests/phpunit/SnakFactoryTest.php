<?php

namespace Wikibase\Test;

use DataValues\DataValueFactory;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\PropertyValueSnak;
use Wikibase\SnakFactory;

/**
 * @covers Wikibase\SnakFactory
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseSnak
 * @group Database
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SnakFactoryTest extends \MediaWikiTestCase {

	public static function provideNewSnak() {
		return array(
			array( 1, 'somevalue', null, null, 'Wikibase\PropertySomeValueSnak', null, null, 'some value' ),
			array( 1, 'novalue', null, null, 'Wikibase\PropertyNoValueSnak', null, null, 'no value' ),
			array( 1, 'value', 'string', 'foo', 'Wikibase\PropertyValueSnak', 'DataValues\StringValue', null, 'a value' ),
			array( 1, 'kittens', null, 'foo', null, null, 'InvalidArgumentException', 'bad snak type' ),
		);
	}

	/**
	 * @dataProvider provideNewSnak
	 */
	public function testNewSnak( $propertyId, $snakType, $valueType, $snakValue, $expectedSnakClass, $expectedValueClass, $expectedException, $message ) {
		if ( is_int( $propertyId ) ) {
			$propertyId = PropertyId::newFromNumber( $propertyId );
		}

		if ( $valueType !== null ) {
			$dataValueFactory = new DataValueFactory();
			$dataValueFactory->registerDataValue( $valueType, $expectedValueClass );

			$dataValue = $dataValueFactory->newDataValue( $valueType, $snakValue );
		} else {
			$dataValue = null;
		}

		if ( $expectedException !== null ) {
			$this->setExpectedException( $expectedException );
		}

		$factory = new SnakFactory();
		$snak = $factory->newSnak( $propertyId, $snakType, $dataValue );

		if ( $expectedSnakClass !== null ) {
			$this->assertInstanceOf( $expectedSnakClass, $snak, $message );
		}

		if ( $expectedValueClass !== null && $snak instanceof PropertyValueSnak ) {
			$dataValue = $snak->getDataValue();
			$this->assertInstanceOf( $expectedValueClass, $dataValue, $message );
		}
	}

}