<?php

namespace Wikibase\Repo;

/**
 * Lazy and potentially infinite version of PHPs native range() function (without $step support).
 *
 * @license GPL-2.0-or-later
 */
class RangeTraversable implements \IteratorAggregate {

	private $startingNumber;
	private $inclusiveUpperBound;

	/**
	 * @param int $startingNumber
	 * @param int|null $inclusiveUpperBound
	 */
	public function __construct( $startingNumber = 1, $inclusiveUpperBound = null ) {
		$this->startingNumber = $startingNumber;
		$this->inclusiveUpperBound = $inclusiveUpperBound;
	}

	public function getIterator() {
		$number = $this->startingNumber;

		if ( $this->inclusiveUpperBound === null ) {
			while ( true ) {
				yield $number++;
			}
		} else {
			while ( $number <= $this->inclusiveUpperBound ) {
				yield $number++;
			}
		}
	}

}
