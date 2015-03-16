<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use DataValues\TimeValue;
use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
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
	 * @return HtmlTimeFormatter
	 */
	private function getFormatter() {
		$options = new FormatterOptions();
		$options->setOption( ValueFormatter::OPT_LANG, 'qqx' );

		$dateTimeFormatter = $this->getMock( 'ValueFormatters\ValueFormatter' );

		$dateTimeFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'MOCKDATE' ) );

		return new HtmlTimeFormatter( $options, $dateTimeFormatter );
	}

	/**
	 * @param string $time
	 * @param int $precision
	 * @param string $calendarModel
	 *
	 * @return TimeValue
	 */
	private function getTimeValue( $time, $precision, $calendarModel ) {
		$value = $this->getMockBuilder( 'DataValues\TimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$value->expects( $this->any() )
			->method( 'getTime' )
			->will( $this->returnValue( $time ) );
		$value->expects( $this->any() )
			->method( 'getPrecision' )
			->will( $this->returnValue( $precision ) );
		$value->expects( $this->any() )
			->method( 'getCalendarModel' )
			->will( $this->returnValue( $calendarModel ) );

		return $value;
	}

	/**
	 * @dataProvider timeFormatProvider
	 */
	public function testFormat( TimeValue $value, $pattern ) {
		$formatter = $this->getFormatter();

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function timeFormatProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$julian = 'http://www.wikidata.org/entity/Q1985786';

		$tests = array(
			'Gregorian day in 1920' => array(
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE$/'
			),
			'Julian day in 1920' => array(
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-julian\)<\/sup>$/'
			),
			'Gregorian month in 1920' => array(
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_MONTH,
				$gregorian,
				'/^MOCKDATE$/'
			),
			'Gregorian day in 1520' => array(
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-gregorian\)<\/sup>$/'
			),
			'Gregorian month in 1520' => array(
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_MONTH,
				$gregorian,
				'/^MOCKDATE$/'
			),
			'Julian day in 1520' => array(
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-julian\)<\/sup>$/'
			),
			'Julian day in 1980' => array(
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-julian\)<\/sup>$/'
			),
			'2014' => array(
				'+2014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE$/'
			),
			'2014 with leading zeros' => array(
				'+00000002014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE$/'
			),
			'Massive year' => array(
				'+00123452014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE$/'
			),
			'Negative' => array(
				'-1-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-gregorian\)<\/sup>$/'
			),
			'32-bit integer overflow' => array(
				'-2147483649-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-gregorian\)<\/sup>$/'
			),
			'Unknown calendar model' => array(
				'+2015-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				'Stardate',
				'/^MOCKDATE<sup class="wb-calendar-name">Stardate<\/sup>$/'
			),
			'Optional sign' => array(
				'2015-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE$/'
			),
			'Unsupported time' => array(
				'MOCKTIME',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'/^MOCKDATE<sup class="wb-calendar-name">\(valueview-expert-timevalue-calendar-gregorian\)<\/sup>$/'
			),
			'HTML entities' => array(
				'<a>injection</a>',
				'<a>injection</a>',
				'<a>injection</a>',
				'/^MOCKDATE<sup class="wb-calendar-name">&lt;a&gt;injection&lt;\/a&gt;<\/sup>$/'
			),
		);

		$testCases = array();

		foreach ( $tests as $name => $data ) {
			list( $time, $precision, $calendarModel, $pattern ) = $data;

			$testCases[$name] = array(
				$this->getTimeValue( $time, $precision, $calendarModel ),
				$pattern
			);
		}
		return $testCases;
	}

	/**
	 * @dataProvider invalidValueProvider
	 */
	public function testGivenInvalidValue_formatThrowsException( $value ) {
		$formatter = $this->getFormatter();

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}

	public function invalidValueProvider() {
		return array(
			array( null ),
			array( false ),
			array( 1 ),
			array( 0.1 ),
			array( 'string' ),
			array( new StringValue( 'string' ) ),
		);
	}

}
