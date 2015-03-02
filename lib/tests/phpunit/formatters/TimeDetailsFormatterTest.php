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
	 * @param string $time
	 *
	 * @return TimeValue
	 */
	private function getTimeValue( $time = '<a>injection</a>' ) {
		$value = $this->getMockBuilder( 'DataValues\TimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$value->expects( $this->any() )
			->method( 'getTime' )
			->will( $this->returnValue( $time ) );
		$value->expects( $this->any() )
			->method( 'getCalendarModel' )
			->will( $this->returnValue( '<a>injection</a>' ) );
		$value->expects( $this->any() )
			->method( 'getBefore' )
			->will( $this->returnValue( '<a>injection</a>' ) );
		$value->expects( $this->any() )
			->method( 'getAfter' )
			->will( $this->returnValue( '<a>injection</a>' ) );
		$value->expects( $this->any() )
			->method( 'getPrecision' )
			->will( $this->returnValue( '<a>injection</a>' ) );
		$value->expects( $this->any() )
			->method( 'getTimezone' )
			->will( $this->returnValue( '<a>injection</a>' ) );

		return $value;
	}

	/**
	 * @dataProvider quantityFormatProvider
	 * @param TimeValue $value
	 * @param string $pattern
	 */
	public function testFormat( TimeValue $value, $pattern ) {
		$formatter = new TimeDetailsFormatter( new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'qqx',
		) ) );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$gregorian = TimeFormatter::CALENDAR_GREGORIAN;
		$day = TimeValue::PRECISION_DAY;

		return array(
			'Basic test' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 60, 0, 1, TimeValue::PRECISION_MONTH, $gregorian ),
				'@' . implode( '.*',
					array(
						'<h4[^<>]*>[^<>]*2001[^<>]*</h4>',
						'<td[^<>]*>\+0*2001-01-01T00:00:00</td>',
						'<td[^<>]*>\+01:00</td>',
						'<td[^<>]*>\(valueview-expert-timevalue-calendar-gregorian\)</td>',
						'<td[^<>]*>\(months: 1\)</td>',
						'<td[^<>]*>\(months: 0\)</td>',
						'<td[^<>]*>\(months: 1\)</td>',
					)
				) . '@s'
			),
			'3 digit year' => array(
				new TimeValue( '+999-01-01T00:00:00Z', 0, 0, 0, $day, $gregorian ),
				'@.*<td[^<>]*isotime">\+0999-01-01T00:00:00</td>.*@s'
			),
			'Negative, padded year' => array(
				new TimeValue( '-099999-01-01T00:00:00Z', 0, 0, 0, $day, $gregorian ),
				'@.*<td[^<>]*isotime">\xE2\x88\x9299999-01-01T00:00:00</td>.*@s'
			),
			'Optional Z' => array(
				$this->getTimeValue( '-099999-01-01T00:00:00' ),
				'@.*<td[^<>]*isotime">\xE2\x88\x9299999-01-01T00:00:00</td>.*@s'
			),
			'Optional sign' => array(
				$this->getTimeValue( '099999-01-01T00:00:00Z' ),
				'@.*<td[^<>]*isotime">\+99999-01-01T00:00:00</td>.*@s'
			),
			'Julian' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, $day, TimeFormatter::CALENDAR_JULIAN ),
				'@.*<td[^<>]*calendar">\(valueview-expert-timevalue-calendar-julian\)</td>.*@s'
			),
			'Non-standard calendar model' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, $day, 'Stardate' ),
				'@.*<td[^<>]*calendar">Stardate</td>.*@s'
			),
			'Negative time zone' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', -179, 0, 0, $day, $gregorian ),
				'@.*<td[^<>]*timezone">\xE2\x88\x9202:59</td>.*@s'
			),
			'Seconds precision' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_SECOND, $gregorian ),
				'@.*<td[^<>]*precision">\(seconds: 1\)</td>.*@s'
			),
			'10 years precision' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_10a, $gregorian ),
				'@.*<td[^<>]*precision">\(years: 10\)</td>.*@s'
			),
			'Max. precision' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_Ga, $gregorian ),
				'@.*<td[^<>]*precision">\(years: 1000000000\)</td>.*@s'
			),
			'Before' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 2, 0, TimeValue::PRECISION_YEAR, $gregorian ),
				'@.*<td[^<>]*before">\(years: 2\)</td>.*@s'
			),
			'After in years' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 5, TimeValue::PRECISION_10a, $gregorian ),
				'@.*<td[^<>]*after">\(years: 50\)</td>.*@s'
			),
			'After in days' => array(
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 125, $day, $gregorian ),
				'@.*<td[^<>]*after">\(days: 125\)</td>.*@s'
			),
		);
	}

	public function testFormatError() {
		$formatter = new TimeDetailsFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}

	public function testGivenInvalidTimeValue_formatDoesNotAllowHtmlInjection() {
		$formatter = new TimeDetailsFormatter();
		$value = $this->getTimeValue();

		$html = $formatter->format( $value );
		$this->assertContains( 'injection', $html, 'Should be in the output' );
		$this->assertEquals( 6, substr_count( $html, 'injection' ) );
		$this->assertNotContains( '<a>', $html, 'Should not be unescaped' );
		$this->assertContains( '&lt;', $html, 'Should be escaped' );
		$this->assertNotContains( '&amp;', $html, 'Should not be double escape' );
	}

}
