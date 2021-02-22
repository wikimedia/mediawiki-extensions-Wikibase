<?php

namespace Wikibase\Repo\Parsers;

use Language;
use ValueParsers\BasicNumberUnlocalizer;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MediaWikiNumberUnlocalizer extends BasicNumberUnlocalizer {

	private const UNLOCALIZER_MAP = [
		"\xe2\x88\x92" => '-', // convert minus (U+2212) to hyphen
		"\xe2\x93\x96" => '-', // convert "heavy minus" (U+2796) to hyphen
		"\xe2\x93\x95" => '+', // convert "heavy plus" (U+2795) to plus
	];

	/**
	 * @var Language
	 */
	private $language;

	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * @see NumberUnlocalizer::unlocalizeNumber
	 *
	 * @param string $number string to process
	 *
	 * @return string unlocalized number, in a form suitable for floatval resp. intval.
	 */
	public function unlocalizeNumber( $number ) {
		$canonicalizedNumber = $this->language->parseFormattedNumber( $number );

		// convert "pretty" characters not covered by parseFormattedNumber
		$canonicalizedNumber = strtr( $canonicalizedNumber, self::UNLOCALIZER_MAP );

		// strip any remaining whitespace
		$canonicalizedNumber = preg_replace( '/\s+/u', '', $canonicalizedNumber );

		return $canonicalizedNumber;
	}

	/**
	 * @see NumberUnlocalizer::getNumberRegex
	 *
	 * Constructs a regular expression based on Language::digitTransformTable()
	 * and Language::separatorTransformTable().
	 *
	 * Note that the resulting regex will accept scientific notation.
	 *
	 * @param string $delimiter The regex delimiter, used for escaping.
	 *
	 * @return string regular expression
	 */
	public function getNumberRegex( $delimiter = '/' ) {
		$digitMap = $this->language->digitTransformTable();
		$separatorMap = $this->language->separatorTransformTable();

		// Always accept canonical digits and separators
		$digits = '0123456789';
		$separators = ',.';

		// Add localized digits and separators
		if ( is_array( $digitMap ) ) {
			$digits .= implode( '', array_values( $digitMap ) );
		}
		if ( is_array( $separatorMap ) ) {
			$separators .= implode( '', array_values( $separatorMap ) );
		}

		// if any whitespace characters are acceptable, also accept a regular blank.
		if ( preg_match( '/\s/u', $separators ) ) {
			$separators .= ' ';
		}

		$numberRegex = '[-−+]?[' . preg_quote( $digits . $separators, $delimiter ) . ']+';

		// Scientific notation support. Keep in sync with DecimalParser::splitDecimalExponent.
		$numberRegex .= '(?:(?:[eE]|x10\^)[-+]?[' . preg_quote( $digits, $delimiter ) . ']+)?';

		return $numberRegex;
	}

}
