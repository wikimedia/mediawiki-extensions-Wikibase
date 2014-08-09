<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use Language;
use ValueFormatters\TimeFormatter;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\Test\StringValueParserTest;
use ValueParsers\ValueParser;
use Wikibase\Lib\Parsers\MwDateFormatParser;
use Wikibase\Utils;

/**
 * @covers Wikibase\Lib\Parsers\MwDateFormatParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class MwDateFormatParserTest extends StringValueParserTest {

	protected function getParserClass() {
		return 'Wikibase\Lib\Parsers\MwDateFormatParser';
	}

	public function validInputProvider() {
		$cases = array(
			array( '1 9 2014', '+2014-09-01T00:00:00Z', TimeValue::PRECISION_DAY ),
			array( '1 September 2014', '+2014-09-01T00:00:00Z', TimeValue::PRECISION_DAY ),
			array( '1. Sep. 2014', '+2014-09-01T00:00:00Z', TimeValue::PRECISION_DAY ),
			array( '1.September.2014', '+2014-09-01T00:00:00Z', TimeValue::PRECISION_DAY ),
		);

		foreach ( $cases as $i => $case ) {
			$precision = isset( $case[2] ) ? $case[2] : TimeValue::PRECISION_DAY;
			$expected = new TimeValue( $case[1], 0, 0, 0, $precision, TimeFormatter::CALENDAR_GREGORIAN );
			$cases[$i] = array( $case[0], $expected );
		}

		return $cases;
	}

	public function dateFormatProvider() {
		$languageCodes = array(
			'ace',
			'anp',
			'bo',
			'de',
			'en',
			'fa', //right-to-left
			'gan',
			'haw',
			'krj',
			'ln',
			'lzh', //Chinese
			'lzz',
			'nn',
			'pt',
			'sma',
			'sv',
			'ty',
			'udm',
			'vi',
			'zh-hans', //Chinese
			'zh-hant', //Chinese
		);
		$languageCodes = Utils::getLanguageCodes();
		$dateFormatPreferences = array(
			'mdy' => TimeValue::PRECISION_MINUTE,
			'dmy' => TimeValue::PRECISION_MINUTE,
			'ymd' => TimeValue::PRECISION_MINUTE,
			'ISO 8601' => TimeValue::PRECISION_SECOND,
		);
		$dateFormatTypes = array(
			'date' => TimeValue::PRECISION_DAY,
			'monthonly' => TimeValue::PRECISION_MONTH,
			'both' => null,
		);
		$mwTimestamps = array(
			'12010304054201',
			'19701110064300',
			'20301231234500',
		);
		for ( $i = 1; $i <= 12; $i++ ) {
			$mwTimestamps[] = sprintf( '2014%02d01224400', $i );
		}

		$cases = array();

		foreach ( $languageCodes as $languageCode ) {
/*
			// Exclude Chinese for now
			if ( preg_match( '/^l?zh/', $languageCode ) ) {
				continue;
			}

			if ( false
//				|| $languageCode === 'dsb'
//				|| $languageCode === 'krj'
//				|| $languageCode === 'ln'
				|| $languageCode === 'lzz'
//				|| $languageCode === 'sma'
//				|| $languageCode === 'ty'
//				|| $languageCode === 'udm'
				|| $languageCode === 'vi'
			) {
				continue;
			}
*/

			$language = Language::factory( $languageCode );

			foreach ( $dateFormatPreferences as $dateFormatPreference => $maximumPrecision ) {
				foreach ( $dateFormatTypes as $dateFormatType => $precision ) {
					$dateFormat = $language->getDateFormatString( $dateFormatType, $dateFormatPreference );
					if ( $precision === null) {
						$precision = $maximumPrecision;
					}

					foreach ( $mwTimestamps as $mwTimestamp ) {
						$isoTimestamp = $this->getIsoTimestamp( $mwTimestamp, $precision );
						$dateString = $language->sprintfDate( $dateFormat, $mwTimestamp );
						$expected = new TimeValue( $isoTimestamp, 0, 0, 0, $precision, TimeFormatter::CALENDAR_GREGORIAN );
						$cases[] = array( $expected, $dateString, $languageCode, $dateFormat );
					}
				}
			}
		}

		return $cases;
	}

	private function getIsoTimestamp( $mwTimestamp, $precision ) {
		if ( $precision <= TimeValue::PRECISION_YEAR ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 4 ) . '0000000000';
		} elseif ( $precision === TimeValue::PRECISION_MONTH ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 6 ) . '00000000';
		} elseif ( $precision === TimeValue::PRECISION_DAY ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 8 ) . '000000';
		} elseif ( $precision === TimeValue::PRECISION_HOUR ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 10 ) . '0000';
		} elseif ( $precision === TimeValue::PRECISION_MINUTE ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 12 ) . '00';
		}

		return preg_replace( '/(....)(..)(..)(..)(..)(..)/s', '+$1-$2-$3T$4:$5:$6Z', $mwTimestamp );
	}

	/**
	 * @dataProvider dateFormatProvider
	 */
	public function testParseStrict( TimeValue $expected, $dateString, $languageCode, $dateFormat ) {
		$this->assertMonthsTranslations( $languageCode );
		$this->assertNotEmpty( $dateFormat );

		$parser = new MwDateFormatParser( new ParserOptions( array(
			ValueParser::OPT_LANG => $languageCode,
			MwDateFormatParser::OPT_FORMAT => $dateFormat,
			MwDateFormatParser::OPT_STRICT => true,
		) ) );
		$value = $parser->parse( $dateString );

		if (!$expected->equals( $value )){var_dump($expected,$value);}
		$this->assertTrue( $expected->equals( $value ), $dateString . ' became ' . $value );
	}

	private function assertMonthsTranslations( $languageCode ) {
		$language = Language::factory( $languageCode );
		$months = array();
		$genMonths = array();
		$abbrMonths = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$this->assertMonth( $language->getMonthName( $i ) );
			$this->assertMonth( $language->getMonthNameGen( $i ) );
			$this->assertMonth( $language->getMonthAbbreviation( $i ) );
			$months[$language->getMonthName( $i )] = $i;
			$genMonths[$language->getMonthNameGen( $i )] = $i;
			$abbrMonths[$language->getMonthAbbreviation( $i )] = $i;
		}
		$this->assertCount( 12, $months, 'Normal' );
		$this->assertCount( 12, $genMonths, 'Genitive' );
		$this->assertCount( 12, $abbrMonths, 'Abbreviation' );
	}

	private function assertMonth( $month ) {
		$this->assertInternalType( 'string', $month );
		$this->assertNotEmpty( $month );
		$this->assertNotRegExp( '/^\p{Z}/u', $month, 'ltrim' );
		$this->assertNotRegExp( '/\p{Z}$/u', $month, 'rtrim' );
	}

}
