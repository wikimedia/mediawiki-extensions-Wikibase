<?php

namespace Wikibase\Lib\Test;

use DataValues\BooleanValue;
use DataValues\DataValue;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\InMemoryDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\ValuesFinder;

/**
 * @covers Wikibase\ValuesFinder
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ValuesFinderTest extends \MediaWikiTestCase {

	static $propertyDataTypes = array(
		'P23' => 'string',
		'P42' => 'url',
		'P44' => 'boolean'
	);

	public function snaksProvider() {
		$argLists = array();

		$p23 = new PropertyId( 'p23' );
		$p42 = new PropertyId( 'p42' );
		$p44 = new PropertyId( 'p44' );
		$p404 = new PropertyId( 'P404' );

		$argLists["empty"] = array(
			array(),
			'url',
			array() );

		$argLists["PropertyNoValueSnak"] = array(
			array( new PropertyNoValueSnak( $p42 ) ),
			'url',
			array());

		$argLists["PropertySomeValueSnak"] = array(
			array( new PropertySomeValueSnak( $p42 ) ),
			'url',
			array() );

		$argLists["PropertyValueSnak with string value and unknown data type"] = array(
			array( new PropertyValueSnak( $p404, new StringValue( 'not an url' ) ) ),
			'url',
			array() );

		$argLists["PropertyValueSnak with string value and wrong data type"] = array(
			array( new PropertyValueSnak( $p23, new StringValue( 'not an url' ) ) ),
			'url',
			array() );

		$argLists["PropertyValueSnak with string value and correct data type"] = array(
			array( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' ) ) ),
			'url',
			array( 'http://acme.com/test' ) );

		$argLists["PropertyValueSnak with boolean value"] = array(
			array( new PropertyValueSnak( $p42, new BooleanValue( true ) ) ),
			'url',
			array( true ) );

		$argLists["PropertyValueSnak with string values and correct data type"] = array(
			array( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' ) ),
					new PropertyValueSnak( $p42, new StringValue( 'http://foo.bar/' ) ) ),
			'url',
			array( 'http://acme.com/test', 'http://foo.bar/' ) );

		$argLists["PropertyValueSnak with boolean value and correct data type"] = array(
			array( new PropertyValueSnak( $p44, new BooleanValue( false ) ) ),
			'boolean',
			array( false ) );

		$argLists["PropertyValueSnak with boolean value and wrong data type"] = array(
			array( new PropertyValueSnak( $p44, new BooleanValue( false ) ) ),
			'url',
			array() );

		return $argLists;
	}

	/**
	 * @dataProvider snaksProvider
	 *
	 * @param Snak[] $snaks
	 * @param string $dataType
	 * @param string[] $expected
	 */
	public function testFindFromSnaks( array $snaks, $dataType, array $expected ) {
		$valuesFinder = $this->getValuesFinder();

		$actual = $valuesFinder->findFromSnaks( $snaks, $dataType );

		$actual = array_map( function( DataValue $dataValue ) {
			return $dataValue->getValue();
		}, $actual );

		$this->assertArrayEquals( $expected, $actual ); // assertArrayEquals doesn't take a message :(
	}

	private function getValuesFinder() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		foreach ( self::$propertyDataTypes as $propertyId => $dataType ) {
			$dataTypeLookup->setDataTypeForProperty( new PropertyId( $propertyId ), $dataType );
		}

		return new ValuesFinder( $dataTypeLookup );
	}

}
