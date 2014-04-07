<?php

namespace Wikibase\DataModel\Term;

use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;

/**
 * List of terms. Immutable.
 *
 * If multiple terms with the same language code are provided, only the last one will be retained.
 *
 * @since 0.7.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermList implements Countable, IteratorAggregate {

	/**
	 * @var Term[]
	 */
	private $terms = array();

	/**
	 * @param Term[] $terms
	 */
	public function __construct( array $terms ) {
		foreach ( $terms as $term ) {
			$this->terms[$term->getLanguageCode()] = $term;
		}
	}

	/**
	 * @see Countable::count
	 * @return int
	 */
	public function count() {
		return count( $this->terms );
	}

	/**
	 * Returns an array with language codes as keys and the term text as values.
	 *
	 * @return string[]
	 */
	public function toTextArray() {
		$array = array();

		foreach ( $this->terms as $term ) {
			$array[$term->getLanguageCode()] = $term->getText();
		}

		return $array;
	}

	/**
	 * @see IteratorAggregate::getIterator
	 * @return Traversable
	 */
	public function getIterator() {
		return new \ArrayIterator( $this->terms );
	}

	/**
	 * @param $languageCode
	 *
	 * @return AliasGroup
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function getByLanguage( $languageCode ) {
		if ( !is_string( $languageCode ) ) {
			throw new InvalidArgumentException( '$languageCode should be a string' );
		}

		if ( !array_key_exists( $languageCode, $this->terms ) ) {
			throw new OutOfBoundsException(
				'There is no Term with language code "' . $languageCode . '" in the list'
			);
		}

		return $this->terms[$languageCode];
	}

}
