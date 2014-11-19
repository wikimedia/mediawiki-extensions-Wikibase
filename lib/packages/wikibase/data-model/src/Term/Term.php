<?php

namespace Wikibase\DataModel\Term;

use Comparable;
use InvalidArgumentException;

/**
 * Immutable value object.
 *
 * @since 0.7.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Term implements Comparable {

	private $languageCode;
	private $text;

	/**
	 * @param string $languageCode
	 * @param string $text
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $languageCode, $text ) {
		if ( !is_string( $languageCode ) ) {
			throw new InvalidArgumentException( '$languageCode must be a string; got ' . gettype( $languageCode ) );
		}

		if ( !is_string( $text ) ) {
			throw new InvalidArgumentException( '$text must be a string; got ' . gettype( $text ) );
		}

		$this->languageCode = $languageCode;
		$this->text = $text;
	}

	/**
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * @see Comparable::equals
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
			&& $this->languageCode === $target->getLanguageCode()
			&& $this->text === $target->getText();
	}

}
