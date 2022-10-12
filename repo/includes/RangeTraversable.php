<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use Traversable;

/**
 * Lazy and potentially infinite version of PHPs native range() function (without $step support).
 *
 * @license GPL-2.0-or-later
 */
class RangeTraversable implements \IteratorAggregate {

	/** @var int */
	private $startingNumber;
	/** @var int|null */
	private $inclusiveUpperBound;

	public function __construct( int $startingNumber = 1, int $inclusiveUpperBound = null ) {
		$this->startingNumber = $startingNumber;
		$this->inclusiveUpperBound = $inclusiveUpperBound;
	}

	public function getIterator(): Traversable {
		$number = $this->startingNumber;

		if ( $this->inclusiveUpperBound === null ) {
			// @phan-suppress-next-line PhanInfiniteLoop
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
