<?php

namespace Wikibase\Lib\Test;

use DataValues\NumberValue;
use DataValues\QuantityValue;
use ValueFormatters\BasicNumberLocalizer;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\QuantityDetailsFormatter;

/**
 * @covers Wikibase\Lib\QuantityDetailsFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class QuantityDetailsFormatterTest extends \PHPUnit_Framework_TestCase {

	private function newFormatter( FormatterOptions $options = null ) {
		$numberLocalizer = new BasicNumberLocalizer();

		$unitFormatter = $this->getMockBuilder( 'ValueFormatters\QuantityUnitFormatter' )
			->disableOriginalConstructor()
			->getMock();
		$unitFormatter->expects( $this->any() )
			->method( 'applyUnit' )
			->will( $this->returnCallback( function( $unit, $numberText ) {
				return $numberText . ' ' . $unit;
			} ) );

		return new QuantityDetailsFormatter( $numberLocalizer, $unitFormatter, $options );
	}

	/**
	 * @dataProvider quantityFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = $this->newFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'en'
		) );

		return array(
			array(
				QuantityValue::newFromNumber( '+5', '1', '+6', '+4' ),
				$options,
				'@' . implode( '.*',
					array(
						'<h4[^<>]*>[^<>]*\b5\b[^<>]*1[^<>]*</h4>',
						'<td[^<>]*>[^<>]*\b5\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b6\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b4\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b1\b[^<>]*</td>',
					)
				) . '@s'
			),
		);
	}

	public function testFormatError() {
		$formatter = $formatter = $this->newFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}

}
