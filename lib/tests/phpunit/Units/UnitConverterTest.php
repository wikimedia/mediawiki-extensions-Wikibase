<?php
namespace Wikibase\Test;

use DataValues\DecimalMath;
use DataValues\DecimalValue;
use DataValues\QuantityValue;
use PHPUnit_Framework_TestCase;
use Wikibase\Lib\UnitConverter;
use Wikibase\Lib\UnitStorage;

/**
 * @covers Wikibase\Lib\UnitConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class UnitConverterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var UnitConverter
	 */
	private $uc;

	public function setUp() {
	}

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
				'+197520000000000000000000000000000000000000000000.00'
			],
			[ 'Q1', '+160', 'http://acme.test/Q2', '+19752' ],
			[ 'Q1', '+1600', 'http://acme.test/Q2', '+197520' ],
			[ 'Q1', '+0.131', 'http://acme.test/Q2', '+16.17195' ],
		];
	}

	private function getConverter( $fromUnit, $result ) {
		$mockStorage = $this->getMock( UnitStorage::class );
		$mockStorage->method( 'getConversion' )->with( $fromUnit )->willReturn( $result );
		return new UnitConverter( $mockStorage, 'http://acme.test/' );
	}

	/**
	 * @dataProvider getConverterPairs
	 * @param $fromUnit
	 * @param $fromValue
	 * @param $toUnit
	 * @param $toValue
	 */
	public function testConvert( $fromUnit, $fromValue, $toUnit, $toValue ) {
		$uc = $this->getConverter( $fromUnit, [ '123.45', 'Q2' ] );

		$decimal = new DecimalValue( $fromValue );
		$q = new QuantityValue( $decimal, $fromUnit, $decimal, $decimal );
		$qConverted = $uc->toStandardUnits( $q );

		$this->assertEquals( $toValue, $qConverted->getAmount()->getValue(), 'Wrong amount' );
		$this->assertEquals( $toUnit, $qConverted->getUnit(), 'Wrong unit' );
	}

	public function testConvertPrefixes() {
		$uc = $this->getConverter( 'Q123', [ '123.45', 'Q345' ] );
		$decimal = new DecimalValue( '+0.111' );
		$q = new QuantityValue( $decimal, 'http://acme.test/Q123', $decimal, $decimal );
		$qConverted = $uc->toStandardUnits( $q );

		$this->assertEquals( '+13.70295', $qConverted->getAmount()->getValue(), 'Wrong amount' );
		$this->assertEquals( 'http://acme.test/Q345', $qConverted->getUnit(), 'Wrong unit' );

	}

	public function testBounds() {
		$uc = $this->getConverter( 'Q123', [ '123.45', 'Q345' ] );
		$decimal = new DecimalValue( '+0.111' );
		$low = new DecimalValue( '+0.1105' );
		$up = new DecimalValue( '+0.1150' );
		$q = new QuantityValue( $decimal, 'http://acme.test/Q123', $up, $low );
		$qConverted = $uc->toStandardUnits( $q );

		$this->assertEquals( '+13.70', $qConverted->getAmount()->getValue(), 'Wrong amount' );
		$this->assertEquals( 'http://acme.test/Q345', $qConverted->getUnit(), 'Wrong unit' );
		$this->assertEquals( '+14.20', $qConverted->getUpperBound()->getValue(),
			'Wrong upper bound' );
		$this->assertEquals( '+13.64', $qConverted->getLowerBound()->getValue(),
			'Wrong lower bound' );
	}

	public function getBadConversions() {
		return [
			[ null ],
		    [ [ '1', 'Q123' ] ],
			[ [ '43', 'Q123' ] ],
			[ [ '-1', 'Q23' ] ],
			[ [ '0', 'Q23' ] ],
			[ [ '0.0', 'Q23' ] ],
		];
	}

	/**
	 * Cases where no conversion should happen
	 * @dataProvider getBadConversions
	 * @param array $conv Conversion result data
	 */
	public function testNoConversion( $conv ) {
		$uc = $this->getConverter( 'Q123', $conv );

		$decimal = new DecimalValue( '+42' );
		$q = new QuantityValue( $decimal, 'http://acme.test/Q123', $decimal, $decimal );
		$qConverted = $uc->toStandardUnits( $q );
		$this->assertSame( $q, $qConverted );
	}

}
