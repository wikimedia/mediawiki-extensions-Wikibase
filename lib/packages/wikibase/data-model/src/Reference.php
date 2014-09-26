<?php

namespace Wikibase\DataModel;

use InvalidArgumentException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Snak\Snaks;

/**
 * Object that represents a single Wikibase reference.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#ReferenceRecords
 *
 * @since 0.1, instantiable since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Reference implements \Hashable, \Comparable, \Immutable, \Countable {

	/**
	 * @var Snaks
	 */
	private $snaks;

	/**
	 * An array of Snak is only supported since version 1.1.
	 *
	 * @param Snaks|Snak[]|null $snaks
	 * @throws InvalidArgumentException
	 */
	public function __construct( $snaks = null ) {
		if ( $snaks === null ) {
			$this->snaks = new SnakList();
		}
		elseif ( $snaks instanceof Snaks ) {
			$this->snaks = $snaks;
		}
		elseif ( is_array( $snaks ) ) {
			$this->snaks = new SnakList( $snaks );
		}
		else {
			throw new InvalidArgumentException();
		}
	}

	/**
	 * Returns the property snaks that make up this reference.
	 * Modification of the snaks should NOT happen through this getter.
	 *
	 * @since 0.1
	 *
	 * @return Snaks
	 */
	public function getSnaks() {
		return $this->snaks;
	}

	/**
	 * @see Countable::count
	 *
	 * @since 0.3
	 *
	 * @return integer
	 */
	public function count() {
		return count( $this->snaks );
	}

	/**
	 * @see Hashable::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		// For considering the reference snaks' property order without actually manipulating the
		// reference snaks's order, a new SnakList is generated. The new SnakList is ordered
		// by property and its hash is returned.
		$orderedSnaks = new SnakList( $this->snaks );

		$orderedSnaks->orderByProperty();

		return $orderedSnaks->getHash();
	}

	/**
	 * @see Comparable::equals
	 *
	 * The comparison is done purely value based, ignoring the order of the snaks.
	 *
	 * @since 0.3
	 *
	 * @param mixed $mixed
	 *
	 * @return boolean
	 */
	public function equals( $mixed ) {
		return $mixed instanceof self
			&& $this->snaks->equals( $mixed->snaks );
	}

}
