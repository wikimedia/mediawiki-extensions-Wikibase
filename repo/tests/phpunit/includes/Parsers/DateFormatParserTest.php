<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\Test\StringValueParserTest;
use Wikibase\Repo\Parsers\DateFormatParser;

/**
 * @covers Wikibase\Repo\Parsers\DateFormatParser
 *
 * @group ValueParsers
 * @group WikibaseRepo
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DateFormatParserTest extends StringValueParserTest {

	/**
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return DateFormatParser
	 */
	protected function getInstance() {
		return new DateFormatParser();
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 */
	public function validInputProvider() {
		$monthNames = array( 9 => array( 'Sep', 'September' ) );

		$valid = array(
			'Default options' => array(
				'1 9 2014',
				'd M Y', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'Transform map' => array(
				'Z g Zo15',
				'd M Y', array( '0' => 'o', 2 => 'Z', 9 => 'g' ), null,
				'+2015-09-02T00:00:00Z'
			),
			'Default month map' => array(
				'1. September 2014',
				'd. M Y', null, $monthNames,
				'+2014-09-01T00:00:00Z'
			),
			'Simple month map' => array(
				'1 September 2014',
				'd M Y', null, array( 9 => 'September' ),
				'+2014-09-01T00:00:00Z'
			),
			'Escapes' => array(
				'1s 9s 2014\\',
				'd\\s M\\s Y\\', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'Quotes' => array(
				'1th 9th 2014"',
				'd"th" M"th" Y"', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'Raw characters' => array(
				'1 9ä 2014',
				'd Mä Y', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'xx escaping' => array(
				'2014 x9 1',
				'Y xxm d', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'Raw modifiers' => array(
				'2014 9 1',
				'Y xNmxN xnd', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'Toggles' => array(
				'2014 9 1',
				'xhY m xrd', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'3-character codes' => array(
				'2014 9 1',
				'xjY m d', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'Whitespace is optional' => array(
				'1September2014',
				'd M Y', null, $monthNames,
				'+2014-09-01T00:00:00Z'
			),
			'Delimiters are optional' => array(
				'1 9 2014',
				'd. M. Y', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'Delimiters are ignored' => array(
				'1. 9. 2014',
				'd M Y', null, null,
				'+2014-09-01T00:00:00Z'
			),
			'Year precision' => array(
				'2014',
				'Y', null, null,
				'+2014-00-00T00:00:00Z', TimeValue::PRECISION_YEAR
			),
			'Month precision' => array(
				'9 2014',
				'M Y', null, null,
				'+2014-09-00T00:00:00Z', TimeValue::PRECISION_MONTH
			),
			'Minute precision' => array(
				'1 9 2014 15:30',
				'd M Y H:i', null, null,
				'+2014-09-01T15:30:00Z', TimeValue::PRECISION_MINUTE
			),
			'Second precision' => array(
				'1 9 2014 15:30:59',
				'd M Y H:i:s', null, null,
				'+2014-09-01T15:30:59Z', TimeValue::PRECISION_SECOND
			),
		);

		$cases = array();

		foreach ( $valid as $key => $args ) {
			$dateString = $args[0];
			$dateFormat = $args[1];
			$digitTransformTable = $args[2];
			$monthNames = $args[3];
			$timestamp = $args[4];
			$precision = isset( $args[5] ) ? $args[5] : TimeValue::PRECISION_DAY;
			$calendarModel = isset( $args[6] ) ? $args[6] : TimeValue::CALENDAR_GREGORIAN;

			$cases[$key] = array(
				$dateString,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel ),
				new DateFormatParser( new ParserOptions( array(
					DateFormatParser::OPT_DATE_FORMAT => $dateFormat,
					DateFormatParser::OPT_DIGIT_TRANSFORM_TABLE => $digitTransformTable,
					DateFormatParser::OPT_MONTH_NAMES => $monthNames,
				) ) )
			);
		}

		return $cases;
	}

	/**
	 * @see StringValueParserTest::invalidInputProvider
	 */
	public function invalidInputProvider() {
		$invalid = array(
			'',
			'1',
			'1 2',
			'-1 2 3',
			'1 -2 3',
			'1 2 -3',
			'1st 2nd 3',
			'1 13 1',
			'32 1 1',
		);

		$cases = parent::invalidInputProvider();

		foreach ( $invalid as $value ) {
			$cases[] = array( $value );
		}

		return $cases;
	}

	public function testInvalidDateFormatOption() {
		$parser = new DateFormatParser( new ParserOptions( array(
			DateFormatParser::OPT_DATE_FORMAT => 'YY',
		) ) );
		$this->setExpectedException( ParseException::class );
		$parser->parse( '' );
	}

}
