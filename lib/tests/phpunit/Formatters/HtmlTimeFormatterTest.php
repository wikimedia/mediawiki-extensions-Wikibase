<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use DataValues\TimeValue;
use InvalidArgumentException;
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
 * @license GPL-2.0+
 * @author Addshore
 * @author Thiemo Mättig
 */
class HtmlTimeFormatterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return HtmlTimeFormatter
	 */
	private function getFormatter() {
		$options = new FormatterOptions();
		$options->setOption( ValueFormatter::OPT_LANG, 'qqx' );

		$dateTimeFormatter = $this->getMock( ValueFormatter::class );

		$dateTimeFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'MOCKDATE' ) );

		return new HtmlTimeFormatter( $options, $dateTimeFormatter );
	}

	/**
	 * @param string $timestamp
	 * @param int $precision
	 * @param string $calendarModel
	 *
	 * @return TimeValue
	 */
	private function getTimeValue( $timestamp, $precision, $calendarModel ) {
		$value = new TimeValue( '+1-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $calendarModel );

		$class = new \ReflectionClass( TimeValue::class );

		$timestampProperty = $class->getProperty( 'timestamp' );
		$timestampProperty->setAccessible( true );
		$timestampProperty->setValue( $value, $timestamp );

		$precisionProperty = $class->getProperty( 'precision' );
		$precisionProperty->setAccessible( true );
		$precisionProperty->setValue( $value, $precision );

		return $value;
	}

	/**
	 * @dataProvider timeFormatProvider
	 */
	public function testFormat( TimeValue $value, $expected ) {
		$formatter = $this->getFormatter();
		$this->assertSame( $expected, $formatter->format( $value ) );
	}

	public function timeFormatProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$julian = 'http://www.wikidata.org/entity/Q1985786';

		$tests = array(
			'a gregorian day in 1520' => array(
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-gregorian)</sup>'
			),
			'a gregorian month in 1520' => array(
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_MONTH,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-gregorian)</sup>'
			),
			'a julian day in 1520' => array(
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'MOCKDATE'
			),
			'a gregorian day in 1920' => array(
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-gregorian)</sup>'
			),
			'a julian day in 1920' => array(
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-julian)</sup>'
			),
			'a julian day in 1980' => array(
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-julian)</sup>'
			),
			'a gregorian day in 1980' => array(
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			),

			'a gregorian year in -1000000' => array(
				'-1000000-00-00T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-gregorian)</sup>'
			),
			'a julian year in -1000000' => array(
				'-1000000-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$julian,
				'MOCKDATE'
			),
			'a gregorian year in 1520' => array(
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-gregorian)</sup>'
			),
			'a julian year in 1520' => array(
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$julian,
				'MOCKDATE'
			),
			'a gregorian year in 1920' => array(
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$gregorian,
				'MOCKDATE'
			),
			'a julian year in 1920' => array(
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-julian)</sup>'
			),
			'a julian year in 1980' => array(
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-julian)</sup>'
			),
			'do not enforce calendar model on rough precisions' => array(
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR10,
				$julian,
				'MOCKDATE'
			),
			'a gregorian year in 1980' => array(
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$gregorian,
				'MOCKDATE'
			),

			'a month in 1980' => array(
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_MONTH,
				$gregorian,
				'MOCKDATE'
			),

			'14th century' => array(
				'+1300-00-00T00:00:00Z',
				TimeValue::PRECISION_YEAR100,
				$julian,
				'MOCKDATE'
			),

			'2014-10-10' => array(
				'+2014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			),
			'2014-10-10 with leading zeros' => array(
				'+00000002014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			),
			'massive year' => array(
				'+00123452014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			),
			'negative' => array(
				'-1-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-gregorian)</sup>'
			),
			'32-bit integer overflow' => array(
				'-2147483649-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-gregorian)</sup>'
			),
			'unknown calendar model' => array(
				'+2015-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				'Stardate',
				'MOCKDATE<sup class="wb-calendar-name">Stardate</sup>'
			),
			'optional sign' => array(
				'2015-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			),
			'unsupported time' => array(
				'MOCKTIME',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(valueview-expert-timevalue-calendar-gregorian)</sup>'
			),
			'HTML entities' => array(
				'<a>injection</a>',
				TimeValue::PRECISION_DAY,
				'<a>injection</a>',
				'MOCKDATE<sup class="wb-calendar-name">&lt;a&gt;injection&lt;/a&gt;</sup>'
			),
		);

		$testCases = array();

		foreach ( $tests as $name => $data ) {
			list( $timestamp, $precision, $calendarModel, $pattern ) = $data;

			$testCases[$name] = array(
				$this->getTimeValue( $timestamp, $precision, $calendarModel ),
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

		$this->setExpectedException( InvalidArgumentException::class );
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
