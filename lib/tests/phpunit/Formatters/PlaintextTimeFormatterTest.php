<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\TimeValue;
use MediaWikiIntegrationTestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\PlaintextTimeFormatter;
use Wikibase\Lib\Formatters\ShowCalendarModelDecider;

/**
 * @covers \Wikibase\Lib\Formatters\PlaintextTimeFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PlaintextTimeFormatterTest extends MediaWikiIntegrationTestCase {

	public function testFormatWithoutCalendar(): void {
		$value = $this->newTimeValue( '+2022-11-22T00:00:00Z', TimeValue::CALENDAR_GREGORIAN );
		$dateTimeFormatter = $this->createMock( ValueFormatter::class );
		$dateTimeFormatter->expects( $this->once() )
			->method( 'format' )
			->with( $value )
			->willReturn( '22 November 2022' );
		$options = new FormatterOptions( [ ValueFormatter::OPT_LANG => 'qqx' ] );
		$decider = $this->createMock( ShowCalendarModelDecider::class );
		$decider->expects( $this->once() )
			->method( 'showCalendarModel' )
			->with( $value, $options )
			->willReturn( false );

		$formatter = new PlaintextTimeFormatter( $options, $dateTimeFormatter, $decider );
		$formatted = $formatter->format( $value );

		$this->assertSame( '22 November 2022', $formatted );
	}

	public function testFormatWithKnownCalendar(): void {
		$value = $this->newTimeValue( '+2022-11-22T00:00:00Z', TimeValue::CALENDAR_GREGORIAN );
		$dateTimeFormatter = $this->createMock( ValueFormatter::class );
		$dateTimeFormatter->expects( $this->once() )
			->method( 'format' )
			->with( $value )
			->willReturn( '22 November 2022' );
		$options = new FormatterOptions( [ ValueFormatter::OPT_LANG => 'qqx' ] );
		$decider = $this->createMock( ShowCalendarModelDecider::class );
		$decider->expects( $this->once() )
			->method( 'showCalendarModel' )
			->with( $value, $options )
			->willReturn( true );

		$formatter = new PlaintextTimeFormatter( $options, $dateTimeFormatter, $decider );
		$formatted = $formatter->format( $value );

		$this->assertSame( '(wikibase-time-with-calendar: 22 November 2022,'
			. ' (wikibase-time-calendar-gregorian))', $formatted );
	}

	public function testFormatWithUnknownCalendar(): void {
		$value = $this->newTimeValue( '+2022-11-22T00:00:00Z', 'Q12345' );
		$dateTimeFormatter = $this->createMock( ValueFormatter::class );
		$dateTimeFormatter->expects( $this->once() )
			->method( 'format' )
			->with( $value )
			->willReturn( '22 November 2022' );
		$options = new FormatterOptions( [ ValueFormatter::OPT_LANG => 'qqx' ] );
		$decider = $this->createMock( ShowCalendarModelDecider::class );
		$decider->expects( $this->once() )
			->method( 'showCalendarModel' )
			->with( $value, $options )
			->willReturn( true );

		$formatter = new PlaintextTimeFormatter( $options, $dateTimeFormatter, $decider );
		$formatted = $formatter->format( $value );

		$this->assertSame( '(wikibase-time-with-calendar: 22 November 2022,'
			. ' Q12345)', $formatted );
	}

	private function newTimeValue( string $timestamp, string $calendarModel ): TimeValue {
		return new TimeValue( $timestamp, 0, 0, 0, TimeValue::PRECISION_DAY, $calendarModel );
	}

}
