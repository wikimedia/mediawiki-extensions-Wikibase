<?php
 /**
 *
 * Copyright © 20.06.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 * @group Database
 *
 * @license GPL 2+
 * @file
 *
 * @author Daniel Kinzler
 */

namespace Wikibase\Test;

use DataValues\DataValueFactory;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\PropertyValueSnak;
use Wikibase\SnakFactory;

/**
 * Class SnakFactoryTest
 * @covers Wikibase\SnakFactory
 * @package Wikibase\Test
 */
class SnakFactoryTest extends \PHPUnit_Framework_TestCase {

	public function setUp() {
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
			$propertyId = new EntityId( Property::ENTITY_TYPE, $propertyId );
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