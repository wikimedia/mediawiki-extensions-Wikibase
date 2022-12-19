<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use DataValues\TimeValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\HtmlTimeFormatter;
use Wikibase\Lib\Formatters\ShowCalendarModelDecider;

/**
 * @covers \Wikibase\Lib\Formatters\HtmlTimeFormatter
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

	/**
	 * @return HtmlTimeFormatter
	 */
	private function getFormatter() {
		$options = new FormatterOptions();
		$options->setOption( ValueFormatter::OPT_LANG, 'qqx' );

		$dateTimeFormatter = $this->createMock( ValueFormatter::class );

		$dateTimeFormatter->method( 'format' )
			->willReturn( 'MOCKDATE' );

		return new HtmlTimeFormatter( $options, $dateTimeFormatter, new ShowCalendarModelDecider() );
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
			'a julian day in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'MOCKDATE',
			],
			'a gregorian day in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-gregorian)</sup>',
			],
			'a julian day in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$julian,
				'MOCKDATE<sup class="wb-calendar-name">(wikibase-time-calendar-julian)</sup>',
			],
			'a gregorian day in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$gregorian,
				'MOCKDATE',
			],
			'HTML entities' => [
				'<a>injection</a>',
				TimeValue::PRECISION_DAY,
				'<a>injection</a>',
				'MOCKDATE<sup class="wb-calendar-name">&lt;a&gt;injection&lt;/a&gt;</sup>',
			],
		];

		$testCases = [];

		foreach ( $tests as $name => $data ) {
			list( $timestamp, $precision, $calendarModel, $pattern ) = $data;

			$testCases[$name] = [
				$this->getTimeValue( $timestamp, $precision, $calendarModel ),
				$pattern,
			];
		}
		return $testCases;
	}

	public function testEscapesHtml(): void {
		$dateTimeFormatter = $this->createMock( ValueFormatter::class );
		$dateTimeFormatter->method( 'format' )
			->willReturn( '<script>' );
		$formatter = new HtmlTimeFormatter( null, $dateTimeFormatter, new ShowCalendarModelDecider() );

		$value = $this->getTimeValue( 'MOCKTIME', TimeValue::PRECISION_DAY, 'calendar' );
		$this->assertSame( '&lt;script&gt;<sup class="wb-calendar-name">calendar</sup>',
			$formatter->format( $value ) );
	}

	/**
	 * @dataProvider invalidValueProvider
	 */
	public function testGivenInvalidValue_formatThrowsException( $value ) {
		$formatter = $this->getFormatter();

		$this->expectException( InvalidArgumentException::class );
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
