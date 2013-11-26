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
	 * @see Localizer::localize()
	 *
	 * @since 0.5
	 *
	 * @param string $number a numeric string
	 * @param string $language a language code
	 * @param FormatterOptions $options
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function localize( $number, $language, FormatterOptions $options ) {
		$language = Language::factory( $language );

		$localiezdNumber = $language->formatNum( $number );
		return $localiezdNumber;
	}
}
