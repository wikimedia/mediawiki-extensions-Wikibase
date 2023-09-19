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

	public static function snaksProvider() {
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

		sort( $expected );
		sort( $actual );
		$this->assertSame( $expected, $actual );
	}

	private function getValuesFinder() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		foreach ( self::$propertyDataTypes as $propertyId => $dataType ) {
			$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $propertyId ), $dataType );
		}

		return new ValuesFinder( $dataTypeLookup );
	}

}
