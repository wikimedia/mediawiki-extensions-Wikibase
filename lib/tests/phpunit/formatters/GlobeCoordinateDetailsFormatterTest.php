<?php

namespace Wikibase\Lib\Test;

use DataValues\LatLongValue;
use DataValues\NumberValue;
use DataValues\GlobeCoordinateValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\GlobeCoordinateFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\GlobeCoordinateDetailsFormatter;

/**
 * @covers Wikibase\Lib\GlobeCoordinateDetailsFormatter
 *
 * @since 0.5
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
	 *
	 * @covers GlobeCoordinateDetailsFormatterTest::format()
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
						'<dd[^<>]*>[^<>]*50[^<>]*</dd>',
						'<dd[^<>]*>[^<>]*11[^<>]*</dd>',
						'<dd[^<>]*>[^<>]*1[^<>]*</dd>',
						'<dd[^<>]*>[^<>]*.*Q2[^<>]*</dd>',
					)
				) . '@s'
			),
		);
	}

	/**
	 * @covers GlobeCoordinateDetailsFormatterTest::format()
	 */
	public function testFormatError() {
		$formatter = new GlobeCoordinateDetailsFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}
}
