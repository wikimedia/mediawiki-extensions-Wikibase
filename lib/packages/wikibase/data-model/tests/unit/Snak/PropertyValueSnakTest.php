<?php

namespace Wikibase\Test\Snak;

use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Snak\PropertyValueSnak
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
			array( true, 'P1', new StringValue( 'a' ) ),
			array( true, 'P9001', new StringValue( 'a' ) ),
		);

		foreach ( $argLists as &$argList ) {
			if ( count( $argList ) > 1 ) {
				$argList[1] = new PropertyId( $argList[1] );
			}
		}

		return $argLists;
	}

	public function getClass() {
		return '\Wikibase\DataModel\Snak\PropertyValueSnak';
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

		$property = Property::newFromType( 'wikibase-item' );
		$property->setId( 852645 );

		$argLists[] = array( clone $property, new ItemId( 'Q42' ) );
		$argLists[] = array( clone $property, new ItemId( 'Q9001' ) );

		$property->setId( 852642 );

		$argLists[] = array( clone $property, new ItemId( 'Q9001' ) );

		$property->setDataTypeId( 'commonsMedia' );

		$argLists[] = array( clone $property, new StringValue( 'https://commons.wikimedia.org/wiki/Wikidata' ) );

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
		$q1 = new PropertyId( 'P1' );

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
