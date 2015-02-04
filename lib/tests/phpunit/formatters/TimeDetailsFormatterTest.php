<?php

namespace Wikibase\Lib\Test;

use DataValues\NumberValue;
use DataValues\TimeValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\TimeDetailsFormatter;

/**
 * @covers Wikibase\Lib\TimeDetailsFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class TimeDetailsFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider quantityFormatProvider
	 * @param TimeValue $value
	 * @param string $pattern
	 */
	public function testFormat( TimeValue $value, $pattern ) {
		$formatter = new TimeDetailsFormatter( new FormatterOptions() );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$gregorian = TimeFormatter::CALENDAR_GREGORIAN;
		$day = TimeValue::PRECISION_DAY;

		return array(
			array(
				new TimeValue( '+2001-01-01T00:00:00Z', 60, 0, 1, TimeValue::PRECISION_MONTH, $gregorian ),
				'@' . implode( '.*',
					array(
						'<h4[^<>]*>[^<>]*2001[^<>]*</h4>',
						'<td[^<>]*>\+0*2001-01-01T00:00:00Z</td>',
						'<td[^<>]*>\+01:00</td>',
						'<td[^<>]*>Gregorian</td>',
						'<td[^<>]*>10</td>',
						'<td[^<>]*>0</td>',
						'<td[^<>]*>1</td>',
					)
				) . '@s'
			),
			array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, $day, 'Stardate' ),
				'@.*<td class="wb-time-calendar">Stardate</td>.*@s'
			),
			array(
				new TimeValue( '+2001-01-01T00:00:00Z', -179, 0, 0, $day, $gregorian ),
				'@.*<td class="wb-time-timezone">\xE2\x88\x9202:59</td>.*@s'
			),
		);
	}

	public function testFormatError() {
		$formatter = new TimeDetailsFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}

}
