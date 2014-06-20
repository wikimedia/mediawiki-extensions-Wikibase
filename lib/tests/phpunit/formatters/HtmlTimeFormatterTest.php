<?php

namespace Wikibase\Lib\Test;

use DataValues\TimeValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeFormatter;
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
 */
class HtmlTimeFormatterTest extends \PHPUnit_Framework_TestCase {

	private function getMockFormatter() {
		$mock = $this->getMockBuilder( '\ValueFormatters\ValueFormatter' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->once() )
			->method( 'format' )
			->will( $this->returnValue( 'MOCKDATE' ) );
		return $mock;
	}

	/**
	 * @dataProvider timeFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new HtmlTimeFormatter( $options, $this->getMockFormatter() );

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
				new FormatterOptions(),
				'/^MOCKDATE <sup class="wb-calendar-name">Gregorian<\/sup>$/'
			),
			'a julian day in 1920' => array(
				new TimeValue( '+1920-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_JULIAN ),
				new FormatterOptions(),
				'/^MOCKDATE <sup class="wb-calendar-name">Julian<\/sup>$/'
			),
			'a month in 1920' => array(
				new TimeValue( '+1920-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_MONTH,
					TimeFormatter::CALENDAR_GREGORIAN ),
				new FormatterOptions(),
				'/^MOCKDATE$/'
			),
			'a gregorian day in 1520' => array(
				new TimeValue( '+1520-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				new FormatterOptions(),
				'/^MOCKDATE <sup class="wb-calendar-name">Gregorian<\/sup>$/'
			),
			'a julian day in 1980' => array(
				new TimeValue( '+1980-05-01T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_JULIAN ),
				new FormatterOptions(),
				'/^MOCKDATE <sup class="wb-calendar-name">Julian<\/sup>$/'
			),
			'2014-10-10' => array(
				new TimeValue( '+2014-10-10T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				new FormatterOptions(),
				'/^MOCKDATE$/'
			),
			'2014-10-10 with leading zeros' => array(
				new TimeValue( '+00000002014-10-10T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				new FormatterOptions(),
				'/^MOCKDATE$/'
			),
			'massive year' => array(
				new TimeValue( '+00123452014-10-10T00:00:00Z',
					1 * 60 * 60, 0, 0,
					TimeValue::PRECISION_DAY,
					TimeFormatter::CALENDAR_GREGORIAN ),
				new FormatterOptions(),
				'/^MOCKDATE$/'
			),
		);
	}
}
