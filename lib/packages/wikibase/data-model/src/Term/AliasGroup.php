<?php

namespace Wikibase\DataModel\Term;

use Countable;
use InvalidArgumentException;

/**
 * Ordered set of aliases. Immutable value object.
 *
 * Duplicates and whitespace only values are removed. Values are trimmed.
 *
 * @since 0.7.3
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AliasGroup implements Countable {

	/**
	 * @var string Language code identifying the language of the aliases, but note that there is
	 * nothing this class can do to enforce this convention.
	 */
	private $languageCode;

	/**
	 * @var string[]
	 */
	private $aliases;

	/**
	 * @param string $languageCode Language of the aliases.
	 * @param string[] $aliases
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $languageCode, array $aliases = [] ) {
		if ( !is_string( $languageCode ) || $languageCode === '' ) {
			throw new InvalidArgumentException( '$languageCode must be a non-empty string' );
		}

		$this->languageCode = $languageCode;
		$this->aliases = array_values(
			array_unique(
				array_map(
					'trim',
					array_filter(
						$aliases,
						static function( $alias ) {
							if ( !is_string( $alias ) ) {
								throw new InvalidArgumentException( '$aliases must be an array of strings' );
							}

							return trim( $alias ) !== '';
						}
					)
				)
			)
		);
	}

	/**
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * @return string[]
	 */
	public function getAliases() {
		return $this->aliases;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->aliases );
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
			&& $this->aliases == $target->aliases;
	}

	/**
	 * @see Countable::count
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->aliases );
	}

}
