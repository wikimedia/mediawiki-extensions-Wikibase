<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Repo\Parsers\MonthNameProvider;
use Wikibase\Repo\Parsers\YearMonthTimeParser;

/**
 * @covers Wikibase\Repo\Parsers\YearMonthTimeParser
 *
 * @group ValueParsers
 * @group WikibaseRepo
 * @group Wikibase
 * @group TimeParsers
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Thiemo Mättig
 */
class YearMonthTimeParserTest extends StringValueParserTest {

	/**
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return YearMonthTimeParser
	 */
	protected function getInstance() {
		$monthNameProvider = $this->getMockBuilder( MonthNameProvider::class )
			->disableOriginalConstructor()
			->getMock();
		$monthNameProvider->expects( $this->once() )
			->method( 'getMonthNumbers' )
			->with( 'en' )
			->will( $this->returnValue( array(
				'January' => 1,
				'Jan' => 1,
				'April' => 4,
			) ) );

		return new YearMonthTimeParser( $monthNameProvider );
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 */
	public function validInputProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$julian = 'http://www.wikidata.org/entity/Q1985786';

		$argLists = [];

		$valid = array(
			// leading zeros
			'00001 1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'000000001 100001999' =>
				array( '+100001999-01-00T00:00:00Z' ),

			// use string month names
			'Jan/1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'January/1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'January/1' =>
				array( '+0001-01-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'1999 January' =>
				array( '+1999-01-00T00:00:00Z' ),
			'January 1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'January-1' =>
				array( '+0001-01-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'JanuARY-1' =>
				array( '+0001-01-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'JaN/1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'januARY-1' =>
				array( '+0001-01-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'jan/1999' =>
				array( '+1999-01-00T00:00:00Z' ),

			// use different date separators
			'1-1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'1/1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'1 / 1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'1 1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'1,1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'1.1999' =>
				array( '+1999-01-00T00:00:00Z' ),
			'1. 1999' =>
				array( '+1999-01-00T00:00:00Z' ),

			// presume mm/yy unless impossible month, in which case switch
			'12/12' =>
				array( '+0012-12-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'12/11' =>
				array( '+0011-12-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'11/12' =>
				array( '+0012-11-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'13/12' =>
				array( '+0013-12-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'12/13' =>
				array( '+0013-12-00T00:00:00Z', TimeValue::PRECISION_MONTH, $julian ),
			'2000 1' =>
				array( '+2000-01-00T00:00:00Z' ),

			// big years
			'April-1000000001' =>
				array( '+1000000001-04-00T00:00:00Z' ),
			'April 1000000001' =>
				array( '+1000000001-04-00T00:00:00Z' ),
			'1000000001 April' =>
				array( '+1000000001-04-00T00:00:00Z' ),
			'1 13000' =>
				array( '+13000-01-00T00:00:00Z' ),

			// parse 0 month as if no month has been entered
			'0.1999' =>
				array( '+1999-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			'1999 0' =>
				array( '+1999-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
		);

		foreach ( $valid as $value => $expected ) {
			$timestamp = $expected[0];
			$precision = isset( $expected[1] ) ? $expected[1] : TimeValue::PRECISION_MONTH;
			$calendarModel = isset( $expected[2] ) ? $expected[2] : $gregorian;

			$argLists[] = array(
				(string)$value,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel )
			);
		}

		return $argLists;
	}

	/**
	 * @see StringValueParserTest::invalidInputProvider
	 */
	public function invalidInputProvider() {
		$argLists = parent::invalidInputProvider();

		$invalid = array(
			//These are just wrong!
			'June June June',
			'111 111 111',
			'Jann 2014',
			'13/13',
			'13,1999',
			'1999,13',

			//Dont parse stuff with separators in the year
			'june 200,000,000',
			'june 200.000.000',

			//Not within the scope of this parser
			'1 July 20000',
			'20000',
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}
