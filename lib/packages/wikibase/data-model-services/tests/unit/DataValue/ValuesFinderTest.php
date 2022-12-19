<?php

namespace Wikibase\DataModel\Services\Tests\DataValue;

use DataValues\BooleanValue;
use DataValues\DataValue;
use DataValues\StringValue;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\DataValue\ValuesFinder;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers \Wikibase\DataModel\Services\DataValue\ValuesFinder
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ValuesFinderTest extends TestCase {

	private static $propertyDataTypes = [
		'P23' => 'string',
		'P42' => 'url',
		'P44' => 'boolean',
	];

	public function snaksProvider() {
		$argLists = [];

		$p23 = new NumericPropertyId( 'p23' );
		$p42 = new NumericPropertyId( 'p42' );
		$p44 = new NumericPropertyId( 'p44' );
		$p404 = new NumericPropertyId( 'P404' );

		$argLists['empty'] = [
			[],
			'url',
			[] ];

		$argLists['PropertyNoValueSnak'] = [
			[ new PropertyNoValueSnak( $p42 ) ],
			'url',
			[] ];

		$argLists['PropertySomeValueSnak'] = [
			[ new PropertySomeValueSnak( $p42 ) ],
			'url',
			[] ];

		$argLists['PropertyValueSnak with string value and unknown data type'] = [
			[ new PropertyValueSnak( $p404, new StringValue( 'not an url' ) ) ],
			'url',
			[] ];

		$argLists['PropertyValueSnak with string value and wrong data type'] = [
			[ new PropertyValueSnak( $p23, new StringValue( 'not an url' ) ) ],
			'url',
			[] ];

		$argLists['PropertyValueSnak with string value and correct data type'] = [
			[ new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' ) ) ],
			'url',
			[ 'http://acme.com/test' ] ];

		$argLists['PropertyValueSnak with boolean value'] = [
			[ new PropertyValueSnak( $p42, new BooleanValue( true ) ) ],
			'url',
			[ true ] ];

		$argLists['PropertyValueSnak with string values and correct data type'] = [
			[ new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' ) ),
					new PropertyValueSnak( $p42, new StringValue( 'http://foo.bar/' ) ) ],
			'url',
			[ 'http://acme.com/test', 'http://foo.bar/' ] ];

		$argLists['PropertyValueSnak with boolean value and correct data type'] = [
			[ new PropertyValueSnak( $p44, new BooleanValue( false ) ) ],
			'boolean',
			[ false ] ];

		$argLists['PropertyValueSnak with boolean value and wrong data type'] = [
			[ new PropertyValueSnak( $p44, new BooleanValue( false ) ) ],
			'url',
			[] ];

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

		$actual = array_map( static function( DataValue $dataValue ) {
			return $dataValue->getValue();
		}, $actual );

		$this->assertArrayEquals( $expected, $actual ); // assertArrayEquals doesn't take a message :(
	}

	private function getValuesFinder() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		foreach ( self::$propertyDataTypes as $propertyId => $dataType ) {
			$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $propertyId ), $dataType );
		}

		return new ValuesFinder( $dataTypeLookup );
	}

	/**
	 * Assert that two arrays are equal. By default this means that both arrays need to hold
	 * the same set of values. Using additional arguments, order and associated key can also
	 * be set as relevant.
	 *
	 * @param array $expected
	 * @param array $actual
	 * @param bool $ordered If the order of the values should match
	 * @param bool $named If the keys should match
	 */
	private function assertArrayEquals( array $expected, array $actual,
		$ordered = false, $named = false
	) {
		if ( !$ordered ) {
			$this->objectAssociativeSort( $expected );
			$this->objectAssociativeSort( $actual );
		}

		if ( !$named ) {
			$expected = array_values( $expected );
			$actual = array_values( $actual );
		}

		call_user_func_array(
			[ $this, 'assertEquals' ],
			array_merge( [ $expected, $actual ], array_slice( func_get_args(), 4 ) )
		);
	}

	/**
	 * Does an associative sort that works for objects.
	 *
	 * @param array $array
	 */
	private function objectAssociativeSort( array &$array ) {
		uasort(
			$array,
			static function ( $a, $b ) {
				return serialize( $a ) > serialize( $b ) ? 1 : -1;
			}
		);
	}

}
