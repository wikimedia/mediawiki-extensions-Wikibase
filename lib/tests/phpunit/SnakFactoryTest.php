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
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseSnak
 * @group Database
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SnakFactoryTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		static $isInitialized = false;

		if ( !class_exists( 'Wikibase\PropertyContent' ) ) {
			//TODO: once SnakFactory uses a PropertyDataTypeLookup, we can get rid of this
			$this->markTestSkipped( 'Can\'t test without Wikibase repo, need PropertyContent for fixture.' );
		}

		if ( !$isInitialized ) {
			$p1 = Property::newEmpty();
			$p1->setDataTypeId( 'string' );
			$p1->setId( 1 );

			$p1content = PropertyContent::newFromProperty( $p1 );
			$p1content->save( 'testing ' );

			$isInitialized = true;
		}
	}

	public static function provideNewSnak() {
		return array(
			array( 1, 'somevalue', null, null, 'Wikibase\PropertySomeValueSnak', null, null, 'some value' ),
			array( 1, 'novalue', null, null, 'Wikibase\PropertyNoValueSnak', null, null, 'no value' ),
			array( 1, 'value', 'string', 'foo', 'Wikibase\PropertyValueSnak', null, null, 'a value' ),
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
			$dataValue = DataValueFactory::singleton()->newDataValue( $valueType, $snakValue );
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