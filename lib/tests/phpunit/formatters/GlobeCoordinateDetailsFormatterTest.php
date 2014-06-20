<?php

namespace Wikibase\Lib\Test;

use DataValues\GlobeCoordinateValue;
use DataValues\LatLongValue;
use DataValues\NumberValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\GlobeCoordinateDetailsFormatter;

/**
 * @covers Wikibase\Lib\GlobeCoordinateDetailsFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class GlobeCoordinateDetailsFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider quantityFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new GlobeCoordinateDetailsFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'en'
		) );

		return array(
			array(
				new GlobeCoordinateValue( new LatLongValue( 50, 11 ), 1, GlobeCoordinateValue::GLOBE_EARTH ),
				$options,
				'@' . implode( '.*',
					array(
						'<h4[^<>]*>[^<>]*50[^<>]+11[^<>]*</h4>',
						'<td[^<>]*>[^<>]*50[^<>]*</td>',
						'<td[^<>]*>[^<>]*11[^<>]*</td>',
						'<td[^<>]*>[^<>]*1[^<>]*</td>',
						'<td[^<>]*>[^<>]*.*Q2[^<>]*</td>',
					)
				) . '@s'
			),
		);
	}

	public function testFormatError() {
		$formatter = new GlobeCoordinateDetailsFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}
}
