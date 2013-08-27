<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\PropertyValueSnak
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyValueSnakTest extends SnakObjectTest {

	public function constructorProvider() {
		$argLists = array(
			array( true, 1, new StringValue( 'a' ) ),
			array( true, 9001, new StringValue( 'a' ) ),
		);

		foreach ( $argLists as &$argList ) {
			if ( count( $argList ) > 1 ) {
				$argList[1] = new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, $argList[1] );
			}
		}

		return $argLists;
	}

	public function getClass() {
		return '\Wikibase\PropertyValueSnak';
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDataValue( PropertyValueSnak $omnomnom ) {
		$dataValue = $omnomnom->getDataValue();
		$this->assertInstanceOf( '\DataValues\DataValue', $dataValue );
		$this->assertTrue( $dataValue->equals( $omnomnom->getDataValue() ) );
	}

	public function newFromPropertyValueProvider() {
		$argLists = array();

		$property = \Wikibase\Property::newFromType( 'wikibase-item' );
		$property->setId( 852645 );

		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 42 ) );
		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 9001 ) );

		$property->setId( 852642 );

		$argLists[] = array( clone $property, new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 9001 ) );

		$property->setDataTypeId( 'commonsMedia' );

		$argLists[] = array( clone $property, new \DataValues\StringValue( 'https://commons.wikimedia.org/wiki/Wikidata' ) );

		return $argLists;
	}

	/**
	 * @dataProvider toArrayProvider
	 */
	public function testToArray( PropertyValueSnak $snak, array $expected ) {
		$actual = $snak->toArray();

		$this->assertEquals( $expected, $actual );
	}

	public static function toArrayProvider() {
		$q1 = new EntityId( Property::ENTITY_TYPE, 1 );

		return array(
			'string-value' => array(
				new PropertyValueSnak( $q1, new StringValue( 'boo' ) ),
				array( 'value', $q1->getNumericId(), 'string', 'boo' )
			),
			'bad-value' => array(
				new PropertyValueSnak( $q1, new UnDeserializableValue( 77, 'string', 'not a string' ) ),
				array( 'value', $q1->getNumericId(), 'string', 77 )
			),
		);
	}
}
