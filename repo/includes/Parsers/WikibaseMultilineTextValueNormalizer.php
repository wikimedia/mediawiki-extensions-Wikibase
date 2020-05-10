<?php

namespace Wikibase\Repo\Parsers;

use InvalidArgumentException;
use ValueParsers\Normalizers\StringNormalizer;

/**
 * Adapter implementing ValueParsers\Normalizers\StringNormalizer based on \Wikibase\StringNormalize.
 * Used to perform string normalization in StringParser.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseMultilineTextValueNormalizer implements StringNormalizer {

	/**
	 * @var \Wikibase\StringNormalizer
	 */
	private $normalizer;

	/**
	 * @param \Wikibase\StringNormalizer $normalizer
	 */
	public function __construct( \Wikibase\StringNormalizer $normalizer ) {
		$this->normalizer = $normalizer;
	}

	/**
	 * Trims leading and trailing whitespace and performs unicode normalization
	 * by calling Wikibase\StringNormalizer::trimToNFC().
	 *
	 * @see StringNormalizer::normalize()
	 * @see Wikibase\StringNormalizer::trimToNFC()
	 *
	 * @param string $value the value to normalize
	 *
	 * @throws InvalidArgumentException if $value is not a string
	 * @return string the normalized value
	 */
	public function normalize( $value ) {
		$value = $this->normalizer->trimBadChars( $value );

		# Remove trailing spaces (separator class) or tabs. Somehow tab is not part of Z class.
		# Remove "\r" anywhere
		$value = preg_replace( '/(\pZ|\t|\r)+$|\r/um', '', $value );
		# Trim newlines before and after the whole string
		$value = trim( $value, "\n" );
		# Normalize
		$value = $this->normalizer->cleanupToNFC( $value );

		return $value;
	}

}
