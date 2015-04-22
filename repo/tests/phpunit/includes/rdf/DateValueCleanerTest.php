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
 * @licence GNU GPL v2+
 * @author Stas Malyshev
 */
class DateValueCleanerTest extends \PHPUnit_Framework_TestCase {

	public function getDates() {
		return array(
			// Gregorian
			array("+00000002014-01-05T12:34:56Z", "http://www.wikidata.org/entity/Q1985727", "2014-01-05T12:34:56Z"),
			array("-00000000200-00-00T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "-0200-01-01T00:00:00Z"),
			array("+00000000200-00-00T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "0200-01-01T00:00:00Z"),
			array("+02000000200-00-00T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "2000000200-01-01T00:00:00Z"),
			array("+92000000200-05-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "92000000200-05-31T00:00:00Z"),
			array("-02000000200-05-22T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "-2000000200-05-22T00:00:00Z"),
			array("-02000000200-02-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "-2000000200-02-29T00:00:00Z"),
			array("+00000000200-02-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "0200-02-28T00:00:00Z"),
			array("+00000000204-02-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "0204-02-29T00:00:00Z"),
			array("+00000002204-04-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", "2204-04-30T00:00:00Z"),
			array("+00000000000-04-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", null),
			array("-00000000000-04-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985727", null),
			// Julian
			array("+00000002014-01-05T12:34:56Z", "http://www.wikidata.org/entity/Q1985786", "2014-01-18T12:34:56Z"),
			array("-00000002014-01-05T12:34:56Z", "http://www.wikidata.org/entity/Q1985786", "-2015-12-19T12:34:56Z"),
			array("+00000000200-02-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985786", "0200-03-02T00:00:00Z"),
			array("+00000000204-02-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985786", "0204-03-02T00:00:00Z"),
			array("-02000000204-02-31T00:00:00Z", "http://www.wikidata.org/entity/Q1985786", null),
			// Neither
			array("+00000002014-01-05T12:34:56Z", "http://www.wikidata.org/entity/Q42", null),
		);
	}

	/**
	 * @dataProvider getDates
	 */
	public function testCleanDate( $date, $calendar, $expected ) {
		$julianCleaner = new JulianDateTimeValueCleaner();
		$gregorianCleaner = new DateTimeValueCleaner();

		$value = new TimeValue( $date, 0, 0, 0, TimeValue::PRECISION_SECOND, $calendar );

		$result = $julianCleaner->getStandardValue( $value );
		$this->assertEquals( $expected, $result );

		if ( $calendar == 'http://www.wikidata.org/entity/Q1985727' ) {
			$result = $gregorianCleaner->getStandardValue( $value );
			$this->assertEquals( $expected, $result );
		}
	}

}
