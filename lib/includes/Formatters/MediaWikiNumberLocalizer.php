<?php

namespace Wikibase\Lib\Formatters;

use InvalidArgumentException;
use Language;
use ValueFormatters\NumberLocalizer;

/**
 * Localizes a numeric string using MediaWiki's Language class.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MediaWikiNumberLocalizer implements NumberLocalizer {

	/**
	 * @var Language
	 */
	private $language;

	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * @see NumberLocalizer::localizeNumber
	 *
	 * @param string|int|float $number
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function localizeNumber( $number ) {
		$number = (string)$number;
		$formatted = $this->language->formatNum( $number );
		if ( $this->language->parseFormattedNumber( $formatted ) === $number ) {
			return $formatted;
		} else {
			// loss of precision during formatting (T268456),
			// fall back to unformatted number
			return $number;
		}
	}

}
