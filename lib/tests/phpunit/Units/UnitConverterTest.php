<?php

namespace Wikibase\Lib\Tests\Units;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use MediaWikiTestCaseTrait;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Lib\Units\UnitStorage;

/**
 * @covers \Wikibase\Lib\Units\UnitConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnitConverterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	public function getConverterPairs() {
		return [
			[ 'Q1', '+16', 'http://acme.test/Q2', '+1975.2' ],
			[ 'Q1', '-16', 'http://acme.test/Q2', '-1975.2' ],
			[ 'Q1', '+1.6', 'http://acme.test/Q2', '+197.52' ],
			[ 'Q1', '+0.16', 'http://acme.test/Q2', '+19.752' ],
			[ 'Q1', '+0.1600', 'http://acme.test/Q2', '+19.752' ],
			[ 'Q1', '-0.00000000000000000016', 'http://acme.test/Q2', '-0.000000000000000019752' ],
			[
				'Q1',
				'+1600000000000000000000000000000000000000000000',
				'http://acme.test/Q2',
				'+1975200000000000\d{32}\b',
			],
			[ 'Q1', '+160', 'http://acme.test/Q2', '+19752' ],
			[ 'Q1', '+1600', 'http://acme.test/Q2', '+197520' ],
			[ 'Q1', '+0.131', 'http://acme.test/Q2', '+16.17195' ],
		];
	}

	/**
	 * @param string $fromUnit
	 * @param string[]|null $result
	 *
	 * @return UnitConverter
	 */
	private function getConverter( $fromUnit, array $result = null ) {
		if ( $result ) {
			$result = [ 'factor' => $result[0], 'unit' => $result[1] ];
		}
		$mockStorage = $this->createMock( UnitStorage::class );
		$mockStorage->method( 'getConversion' )->with( $fromUnit )->willReturn( $result );
		return new UnitConverter( $mockStorage, 'http://acme.test/' );
	}

	/**
	 * @dataProvider getConverterPairs
	 */
	public function testConvert( $fromUnit, $fromValue, $toUnit, $toValue ) {
		$uc = $this->getConverter( $fromUnit, [ '123.45', 'Q2' ] );

		$decimal = new DecimalValue( $fromValue );
		$q = new QuantityValue( $decimal, $fromUnit, $decimal, $decimal );
		$qConverted = $uc->toStandardUnits( $q );

		$this->assertMatchesRegularExpression( "/^\\$toValue/", $qConverted->getAmount()->getValue(), 'Wrong amount' );
		$this->assertEquals( $toUnit, $qConverted->getUnit(), 'Wrong unit' );
	}

	public function testConvertPrefixes() {
		$uc = $this->getConverter( 'Q123', [ '123.45', 'Q345' ] );
		$decimal = new DecimalValue( '+0.111' );
		$q = new QuantityValue( $decimal, 'http://acme.test/Q123', $decimal, $decimal );
		$qConverted = $uc->toStandardUnits( $q );

		$this->assertStringStartsWith( '+13.70295', $qConverted->getAmount()->getValue(), 'Wrong amount' );
		$this->assertEquals( 'http://acme.test/Q345', $qConverted->getUnit(), 'Wrong unit' );
	}

	public function testBounds() {
		$uc = $this->getConverter( 'Q123', [ '123.45', 'Q345' ] );
		$decimal = new DecimalValue( '+0.111' );
		$low = new DecimalValue( '+0.1105' );
		$up = new DecimalValue( '+0.1150' );
		$q = new QuantityValue( $decimal, 'http://acme.test/Q123', $up, $low );
		$qConverted = $uc->toStandardUnits( $q );

		$this->assertSame( '+13.70', $qConverted->getAmount()->getValue(), 'Wrong amount' );
		$this->assertEquals( 'http://acme.test/Q345', $qConverted->getUnit(), 'Wrong unit' );
		$this->assertSame( '+14.20', $qConverted->getUpperBound()->getValue(),
			'Wrong upper bound' );
		$this->assertSame( '+13.64', $qConverted->getLowerBound()->getValue(),
			'Wrong lower bound' );
	}

	public function getBadConversions() {
		return [
			[ null, true ],
			[ [ '1', 'Q123' ], false ],
			[ [ '43', 'Q123' ], false ],
			[ [ '-1', 'Q23' ], true ],
			[ [ -1, 'Q23' ], true ],
			[ [ '0', 'Q23' ], true ],
			[ [ '0.0', 'Q23' ], true ],
		];
	}

	/**
	 * Cases where no conversion should happen
	 * @dataProvider getBadConversions
	 * @param string[]|null $conv Conversion result data
	 * @param bool $expectNull Should result be null?
	 */
	public function testNoConversion( ?array $conv, $expectNull ) {
		$uc = $this->getConverter( 'Q123', $conv );

		$decimal = new DecimalValue( '+42' );
		$q = new QuantityValue( $decimal, 'http://acme.test/Q123', $decimal, $decimal );
		$qConverted = $uc->toStandardUnits( $q );
		if ( $expectNull ) {
			$this->assertNull( $qConverted );
		} else {
			$this->assertSame( $q, $qConverted );
		}
	}

}
