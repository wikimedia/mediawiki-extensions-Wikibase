<?php

namespace Wikibase\DataModel\Term;

use InvalidArgumentException;

/**
 * Immutable value object.
 *
 * @since 0.7.3
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Term {

	/**
	 * @var string Language code identifying the language of the text, but note that there is
	 * nothing this class can do to enforce this convention.
	 */
	private $languageCode;

	/**
	 * @var string
	 */
	private $text;

	/**
	 * @param string $languageCode Language of the text.
	 * @param string $text
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $languageCode, $text ) {
		if ( !is_string( $languageCode ) || $languageCode === '' ) {
			throw new InvalidArgumentException( '$languageCode must be a non-empty string' );
		}

		if ( !is_string( $text ) ) {
			throw new InvalidArgumentException( '$text must be a string' );
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
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return is_object( $target )
			&& get_called_class() === get_class( $target )
			&& $this->languageCode === $target->languageCode
			&& $this->text === $target->text;
	}

}
