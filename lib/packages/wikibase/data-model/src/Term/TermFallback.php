<?php

namespace Wikibase\DataModel\Term;

use InvalidArgumentException;

/**
 * Immutable value object.
 *
 * @since 2.4.0
 *
 * @license GPL-2.0-or-later
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class TermFallback extends Term {

	/**
	 * @var string Actual language of the text.
	 */
	private $actualLanguageCode;

	/**
	 * @var string|null Source language if the text is a transliteration.
	 */
	private $sourceLanguageCode;

	/**
	 * @param string $requestedLanguageCode Requested language, not necessarily the language of the
	 * text.
	 * @param string $text
	 * @param string $actualLanguageCode Actual language of the text.
	 * @param string|null $sourceLanguageCode Source language if the text is a transliteration.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $requestedLanguageCode, $text, $actualLanguageCode, $sourceLanguageCode ) {
		parent::__construct( $requestedLanguageCode, $text );

		if ( !is_string( $actualLanguageCode ) || $actualLanguageCode === '' ) {
			throw new InvalidArgumentException( '$actualLanguageCode must be a non-empty string' );
		}

		if ( !( $sourceLanguageCode === null
			|| ( is_string( $sourceLanguageCode ) && $sourceLanguageCode !== '' )
		) ) {
			throw new InvalidArgumentException( '$sourceLanguageCode must be a non-empty string or null' );
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
	 * @return string|null
	 */
	public function getSourceLanguageCode() {
		return $this->sourceLanguageCode;
	}

	/**
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& parent::equals( $target )
			&& $this->actualLanguageCode === $target->actualLanguageCode
			&& $this->sourceLanguageCode === $target->sourceLanguageCode;
	}

}
