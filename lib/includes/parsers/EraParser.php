<?php

namespace Wikibase\Lib\Parsers;

use ValueParsers\ParseException;
use ValueParsers\StringValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class EraParser extends StringValueParser {

	/**
	 * @since 0.5
	 */
	const CURRENT_ERA = '+';
	/**
	 * @since 0.5
	 */
	const BEFORE_CURRENT_ERA = '-';

	/**
	 * @var string regex snippet matching BEFORE_CURRENT_ERA
	 */
	private $BCEregex = '(B\.?\s*C\.?(\s*E\.?)?|Before\s+(Christ|Common\s+Era))';
	/**
	 * @var string regex snippet matching CURRENT_ERA
	 */
	private $CEregex = '(C\.?\s*E\.?|A\.?\s*D\.?|Common\s+Era|After\s+Christ|Anno\s+Domini)';

	/**
	 * Parses the provided string and returns the era
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return array( 0 => parsed era constant, 1 => $value with no era data )
	 */
	protected function stringParse( $value ) {
		$char1 = substr( $value, 0, 1 );
		if( $char1 === self::CURRENT_ERA || $char1 === self::BEFORE_CURRENT_ERA ) {
			$eraFromSign = $char1;
		}
		if( preg_match( '/' . $this->CEregex . '$/i', $value, $matches ) ) {
			$eraFromString = self::CURRENT_ERA;
		}
		if( preg_match( '/' . $this->BCEregex . '$/i', $value, $matches ) ) {
			$eraFromString = self::BEFORE_CURRENT_ERA;
		}

		if( isset( $eraFromSign ) && isset( $eraFromString ) ) {
			throw new ParseException( 'Parsed two eras from the same string' );
		}

		$cleanValue = $this->cleanValue( $value );
		if( isset( $eraFromString ) ) {
			return array( $eraFromString, $cleanValue );
		}
		if( isset( $eraFromSign ) ) {
			return array( $eraFromSign, $cleanValue );
		}
		//Default to CE
		return array( self::CURRENT_ERA, $cleanValue );
	}

	/**
	 * Removes any parse-able Era information from the given string value
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function cleanValue( $value ) {
		$char1 = substr( $value, 0, 1 );
		if( $char1 === self::CURRENT_ERA || $char1 === self::BEFORE_CURRENT_ERA ) {
			$value = substr( $value, 1 );
		}

		$value = preg_replace( '/\s*(' . $this->CEregex . '|' .  $this->BCEregex . ')$/i', '', $value );

		return trim( $value );
	}

}
