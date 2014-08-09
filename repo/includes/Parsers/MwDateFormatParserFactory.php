<?php

namespace Wikibase\Repo\Parsers;

use InvalidArgumentException;
use Language;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;

/**
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class MwDateFormatParserFactory {

	/**
	 * @var array[]
	 */
	private static $monthNamesCache = array();

	/**
	 * @param string $languageCode
	 * @param string $dateFormatPreference Typically "dmy", "mdy", "ISO 8601" or "default", but
	 *  this depends heavily on the actual MessagesXx.php file.
	 * @param string $dateFormatType Either "date", "both" (date and time) or "monthonly".
	 *
	 * @throws InvalidArgumentException
	 * @return ValueParser
	 */
	public function getMwDateFormatParser(
		$languageCode = 'en',
		$dateFormatPreference = 'dmy',
		$dateFormatType = 'date'
	) {
		if ( !is_string( $languageCode ) || $languageCode === '' ) {
			throw new InvalidArgumentException( '$languageCode must be a non-empty string' );
		}

		if ( !is_string( $dateFormatPreference ) || $dateFormatPreference === '' ) {
			throw new InvalidArgumentException( '$dateFormatPreference must be a non-empty string' );
		}

		if ( !is_string( $dateFormatType ) || $dateFormatType === '' ) {
			throw new InvalidArgumentException( '$dateFormatType must be a non-empty string' );
		}

		$language = Language::factory( $languageCode );
		$dateFormat = $language->getDateFormatString( $dateFormatType, $dateFormatPreference );
		$digitTransformTable = $language->digitTransformTable();
		$monthNames = $this->getCachedMonthNames( $language );

		return new DateFormatParser( new ParserOptions( array(
			DateFormatParser::OPT_DATE_FORMAT => $dateFormat,
			DateFormatParser::OPT_DIGIT_TRANSFORM_TABLE => $digitTransformTable,
			DateFormatParser::OPT_MONTH_NAMES => $monthNames,
		) ) );
	}

	/**
	 * @param Language $language
	 *
	 * @return array[]
	 */
	private function getCachedMonthNames( Language $language ) {
		$languageCode = $language->getCode();

		if ( !isset( self::$monthNamesCache[$languageCode] ) ) {
			self::$monthNamesCache[$languageCode] = $this->getMwMonthNames( $language );
		}

		return self::$monthNamesCache[$languageCode];
	}

	/**
	 * @param Language $language
	 *
	 * @return array[]
	 */
	private function getMwMonthNames( Language $language ) {
		$monthNames = array();

		for ( $i = 1; $i <= 12; $i++ ) {
			$monthNames[$i] = array(
				$this->trim( $language->getMonthName( $i ) ),
				$this->trim( $language->getMonthNameGen( $i ) ),
				$this->trim( $language->getMonthAbbreviation( $i ) ),
			);
		}

		return $monthNames;
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	private function trim( $string ) {
		return preg_replace( '/^\p{Z}|\p{Z}$/u', '', $string );
	}

}
