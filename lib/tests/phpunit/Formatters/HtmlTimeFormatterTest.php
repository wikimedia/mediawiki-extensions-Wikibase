<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use DataValues\TimeValue;
use InvalidArgumentException;
use PHPUnit4And6Compat;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\HtmlTimeFormatter;

/**
 * @covers Wikibase\Lib\HtmlTimeFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Thiemo Kreuz
 */
class HtmlTimeFormatterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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

		$tests = [
			'a gregorian day in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>'
			],
			'a gregorian month in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_MONTH,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>'
			],
			'a julian day in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'MOCKDATE'
			],
			'a gregorian day in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>'
			],
			'a julian day in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-julian)</sup>'
			],
			'a julian day in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-julian)</sup>'
			],
			'a gregorian day in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			],

			'a gregorian year in -1000000' => [
				'-1000000-00-00T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>'
			],
			'a julian year in -1000000' => [
				'-1000000-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$julian,
				'MOCKDATE'
			],
			'a gregorian year in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>'
			],
			'a julian year in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$julian,
				'MOCKDATE'
			],
			'a gregorian year in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$gregorian,
				'MOCKDATE'
			],
			'a julian year in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-julian)</sup>'
			],
			'a julian year in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-julian)</sup>'
			],
			'do not enforce calendar model on rough precisions' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR10,
				$julian,
				'MOCKDATE'
			],
			'a gregorian year in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				$gregorian,
				'MOCKDATE'
			],

			'a month in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_MONTH,
				$gregorian,
				'MOCKDATE'
			],

			'14th century' => [
				'+1300-00-00T00:00:00Z',
				TimeValue::PRECISION_YEAR100,
				$julian,
				'MOCKDATE'
			],

			'2014-10-10' => [
				'+2014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			],
			'2014-10-10 with leading zeros' => [
				'+00000002014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			],
			'massive year' => [
				'+00123452014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			],
			'negative' => [
				'-1-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>'
			],
			'32-bit integer overflow' => [
				'-2147483649-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>'
			],
			'unknown calendar model' => [
				'+2015-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				'Stardate',
				'MOCKDATE<sup class="wb-calendar-name">Stardate</sup>'
			],
			'optional sign' => [
				'2015-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE'
			],
			'unsupported time' => [
				'MOCKTIME',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>'
			],
			'HTML entities' => [
				'<a>injection</a>',
				TimeValue::PRECISION_DAY,
				'<a>injection</a>',
				'MOCKDATE<sup class="wb-calendar-name">&lt;a&gt;injection&lt;/a&gt;</sup>'
			],
		];

		$testCases = [];

		foreach ( $tests as $name => $data ) {
			list( $timestamp, $precision, $calendarModel, $pattern ) = $data;

			$testCases[$name] = [
				$this->getTimeValue( $timestamp, $precision, $calendarModel ),
				$pattern
			];
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
		return [
			[ null ],
			[ false ],
			[ 1 ],
			[ 0.1 ],
			[ 'string' ],
			[ new StringValue( 'string' ) ],
		];
	}

}
