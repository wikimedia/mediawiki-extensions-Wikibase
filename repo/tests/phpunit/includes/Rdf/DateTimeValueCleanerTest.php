<?php

namespace Wikibase\Test\Rdf;

use DataValues\TimeValue;
use Wikibase\Rdf\DateTimeValueCleaner;
use Wikibase\Rdf\JulianDateTimeValueCleaner;

/**
 * @covers Wikibase\Rdf\DateTimeValueCleaner
 * @covers Wikibase\Rdf\JulianDateTimeValueCleaner
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class DateTimeValueCleanerTest extends \PHPUnit_Framework_TestCase {

	public function getDates() {
		$greg = TimeValue::CALENDAR_GREGORIAN;
		$jul = TimeValue::CALENDAR_JULIAN;
		$year1m = TimeValue::PRECISION_YEAR1M;
		$year = TimeValue::PRECISION_YEAR;
		$month = TimeValue::PRECISION_MONTH;

		return [
			// Gregorian
			[ '+00000002014-01-05T12:34:56Z', $greg, '2014-01-05T12:34:56Z' ],
			[ '+00000002014-01-05T12:34:56Z', $greg, '2014-01-01T12:34:56Z', $year ],
			[ '-00000000200-00-00T00:00:00Z', $greg, '-0200-01-01T00:00:00Z' ],
			[ '+00000000200-00-00T00:00:00Z', $greg, '0200-01-01T00:00:00Z' ],
			[ '+00000000200-00-00T00:00:00Z', $greg, '0200-01-01T00:00:00Z', $year ],
			[ '+02000000200-00-00T00:00:00Z', $greg, '2000000200-01-01T00:00:00Z' ],
			[ '+92000000200-05-31T00:00:00Z', $greg, '92000000200-01-01T00:00:00Z', $year1m ],
			[ '+92000000200-05-31T00:00:00Z', $greg, '92000000200-05-31T00:00:00Z' ],
			[ '-02000000200-05-22T00:00:00Z', $greg, '-2000000200-05-22T00:00:00Z' ],
			[ '-02000000200-02-31T00:00:00Z', $greg, '-2000000200-02-29T00:00:00Z' ],
			[ '+00000000200-02-31T00:00:00Z', $greg, '0200-02-28T00:00:00Z' ],
			[ '+00000000204-02-31T00:00:00Z', $greg, '0204-02-29T00:00:00Z' ],
			[ '+00000002204-04-31T00:00:00Z', $greg, '2204-04-30T00:00:00Z' ],
			[ '+00000002204-04-31T00:00:00Z', $greg, '2204-04-01T00:00:00Z', $month ],
			[ '+00000000000-04-31T00:00:00Z', $greg, null ],
			[ '-00000000000-04-31T00:00:00Z', $greg, null ],
			[ '+98765432198765-00-00T00:00:00Z', $greg, '98765432198765-01-01T00:00:00Z', $year ],
			[ '-98765432198765-00-00T00:00:00Z', $greg, '-98765432198765-01-01T00:00:00Z', $year ],
			[ '+8888888888888888-01-01T00:00:00Z', $greg, '8888888888888888-01-01T00:00:00Z' ],
			[ '-8888888888888888-01-01T00:00:00Z', $greg, '-8888888888888888-01-01T00:00:00Z' ],

			// Julian
			[ '+00000002014-01-05T12:34:56Z', $jul, '2014-01-18T12:34:56Z' ],
			[ '-00000002014-01-05T12:34:56Z', $jul, '-2015-12-19T12:34:56Z' ],
			[ '+00000000200-02-31T00:00:00Z', $jul, '0200-03-02T00:00:00Z' ],
			[ '+00000000204-02-31T00:00:00Z', $jul, '0204-03-02T00:00:00Z' ],
			[ '-02000000204-02-31T00:00:00Z', $jul, '-2000000204-01-01T00:00:00Z' ],
			[ '-4713-12-31T00:00:00Z', $jul, '-4713-11-23T00:00:00Z' ],
			[ '-4714-01-02T00:00:00Z', $jul, '-4714-01-01T00:00:00Z' ],
			[ '+98765432198765-00-00T00:00:00Z', $jul, '98765432198765-01-01T00:00:00Z', $year ],
			[ '-98765432198765-00-00T00:00:00Z', $jul, '-98765432198765-01-01T00:00:00Z', $year ],
			[ '+8888888888888888-01-01T00:00:00Z', $jul, '8888888888888888-01-01T00:00:00Z' ],
			[ '-8888888888888888-01-01T00:00:00Z', $jul, '-8888888888888888-01-01T00:00:00Z' ],

			// Neither
			[ '+00000002014-01-05T12:34:56Z', 'http://www.wikidata.org/entity/Q42', null ],
		];
	}

	public function getDatesXSD11() {
		$greg = TimeValue::CALENDAR_GREGORIAN;
		$jul = TimeValue::CALENDAR_JULIAN;
		$year10 = TimeValue::PRECISION_YEAR10;
		$year = TimeValue::PRECISION_YEAR;
		$day = TimeValue::PRECISION_DAY;

		return [
			// Gregorian
			[ '-00000000200-00-00T00:00:00Z', $greg, '-0199-01-01T00:00:00Z' ],
			[ '-02000000200-05-22T00:00:00Z', $greg, '-2000000200-01-01T00:00:00Z', $year10 ],
			[ '-02000000200-02-31T00:00:00Z', $greg, '-2000000200-01-01T00:00:00Z', $year10 ],
			[ '+98765432198765-00-00T00:00:00Z', $greg, '98765432198765-01-01T00:00:00Z', $year ],
			[ '-98765432198765-00-00T00:00:00Z', $greg, '-98765432198764-01-01T00:00:00Z', $year ],
			[ '+8888888888888888-01-01T00:00:00Z', $greg, '8888888888888888-01-01T00:00:00Z' ],
			[ '-8888888888888888-01-01T00:00:00Z', $greg, '-8888888888888887-01-01T00:00:00Z' ],

			// Julian
			[ '-00000002014-01-05T12:34:56Z', $jul, '-2014-12-19T12:34:56Z' ],
			[ '-00000002014-01-05T12:34:56Z', $jul, '-2013-01-01T12:34:56Z', $year ],
			[ '-0100-07-12T00:00:00Z', $jul, '-0099-07-10T00:00:00Z', $day ],
			[ '-4713-12-31T00:00:00Z', $jul, '-4712-11-23T00:00:00Z' ],
			[ '-4714-01-02T00:00:00Z', $jul, '-4713-01-01T00:00:00Z' ],
			[ '+98765432198765-00-00T00:00:00Z', $jul, '98765432198765-01-01T00:00:00Z', $year ],
			[ '-98765432198765-00-00T00:00:00Z', $jul, '-98765432198764-01-01T00:00:00Z', $year ],
			[ '+8888888888888888-01-01T00:00:00Z', $jul, '8888888888888888-01-01T00:00:00Z' ],
			[ '-8888888888888888-01-01T00:00:00Z', $jul, '-8888888888888887-01-01T00:00:00Z' ],
		];
	}

	/**
	 * @dataProvider getDates
	 */
	public function testCleanDate(
		$date,
		$calendar,
		$expected,
		$precision = TimeValue::PRECISION_SECOND
	) {
		$julianCleaner = new JulianDateTimeValueCleaner( false );
		$gregorianCleaner = new DateTimeValueCleaner( false );

		$value = new TimeValue( $date, 0, 0, 0, $precision, $calendar );

		$result = $julianCleaner->getStandardValue( $value );
		$this->assertSame( $expected, $result );

		if ( $calendar === TimeValue::CALENDAR_GREGORIAN ) {
			$result = $gregorianCleaner->getStandardValue( $value );
			$this->assertSame( $expected, $result );
		}
	}

	/**
	 * @dataProvider getDatesXSD11
	 */
	public function testCleanDateXSD11(
		$date,
		$calendar,
		$expected,
		$precision = TimeValue::PRECISION_SECOND
	) {
		$julianCleaner = new JulianDateTimeValueCleaner();
		$gregorianCleaner = new DateTimeValueCleaner();

		$value = new TimeValue( $date, 0, 0, 0, $precision, $calendar );

		$result = $julianCleaner->getStandardValue( $value );
		$this->assertSame( $expected, $result );

		if ( $calendar === TimeValue::CALENDAR_GREGORIAN ) {
			$result = $gregorianCleaner->getStandardValue( $value );
			$this->assertSame( $expected, $result );
		}
	}

}
