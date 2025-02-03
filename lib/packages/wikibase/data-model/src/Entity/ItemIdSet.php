<?php

namespace Wikibase\DataModel\Entity;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Immutable set of ItemId objects. Unordered and unique.
 * The constructor filters out duplicates.
 *
 * @since 0.7.4
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemIdSet implements IteratorAggregate, Countable {

	/**
	 * @var ItemId[]
	 */
	private $ids = [];

	/**
	 * @param ItemId[] $ids
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $ids = [] ) {
		foreach ( $ids as $id ) {
			if ( !( $id instanceof ItemId ) ) {
				throw new InvalidArgumentException( 'Every element in $ids must be an instance of ItemId' );
			}

			$this->ids[$id->getNumericId()] = $id;
		}
	}

	/**
	 * @see Countable::count
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->ids );
	}

	/**
	 * @see IteratorAggregate::getIterator
	 *
	 * @return Iterator|ItemId[]
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator( $this->ids );
	}

	/**
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function getSerializations() {
		return array_values(
			array_map(
				static function( ItemId $id ) {
					return $id->getSerialization();
				},
				$this->ids
			)
		);
	}

	/**
	 * @param ItemId $id
	 *
	 * @return bool
	 */
	public function has( ItemId $id ) {
		return array_key_exists( $id->getNumericId(), $this->ids );
	}

	/**
	 * @see Countable::equals
	 *
	 * @since 0.1
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
			&& $this->ids == $target->ids;
	}

}
