<?php

namespace Wikibase\Lib\Test;

use DataValues\TimeValue;
use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\HtmlTimeFormatter;

/**
 * @covers Wikibase\Lib\HtmlTimeFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class HtmlTimeFormatterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return ValueFormatter
	 */
	private function getDateTimeFormatter() {
		$mock = $this->getMockBuilder( 'ValueFormatters\ValueFormatter' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->once() )
			->method( 'format' )
			->will( $this->returnValue( 'MOCKDATE' ) );
		return $mock;
	}

	/**
	 * @dataProvider timeFormatProvider
	 * @param TimeValue $value
	 * @param string $pattern
	 */
	public function testFormat( TimeValue $value, $pattern ) {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'qqx',
		) );
		$formatter = new HtmlTimeFormatter( $options, $this->getDateTimeFormatter() );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function timeFormatProvider() {
		return array(
			'a gregorian day in 1920' => array(
				new TimeValue( '+1920-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^MOCKDATE$/'
			),
			'a julian day in 1920' => array(
				new TimeValue( '+1920-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_JULIAN ),
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-julian\)<\/sup>$/'
			),
			'a month in 1920' => array(
				new TimeValue( '+1920-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_MONTH,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^MOCKDATE$/'
			),
			'a gregorian day in 1520' => array(
				new TimeValue( '+1520-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-gregorian\)<\/sup>$/'
			),
			'a julian day in 1520' => array(
				new TimeValue( '+1520-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_JULIAN ),
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-julian\)<\/sup>$/'
			),
			'a julian day in 1980' => array(
				new TimeValue( '+1980-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_JULIAN ),
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-julian\)<\/sup>$/'
			),
			'2014-10-10' => array(
				new TimeValue( '+2014-10-10T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^MOCKDATE$/'
			),
			'2014-10-10 with leading zeros' => array(
				new TimeValue( '+00000002014-10-10T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^MOCKDATE$/'
			),
			'massive year' => array(
				new TimeValue( '+00123452014-10-10T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^MOCKDATE$/'
			),
			'negative' => array(
				new TimeValue( '-1-01-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-gregorian\)<\/sup>$/'
			),
			'32-bit integer overflow' => array(
				new TimeValue( '-2147483649-01-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-gregorian\)<\/sup>$/'
			),
			'unknown calendar model' => array(
				new TimeValue( '+2100-01-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					'Stardate' ),
				'/^MOCKDATE<sup class="wb-calendar-name">Stardate<\/sup>$/'
			),
			'HTML entities' => array(
				new TimeValue( '+2100-01-01T00:00:00Z',
					0, 0, 0,
					TimeValue::PRECISION_DAY,
					'<a>injection</a>' ),
				'/^MOCKDATE<sup class="wb-calendar-name">&lt;a&gt;injection&lt;\/a&gt;<\/sup>$/'
			),
		);
	}

}
