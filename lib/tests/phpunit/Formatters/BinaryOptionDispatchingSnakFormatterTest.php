<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use DataValues\DataValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\BinaryOptionDispatchingSnakFormatter;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\BinaryOptionDispatchingSnakFormatterTest
 *
 * @group SnakFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class BinaryOptionDispatchingSnakFormatterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider formatSnakProvider
	 */
	public function testFormatSnak(
		Snak $snak,
		$specialHandling
	) {
		$formatter = new BinaryOptionDispatchingSnakFormatter(
			'text/whatever',
			$this->getPropertyDataTypeLookup(),
			$this->getSnakFormatter( $specialHandling ? 1 : 0, 'a' ),
			$this->getSnakFormatter( $specialHandling ? 0 : 1, 'b' ),
			[ 'sdfd', 'special', 'dsfd' ]
		);

		$this->assertSame(
			$specialHandling ? 'a' : 'b',
			$formatter->formatSnak( $snak )
		);
	}

	public function formatSnakProvider() {
		$pSpecial = new PropertyId( 'P1' );
		$pRegular = new PropertyId( 'P2' );

		return [
			'PropertyNoValueSnak gets fallback treatment always' => [
				new PropertyNoValueSnak( $pSpecial ),
				false
			],
			'PropertySomeValueSnak gets fallback treatment always' => [
				new PropertySomeValueSnak( $pSpecial ),
				false
			],
			'PropertyValueSnak with special treatment' => [
				new PropertyValueSnak( $pSpecial, $this->getMock( DataValue::class ) ),
				true
			],
			'PropertyValueSnak without special treatment' => [
				new PropertyValueSnak( $pRegular, $this->getMock( DataValue::class ) ),
				false
			]
		];
	}

	public function testGetFormat() {
		$formatter = new BinaryOptionDispatchingSnakFormatter(
			'text/whatever',
			$this->getMock( PropertyDataTypeLookup::class ),
			$this->getMock( SnakFormatter::class ),
			$this->getMock( SnakFormatter::class ),
			[]
		);

		$this->assertSame( 'text/whatever', $formatter->getFormat() );
	}

	private function getSnakFormatter( $expectedCallCount, $result = '' ) {
		$snakFormatter = $this->getMock( SnakFormatter::class );
		$snakFormatter->expects( $this->exactly( $expectedCallCount ) )
			->method( 'formatSnak' )
			->with( $this->isInstanceOf( Snak::class ) )
			->will( $this->returnValue( $result ) );

		return $snakFormatter;
	}

	private function getPropertyDataTypeLookup() {
		$propertyDataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$propertyDataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( function( PropertyId $propertyId ) {
				switch ( $propertyId->getSerialization() ) {
					case 'P1':
						return 'special';
					case 'P2':
						return 'something';
					default:
						$this->fail( 'Unexpcted PropertyId' );
				}
			} );

		return $propertyDataTypeLookup;
	}

}
