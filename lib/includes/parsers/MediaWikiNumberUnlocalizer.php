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
	 * @param string $delimiter The regex delimiter, used for escaping.
	 *
	 * @return string regular expression
	 */
	public function getNumberRegex( $delimiter = '/' ) {
		$digitMap = $this->language->digitTransformTable();
		$separatorMap = $this->language->separatorTransformTable();

		// Always accept canonical digits and separators
		$characters = '0123456789,.';

		// Add localized digits and separators
		if ( is_array( $digitMap ) ) {
			$characters .= implode( '', array_values( $digitMap ) );
		}
		if ( is_array( $separatorMap ) ) {
			$characters .= implode( '', array_values( $separatorMap ) );
		}

		// if any whitespace characters are acceptable, also accept a regular blank.
		if ( preg_match( '/\s/u', $characters ) ) {
			$characters .= ' ';
		}

		return '[-+]?[' . preg_quote( $characters, $delimiter ) . ']+';
	}

}
