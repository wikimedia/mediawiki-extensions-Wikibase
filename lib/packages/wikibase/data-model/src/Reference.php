<?php

namespace Wikibase\DataModel;

use Countable;
use InvalidArgumentException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Object that represents a single Wikibase reference.
 * See https://www.mediawiki.org/wiki/Wikibase/DataModel#ReferenceRecords
 *
 * @since 0.1, instantiable since 0.4
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Reference implements Countable {

	/**
	 * @var SnakList
	 */
	private $snaks;

	/**
	 * An array of Snak objects is only supported since version 1.1.
	 *
	 * @param Snak[]|SnakList $snaks
	 * @throws InvalidArgumentException
	 */
	public function __construct( $snaks = [] ) {
		if ( is_array( $snaks ) ) {
			$snaks = new SnakList( $snaks );
		}

		if ( !( $snaks instanceof SnakList ) ) {
			throw new InvalidArgumentException( '$snaks must be an array or an instance of SnakList' );
		}

		$this->snaks = $snaks;
	}

	/**
	 * Returns the property snaks that make up this reference.
	 * Modification of the snaks should NOT happen through this getter.
	 *
	 * @since 0.1
	 *
	 * @return SnakList
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
	public function count(): int {
		return count( $this->snaks );
	}

	/**
	 * @since 2.6
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->snaks->isEmpty();
	}

	/**
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
	 *
	 * The comparison is done purely value based, ignoring the order of the snaks.
	 *
	 * @since 0.3
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
			&& $this->snaks->equals( $target->snaks );
	}

}
