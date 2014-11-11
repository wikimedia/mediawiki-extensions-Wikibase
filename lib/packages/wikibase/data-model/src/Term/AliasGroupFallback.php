<?php

namespace Wikibase\DataModel\Term;

use InvalidArgumentException;

/**
 * Ordered set of aliases resulting from language fall back.
 * Immutable value object.
 *
 * Duplicates and whitespace only values are removed. Values are trimmed.
 *
 * @since 2.3.0
 *
 * @licence GNU GPL v2+
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class AliasGroupFallback extends AliasGroup {

	private $actualLanguageCode;
	private $sourceLanguageCode;

	/**
	 * @param string $languageCode
	 * @param string[] $aliases
	 * @param string $actualLanguageCode fallen back to
	 * @param string|null $sourceLanguageCode for transliteration
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $languageCode, array $aliases, $actualLanguageCode, $sourceLanguageCode ) {
		parent::__construct( $languageCode, $aliases );
		$this->setActualLanguageCode( $actualLanguageCode );
		$this->setSourceLanguageCode( $sourceLanguageCode );
	}

	private function setActualLanguageCode( $actualLanguageCode ) {
		if ( !is_string( $actualLanguageCode ) ) {
			throw new InvalidArgumentException( '$actualLanguageCode needs to be a string.' );
		}

		$this->actualLanguageCode = $actualLanguageCode;
	}

	private function setSourceLanguageCode( $sourceLanguageCode ) {
		if ( !is_null( $sourceLanguageCode ) && !is_string( $sourceLanguageCode ) ) {
			throw new InvalidArgumentException( '$sourceLanguageCode needs to be null or a string.' );
		}

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
