<?php

namespace Wikibase\Lib;
use Language;
use ValueParsers\BasicUnlocalizer;

/**
 * MediaWikiNumberUnlocalizer
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MediaWikiNumberUnlocalizer extends BasicUnlocalizer {

	protected static $unlocalizerMap = array(
		"\xe2\x88\x92" => '-', // convert minus (U+2212) to hyphen
		"\xe2\x93\x96" => '-', // convert "heavy minus" (U+2796) to hyphen
		"\xe2\x93\x95" => '+', // convert "heavy plus" (U+2795) to plus
	);

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @param Language $language
	 */
	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * @see Unlocalizer::unlocalize()
	 *
	 * @param string $number string to process
	 *
	 * @return string unlocalized string
	 */
	public function unlocalizeNumber( $number ) {
		$canonicalizedNumber = $this->language->parseFormattedNumber( $number );

		// convert "pretty" characters not covered by parseFormattedNumber
		$canonicalizedNumber = strtr( $canonicalizedNumber, self::$unlocalizerMap );

		// strip any remaining whitespace
		$canonicalizedNumber = preg_replace( '/\s/u', '', $canonicalizedNumber );

		return $canonicalizedNumber;
	}

	/**
	 * @see Unlocalizer::getNumberRegex()
	 *
	 * Constructs a regular expression based on Language::digitTransformTable()
	 * and Language::separatorTransformTable().
	 *
	 * @param string $delim the regex delimiter, used for escaping.
	 *
	 * @return string regular expression
	 */
	public function getNumberRegex( $delim = '/' ) {
		$digitMap = $this->language->digitTransformTable();
		$separatorMap = $this->language->separatorTransformTable();

		if ( empty( $digitMap ) ) {
			$numerals = '0123456789';
		} else {
			$numerals = implode( '', array_keys( $digitMap ) ) // accept canonical numerals
				. implode( '', array_values( $digitMap ) ); // ...and localized numerals
		}

		if ( empty( $separatorMap ) ) {
			$separators = '.,';
		} else {
			$separators = implode( '', array_keys( $separatorMap ) ) // accept canonical separators
				. implode( '', array_values( $separatorMap ) ); // ...and localized separators
		}

		$characters = $numerals . $separators;

		// if any whitespace characters are acceptable, also accept a regular blank.
		if ( preg_match( '/\s/u', $characters ) ) {
			$characters = $characters . ' ';
		}

		return '[-+]?[' . preg_quote( $characters, $delim ) . ']+';
	}

}
