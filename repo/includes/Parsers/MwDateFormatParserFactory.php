<?php

namespace Wikibase\Repo\Parsers;

use InvalidArgumentException;
use Language;
use MediaWiki\MediaWikiServices;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;

/**
 * This factory creates a parser that accepts most outputs of MediaWiki's Language::sprintfDate
 * formatting.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MwDateFormatParserFactory {

	/**
	 * @var array[]
	 */
	private static $monthNamesCache = [];

	/**
	 * @param string $languageCode
	 * @param string $dateFormatPreference Typically "dmy", "mdy", "ISO 8601" or "default", but
	 *  this depends heavily on the actual MessagesXx.php file.
	 * @param string $dateFormatType Either "date", "both" (date and time) or "monthonly".
	 * @param ParserOptions|null $options
	 *
	 * @throws InvalidArgumentException
	 * @return ValueParser
	 */
	public function getMwDateFormatParser(
		$languageCode = 'en',
		$dateFormatPreference = 'dmy',
		$dateFormatType = 'date',
		ParserOptions $options = null
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

		$language = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $languageCode );
		$dateFormat = $language->getDateFormatString( $dateFormatType, $dateFormatPreference );
		$digitTransformTable = $language->digitTransformTable();
		$monthNames = $this->getCachedMonthNames( $language );

		if ( $options === null ) {
			$options = new ParserOptions();
		}
		$options->setOption( DateFormatParser::OPT_DATE_FORMAT, $dateFormat );
		$options->setOption( DateFormatParser::OPT_DIGIT_TRANSFORM_TABLE, $digitTransformTable );
		$options->setOption( DateFormatParser::OPT_MONTH_NAMES, $monthNames );
		return new DateFormatParser( $options );
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
		$monthNames = [];

		for ( $i = 1; $i <= 12; $i++ ) {
			$monthNames[$i] = [
				$this->trim( $language->getMonthName( $i ) ),
				$this->trim( $language->getMonthNameGen( $i ) ),
				$this->trim( $language->getMonthAbbreviation( $i ) ),
			];
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
