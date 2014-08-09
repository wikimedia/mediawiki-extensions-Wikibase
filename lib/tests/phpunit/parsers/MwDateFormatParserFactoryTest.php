<?php

namespace Wikibase\Lib\Parsers\Test;

use DataValues\TimeValue;
use Language;
use PHPUnit_Framework_TestCase;
use Wikibase\Lib\Parsers\MwDateFormatParserFactory;

/**
 * @covers Wikibase\Lib\Parsers\MwDateFormatParserFactory
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class MwDateFormatParserFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var MwDateFormatParserFactory
	 */
	private $factory;

	protected function setUp() {
		$this->factory = new MwDateFormatParserFactory();
	}

	public function testGetMwDateFormatParser() {
		$parser = $this->factory->getMwDateFormatParser();
		$this->assertInstanceOf( 'ValueParsers\ValueParser', $parser );
	}

	public function testGetMwDateFormatParserWithParameters() {
		$parser = $this->factory->getMwDateFormatParser( 'en', 'dmy', 'date' );
		$this->assertInstanceOf( 'ValueParsers\ValueParser', $parser );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetMwDateFormatParserWithInvalidLanguageCode() {
		$this->factory->getMwDateFormatParser( null );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetMwDateFormatParserWithInvalidFormat() {
		$this->factory->getMwDateFormatParser( 'en', null );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetMwDateFormatParserWithInvalidFormatType() {
		$this->factory->getMwDateFormatParser( 'en', 'dmy', null );
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

	public function validInputProvider() {
		$mwTimestamps = array(
			'12010304054201',
			'19701110064300',
			'20301231234500',
		);
		for ( $i = 1; $i <= 12; $i++ ) {
			$mwTimestamps[] = sprintf( '2014%02d01224400', $i );
		}

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

		$cases = array();

		foreach ( $this->getLanguageCodes() as $languageCode ) {
			$language = Language::factory( $languageCode );

			foreach ( $dateFormatPreferences as $dateFormatPreference => $maximumPrecision ) {
				foreach ( $dateFormatTypes as $dateFormatType => $precision ) {
					$dateFormat = $language->getDateFormatString( $dateFormatType, $dateFormatPreference );
					if ( $precision === null ) {
						$precision = $maximumPrecision;
					}

					foreach ( $mwTimestamps as $mwTimestamp ) {
						$input = $language->sprintfDate( $dateFormat, $mwTimestamp );
						$expected = new TimeValue(
							$this->getIsoTimestamp( $mwTimestamp, $precision ),
							0, 0, 0,
							$precision,
							TimeValue::CALENDAR_GREGORIAN
						);

						$cases[] = array(
							$input,
							$expected,
							$languageCode,
							$dateFormatPreference,
							$dateFormatType
						);
					}
				}
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider validInputProvider
	 */
	public function testParseWithValidInputs(
		$input,
		TimeValue $expected,
		$languageCode,
		$dateFormatPreference,
		$dateFormatType
	) {
		$parser = $this->factory->getMwDateFormatParser(
			$languageCode,
			$dateFormatPreference,
			$dateFormatType
		);
		$parsed = $parser->parse( $input );
		$this->assertTrue( $expected->equals( $parsed ), $input . ' became ' . $parsed );
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

	public function invalidInputProvider() {
		return array(
			array( null ),
			array( true ),
			array( 2015 ),
		);
	}

	/**
	 * @dataProvider invalidInputProvider
	 * @expectedException \ValueParsers\ParseException
	 */
	public function testParseWithInvalidInputs( $input ) {
		$parser = $this->factory->getMwDateFormatParser();
		$parser->parse( $input );
	}

	public function languageCodeProvider() {
		return array_map( function( $languageCode ) {
			return array( $languageCode );
		}, $this->getLanguageCodes() );
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testMwMonthNameTranslations( $languageCode ) {
		$language = Language::factory( $languageCode );
		$months = array();
		$genMonths = array();
		$abbrMonths = array();

		for ( $i = 1; $i <= 12; $i++ ) {
			$this->assertValidMonthNameTranslation( $language->getMonthName( $i ) );
			$this->assertValidMonthNameTranslation( $language->getMonthNameGen( $i ) );
			$this->assertValidMonthNameTranslation( $language->getMonthAbbreviation( $i ) );

			$months[$language->getMonthName( $i )] = $i;
			$genMonths[$language->getMonthNameGen( $i )] = $i;
			$abbrMonths[$language->getMonthAbbreviation( $i )] = $i;
		}

		$this->assertCount( 12, $months, 'Month names are unique' );
		$this->assertCount( 12, $genMonths, 'Genitive month names are unique' );
		$this->assertCount( 12, $abbrMonths, 'Abbreviated month names are unique' );
	}

	private function assertValidMonthNameTranslation( $month ) {
		$this->assertInternalType( 'string', $month );
		$this->assertNotEmpty( $month );
		$this->assertNotRegExp( '/^\p{Z}/u', $month, 'Month name does not start with whitespace' );
		$this->assertNotRegExp( '/\p{Z}$/u', $month, 'Month name does not end with whitespace' );
	}

}
