<?php

namespace Wikibase\Lib;

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
		$localizedNumber = $this->language->formatNum( $number );
		return $localizedNumber;
	}

}
