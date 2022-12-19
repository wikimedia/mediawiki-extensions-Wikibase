<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\TimeValue;
use InvalidArgumentException;
use MediaWikiTestCaseTrait;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\TimeDetailsFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\TimeDetailsFormatter
 * @uses DataValues\TimeValue
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class TimeDetailsFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	/**
	 * @param string $formattedHeading
	 *
	 * @return TimeDetailsFormatter
	 */
	private function getFormatter( $formattedHeading = '' ) {
		$options = new FormatterOptions();
		$options->setOption( ValueFormatter::OPT_LANG, 'qqx' );

		$timeFormatter = $this->createMock( ValueFormatter::class );
		$timeFormatter->method( 'format' )
			->willReturn( $formattedHeading );

		return new TimeDetailsFormatter( $options, $timeFormatter );
	}

	/**
	 * @param string $timestamp
	 * @param int|string $timezone
	 * @param int|string $before
	 * @param int|string $after
	 * @param int|string $precision
	 * @param string $calendarModel
	 *
	 * @return TimeValue
	 */
	private function getTimeValue(
		$timestamp = '<a>time</a>',
		$timezone = '<a>timezone</a>',
		$before = '<a>before</a>',
		$after = '<a>after</a>',
		$precision = '<a>precision</a>',
		$calendarModel = '<a>calendarmodel</a>'
	) {
		$value = new TimeValue( '+1-00-00T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR, $calendarModel );

		$class = new \ReflectionClass( TimeValue::class );

		$timestampProperty = $class->getProperty( 'timestamp' );
		$timestampProperty->setAccessible( true );
		$timestampProperty->setValue( $value, $timestamp );

		$timezoneProperty = $class->getProperty( 'timezone' );
		$timezoneProperty->setAccessible( true );
		$timezoneProperty->setValue( $value, $timezone );

		$beforeProperty = $class->getProperty( 'before' );
		$beforeProperty->setAccessible( true );
		$beforeProperty->setValue( $value, $before );

		$afterProperty = $class->getProperty( 'after' );
		$afterProperty->setAccessible( true );
		$afterProperty->setValue( $value, $after );

		$precisionProperty = $class->getProperty( 'precision' );
		$precisionProperty->setAccessible( true );
		$precisionProperty->setValue( $value, $precision );

		return $value;
	}

	/**
	 * @dataProvider quantityFormatProvider
	 * @param TimeValue $value
	 * @param string $pattern
	 */
	public function testFormat( TimeValue $value, $pattern ) {
		$formatter = $this->getFormatter( '<a>HTML</a>' );

		$html = $formatter->format( $value );
		$this->assertMatchesRegularExpression( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$julian = 'http://www.wikidata.org/entity/Q1985786';
		$day = TimeValue::PRECISION_DAY;

		return [
			'Basic test' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 60, 0, 1, TimeValue::PRECISION_MONTH, $gregorian ),
				'@' . implode( '.*',
					[
						'<b[^<>]*><a>HTML</a></b>',
						'<td[^<>]*>\+0*2001-01-01T00:00:00Z</td>',
						'<td[^<>]*>\+01:00</td>',
						'<td[^<>]*>\(valueview-expert-timevalue-calendar-gregorian\)</td>',
						'<td[^<>]*>\(months: 1\)</td>',
						'<td[^<>]*>0</td>',
						'<td[^<>]*>\(months: 1\)</td>',
					]
				) . '@s',
			],
			'3 digit year' => [
				new TimeValue( '+999-01-01T00:00:00Z', 0, 0, 0, $day, $gregorian ),
				'@.*<td[^<>]*isotime">\+0999-01-01T00:00:00Z</td>.*@s',
			],
			'Negative, padded year' => [
				new TimeValue( '-099999-01-01T00:00:00Z', 0, 0, 0, $day, $gregorian ),
				'@.*<td[^<>]*isotime">\xE2\x88\x9299999-01-01T00:00:00Z</td>.*@s',
			],
			'Optional Z' => [
				$this->getTimeValue( '-099999-01-01T00:00:00' ),
				'@.*<td[^<>]*isotime">\xE2\x88\x9299999-01-01T00:00:00</td>.*@s',
			],
			'Optional sign' => [
				$this->getTimeValue( '099999-01-01T00:00:00Z' ),
				'@.*<td[^<>]*isotime">\+99999-01-01T00:00:00Z</td>.*@s',
			],
			'Julian' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, $day, $julian ),
				'@.*<td[^<>]*calendar">\(valueview-expert-timevalue-calendar-julian\)</td>.*@s',
			],
			'Non-standard calendar model' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, $day, 'Stardate' ),
				'@.*<td[^<>]*calendar">Stardate</td>.*@s',
			],
			'Negative time zone' => [
				new TimeValue( '+2001-01-01T00:00:00Z', -179, 0, 0, $day, $gregorian ),
				'@.*<td[^<>]*timezone">\xE2\x88\x9202:59</td>.*@s',
			],
			'Seconds precision' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_SECOND, $gregorian ),
				'@.*<td[^<>]*precision">\(seconds: 1\)</td>.*@s',
			],
			'10 years precision' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR10, $gregorian ),
				'@.*<td[^<>]*precision">\(years: 10\)</td>.*@s',
			],
			'Max. precision' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_YEAR1G, $gregorian ),
				'@.*<td[^<>]*precision">\(years: 1000000000\)</td>.*@s',
			],
			'Before' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 2, 0, TimeValue::PRECISION_YEAR, $gregorian ),
				'@.*<td[^<>]*before">\(years: 2\)</td>.*@s',
			],
			'After in years' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 5, TimeValue::PRECISION_YEAR10, $gregorian ),
				'@.*<td[^<>]*after">\(years: 50\)</td>.*@s',
			],
			'After in days' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 125, $day, $gregorian ),
				'@.*<td[^<>]*after">\(days: 125\)</td>.*@s',
			],
			'Extreme range' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 1000000, 1000000, $day, $gregorian ),
				'@<td[^<>]*before">\(days: 1000000\)</td>.*<td[^<>]*after">\(days: 1000000\)</td>@s',
			],
			'Zero range' => [
				new TimeValue( '+2001-01-01T00:00:00Z', 0, 0, 0, $day, $gregorian ),
				'@<td[^<>]*before">0</td>.*<td[^<>]*after">0</td>@s',
			],
		];
	}

	public function testFormatError() {
		$formatter = $this->getFormatter();
		$value = new NumberValue( 23 );

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	public function testGivenInvalidTimeValue_formatDoesNotThrowException() {
		$formatter = $this->getFormatter();
		$value = $this->getTimeValue();

		$html = $formatter->format( $value );
		$this->assertIsString( $html );
	}

	public function testGivenInvalidTimeValue_formatDoesNotAllowHtmlInjection() {
		$formatter = $this->getFormatter();
		$value = $this->getTimeValue();

		$html = $formatter->format( $value );
		$this->assertStringNotContainsString( '<a>', $html, 'Should not be unescaped' );
		$this->assertStringContainsString( '&lt;', $html, 'Should be escaped' );
		$this->assertStringNotContainsString( '&amp;', $html, 'Should not be double escape' );
	}

	public function testGivenInvalidTimeValue_formatEchoesTimeValueFields() {
		$formatter = $this->getFormatter();
		$value = $this->getTimeValue();

		$html = $formatter->format( $value );
		$this->assertStringContainsString( '>&lt;a&gt;time&lt;/a&gt;<', $html );
		$this->assertStringContainsString( '>&lt;a&gt;timezone&lt;/a&gt;<', $html );
		$this->assertStringContainsString( '>&lt;a&gt;before&lt;/a&gt;<', $html );
		$this->assertStringContainsString( '>&lt;a&gt;after&lt;/a&gt;<', $html );
		$this->assertStringContainsString( '>&lt;a&gt;precision&lt;/a&gt;<', $html );
		$this->assertStringContainsString( '>&lt;a&gt;calendarmodel&lt;/a&gt;<', $html );
	}

	public function testGivenValidTimeValueWithInvalidPrecision_formatEchoesTimeValueFields() {
		$formatter = $this->getFormatter();
		$value = $this->getTimeValue( '+2001-01-01T00:00:00Z', 0, 1, 1, 'precision' );

		$html = $formatter->format( $value );
		$this->assertSame( 1, substr_count( $html, '>precision<' ), 'precision' );
		$this->assertSame( 2, substr_count( $html, '>1<' ), 'before' );
	}

	public function testGivenValidTimeValueWithInvalidBeforeAndAfter_formatEchoesTimeValueFields() {
		$formatter = $this->getFormatter();
		$value = $this->getTimeValue( '+2001-01-01T00:00:00Z', 0, 'before', 'after', TimeValue::PRECISION_DAY );

		$html = $formatter->format( $value );
		$this->assertSame( 1, substr_count( $html, '>11<' ), 'precision' );
		$this->assertSame( 1, substr_count( $html, '>before<' ), 'before' );
		$this->assertSame( 1, substr_count( $html, '>after<' ), 'after' );
	}

}
