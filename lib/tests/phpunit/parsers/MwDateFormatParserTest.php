<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use Language;
use ValueParsers\ParserOptions;
use ValueParsers\Test\StringValueParserTest;
use ValueParsers\ValueParser;
use Wikibase\Lib\Parsers\MwDateFormatParser;

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

	/**
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return MwDateFormatParser
	 */
	protected function getInstance() {
		return new MwDateFormatParser();
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
			$expected = new TimeValue( $case[1], 0, 0, 0, $precision, TimeValue::CALENDAR_GREGORIAN );
			$cases[$i] = array( $case[0], $expected );
		}

		return $cases;
	}

	private function getLanguageCodes() {
		// Focus on a critical subset of languages. Enable the following MediaWiki dependency to
		// test the full set of all 400+ supported languages. This takes 30 minutes on my machine.
		// return array_keys( Language::fetchLanguageNames() );
		return array(
			'ace',
			'anp',
			'bo',
			'de',
			'en',
			'fa', // right-to-left
			'gan',
			'haw',
			'krj',
			'ln',
			'lzh', // Chinese
			'lzz',
			'nn',
			'pt',
			'sma',
			'sv',
			'ty',
			'udm',
			'vi',
			'zh-hans', // Chinese
			'zh-hant', // Chinese
		);
	}

	public function languageCodeProvider() {
		return array_map( function( $languageCode ) {
			return array( $languageCode );
		}, $this->getLanguageCodes() );
	}

	public function dateFormatProvider() {
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

		foreach ( $this->getLanguageCodes() as $languageCode ) {
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
						$expected = new TimeValue(
							$isoTimestamp,
							0, 0, 0,
							$precision,
							TimeValue::CALENDAR_GREGORIAN
						);
						$cases[] = array( $expected, $dateString, $languageCode, $dateFormat );
					}
				}
			}
		}

		return $cases;
	}

	/**
	 * @param string $mwTimestamp
	 * @param int $precision
	 *
	 * @return string
	 */
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

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testMonthsTranslations( $languageCode ) {
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

		$this->assertCount( 12, $months, 'Month names are unique' );
		$this->assertCount( 12, $genMonths, 'Genitive month names are unique' );
		$this->assertCount( 12, $abbrMonths, 'Abbreviated month names are unique' );
	}

	private function assertMonth( $month ) {
		$this->assertInternalType( 'string', $month );
		$this->assertNotEmpty( $month );
		$this->assertNotRegExp( '/^\p{Z}/u', $month, 'Month name does not start with whitespace' );
		$this->assertNotRegExp( '/\p{Z}$/u', $month, 'Month name does not end with whitespace' );
	}

}
