<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\TimeValue;
use MediaWikiIntegrationTestCase;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Formatters\ShowCalendarModelDecider;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Lib\Formatters\ShowCalendarModelDecider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ShowCalendarModelDeciderTest extends MediaWikiIntegrationTestCase {

	/** @dataProvider provideValuesWithDecision */
	public function testShowCalendarModel_auto( TimeValue $value, bool $expected ): void {
		$decider = new ShowCalendarModelDecider();
		$options = new FormatterOptions( [ ShowCalendarModelDecider::OPT_SHOW_CALENDAR => 'auto' ] );
		$this->assertSame( $expected, $decider->showCalendarModel( $value, $options ) );
	}

	/** @dataProvider provideValuesWithDecision */
	public function testShowCalendarModel_true( TimeValue $value ): void {
		$decider = new ShowCalendarModelDecider();
		$options = new FormatterOptions( [ ShowCalendarModelDecider::OPT_SHOW_CALENDAR => true ] );
		$this->assertTrue( $decider->showCalendarModel( $value, $options ) );
	}

	/** @dataProvider provideValuesWithDecision */
	public function testShowCalendarModel_false( TimeValue $value ): void {
		$decider = new ShowCalendarModelDecider();
		$options = new FormatterOptions( [ ShowCalendarModelDecider::OPT_SHOW_CALENDAR => false ] );
		$this->assertFalse( $decider->showCalendarModel( $value, $options ) );
	}

	public function provideValuesWithDecision(): iterable {
		$tests = [
			'a gregorian day in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				true,
			],
			'a gregorian month in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_MONTH,
				TimeValue::CALENDAR_GREGORIAN,
				true,
			],
			'a julian day in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_JULIAN,
				false,
			],
			'a gregorian day in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				true,
			],
			'a julian day in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_JULIAN,
				true,
			],
			'a julian day in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_JULIAN,
				true,
			],
			'a gregorian day in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				false,
			],

			'a gregorian year in -1000000' => [
				'-1000000-00-00T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				TimeValue::CALENDAR_GREGORIAN,
				true,
			],
			'a julian year in -1000000' => [
				'-1000000-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				TimeValue::CALENDAR_JULIAN,
				false,
			],
			'a gregorian year in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				TimeValue::CALENDAR_GREGORIAN,
				true,
			],
			'a julian year in 1520' => [
				'+1520-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				TimeValue::CALENDAR_JULIAN,
				false,
			],
			'a gregorian year in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				TimeValue::CALENDAR_GREGORIAN,
				false,
			],
			'a julian year in 1920' => [
				'+1920-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				TimeValue::CALENDAR_JULIAN,
				true,
			],
			'a julian year in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				TimeValue::CALENDAR_JULIAN,
				true,
			],
			'do not enforce calendar model on rough precisions' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR10,
				TimeValue::CALENDAR_JULIAN,
				false,
			],
			'a gregorian year in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_YEAR,
				TimeValue::CALENDAR_GREGORIAN,
				false,
			],

			'a month in 1980' => [
				'+1980-05-01T00:00:00Z',
				TimeValue::PRECISION_MONTH,
				TimeValue::CALENDAR_GREGORIAN,
				false,
			],

			'14th century' => [
				'+1300-00-00T00:00:00Z',
				TimeValue::PRECISION_YEAR100,
				TimeValue::CALENDAR_JULIAN,
				false,
			],

			'2014-10-10' => [
				'+2014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				false,
			],
			'2014-10-10 with leading zeros' => [
				'+00000002014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				false,
			],
			'massive year' => [
				'+00123452014-10-10T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				false,
			],
			'negative' => [
				'-1-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				true,
			],
			'32-bit integer overflow' => [
				'-2147483649-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				true,
			],
			'unknown calendar model' => [
				'+2015-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				'Stardate',
				true,
			],
			'optional sign' => [
				'2015-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				false,
			],
			'unsupported time' => [
				'MOCKTIME',
				TimeValue::PRECISION_DAY,
				TimeValue::CALENDAR_GREGORIAN,
				true,
			],
		];

		foreach ( $tests as $name => $data ) {
			[ $timestamp, $precision, $calendarModel, $decision ] = $data;

			yield $name => [
				$this->getTimeValue( $timestamp, $precision, $calendarModel ),
				$decision,
			];
		}
	}

	private function getTimeValue( string $timestamp, int $precision, string $calendarModel ): TimeValue {
		$value = new TimeValue( '+1-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $calendarModel );

		$wrapper = TestingAccessWrapper::newFromObject( $value );
		$wrapper->timestamp = $timestamp;
		$wrapper->precision = $precision;

		return $value;
	}

}
