<?php

namespace Wikibase\DataModel\Term;

use InvalidArgumentException;

/**
 * Immutable value object.
 *
 * @since 2.3.0
 *
 * @licence GNU GPL v2+
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class TermFallback extends Term {

	private $actualLanguageCode;
	private $sourceLanguageCode;

	/**
	 * @param string $languageCode
	 * @param string $text
	 * @param string $actualLanguageCode fallen back to
	 * @param string|null $sourceLanguageCode for transliteration
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $languageCode, $text, $actualLanguageCode, $sourceLanguageCode ) {
		parent::__construct( $languageCode, $text );
		if ( !is_string( $actualLanguageCode ) ) {
			throw new InvalidArgumentException( '$actualLanguageCode should be a string' );
		}

		if ( !is_null( $sourceLanguageCode ) && !is_string( $sourceLanguageCode ) ) {
			throw new InvalidArgumentException( '$sourceLanguageCode should be a string or null' );
		}

		$this->actualLanguageCode = $actualLanguageCode;
		$this->sourceLanguageCode = $sourceLanguageCode;
	}

	/**
	 * @return string
	 */
	public function getActualLanguageCode() {
		return $this->actualLanguageCode;
	}

	/**
	 * @return string
	 */
	public function getSourceLanguageCode() {
		return $this->sourceLanguageCode;
	}

	/**
	 * @see Comparable::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		return $target instanceof self
			&& parent::equals( $target )
			&& $this->actualLanguageCode === $target->getActualLanguageCode()
			&& $this->sourceLanguageCode === $target->getSourceLanguageCode();
	}

}
