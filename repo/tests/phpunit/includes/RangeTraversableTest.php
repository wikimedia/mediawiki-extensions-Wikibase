<?php

namespace Wikibase\Repo\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RangeTraversable;

/**
 * @covers \Wikibase\Repo\RangeTraversable
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RangeTraversableTest extends TestCase {

	public function testTraversingWithDefaults() {
		$this->assertFirstElementsEqualTo(
			[ 1, 2, 3, 4, 5 ],
			new RangeTraversable()
		);
	}

	public function assertFirstElementsEqualTo( array $expected, \Traversable $actual ) {
		$this->assertSame(
			$expected,
			iterator_to_array(
				new \LimitIterator(
					new \IteratorIterator( $actual ),
					0,
					count( $expected )
				)
			)
		);
	}

	public function testStartAtZero() {
		$this->assertFirstElementsEqualTo(
			[ 0, 1, 2, 3, 4, 5 ],
			new RangeTraversable( 0 )
		);
	}

	public function testStartAtMinusOne() {
		$this->assertFirstElementsEqualTo(
			[ -1, 0, 1, 2, 3, 4, 5 ],
			new RangeTraversable( -1 )
		);
	}

	public function testUpperBoundAt3() {
		$this->assertSame(
			[ 1, 2, 3 ],
			iterator_to_array( new RangeTraversable( 1, 3 ) )
		);
	}

	public function testUpperBoundBelowStart() {
		$this->assertSame(
			[],
			iterator_to_array( new RangeTraversable( 5, 3 ) )
		);
	}

}
