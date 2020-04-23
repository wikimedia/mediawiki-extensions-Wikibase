<?php

namespace Wikibase\Repo\Parsers;

use InvalidArgumentException;
use ValueParsers\Normalizers\StringNormalizer as ValueParsersStringNormalizer;
use Wikibase\Lib\StringNormalizer as WikibaseLibStringNormalizer;

/**
 * Adapter implementing ValueParsers\Normalizers\StringNormalizer based on \Wikibase\Lib\StringNormalizer.
 * Used to perform string normalization in StringParser.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseStringValueNormalizer implements ValueParsersStringNormalizer {

	/**
	 * @var WikibaseLibStringNormalizer
	 */
	private $normalizer;

	/**
	 * @param WikibaseLibStringNormalizer $normalizer
	 */
	public function __construct( WikibaseLibStringNormalizer $normalizer ) {
		$this->normalizer = $normalizer;
	}

	/**
	 * Trims leading and trailing whitespace and performs unicode normalization
	 * by calling \Wikibase\Lib\StringNormalizer::trimToNFC().
	 *
	 * @see StringNormalizer::normalize()
	 * @see \Wikibase\Lib\StringNormalizer::trimToNFC()
	 *
	 * @param string $value the value to normalize
	 *
	 * @throws InvalidArgumentException if $value is not a string
	 * @return string the normalized value
	 */
	public function normalize( $value ) {
		return $this->normalizer->trimToNFC( $value );
	}

}
