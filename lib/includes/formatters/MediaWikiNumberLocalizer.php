<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\Localizer;

/**
 * Localizes a numeric string using MediaWiki's Language class.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MediaWikiNumberLocalizer implements Localizer {

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
	 * @see Localizer::localize()
	 *
	 * @since 0.5
	 *
	 * @param string $number a numeric string
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function localizeNumber( $number ) {
		$localiezdNumber = $this->language->formatNum( $number );
		return $localiezdNumber;
	}
}
