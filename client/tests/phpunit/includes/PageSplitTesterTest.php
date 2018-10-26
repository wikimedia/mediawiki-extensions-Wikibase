<?php

namespace Wikibase\Client\Tests;

use InvalidArgumentException;
use PHPUnit4And6Compat;

use Wikibase\Client\PageSplitTester;

/**
 * @covers \Wikibase\Client\PageSplitTester
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PageSplitTesterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider providerConstructInvalidStates
	 */
	public function testConstructInvalidStates( $samplingRatio, array $buckets ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new PageSplitTester( $samplingRatio, $buckets );
	}

	public function providerConstructInvalidStates() {
		return [
			[ -1, [] ],
			[ -1.1, [] ],
			[ 1.1, [] ],
			[ 2, [] ]
		];
	}

	/**
	 * @dataProvider providerConstructValidStates
	 */
	public function testConstructValidStates( $samplingRatio, array $buckets ) {
		new PageSplitTester( $samplingRatio, $buckets );
		$this->assertEquals( true, true );
	}

	public function providerConstructValidStates() {
		return [
			[ 0, [] ],
			[ 0, [ 'control', 'treatment' ] ],
			[ 0.5, [] ],
			[ 1, [] ],
			[ 1, [ 'control', 'treatment' ] ],
			[ 0.1, [ 'control', 'a', 'b', 'c' ] ],
		];
	}

	/**
	 * @dataProvider providerIsSample
	 */
	public function testIsSampled( $expected, $samplingRatio, array $buckets, $pageRandom ) {
		$subject = new PageSplitTester( $samplingRatio, $buckets );
		$this->assertEquals( $expected, $subject->isSampled( $pageRandom ) );
	}

	public function providerIsSample() {
		return [
			[ false, 0, [], 0 ],
			[ false, 0, [], 0.5 ],
			[ false, 0, [ 'control', 'treatment' ], 0 ],
			[ false, 0, [ 'control', 'treatment' ], 0.49 ],
			[ false, 0, [ 'control', 'treatment' ], 0.99 ],
			[ true, 0.5, [], 0 ],
			[ false, 0.5, [], 0.5 ],
			[ false, 0.5, [], 0.99 ],
			[ true, 1, [], 0 ],
			[ true, 1, [], 0.5 ],
			[ true, 1, [], 0.99 ],
			[ true, 1, [ 'a', 'b', 'c' ], 0 ],
			[ true, 1, [ 'a', 'b', 'c' ], 0.33 ],
			[ true, 1, [ 'a', 'b', 'c' ], 0.99 ],
			[ true, 0.1, [ 'control', 'a', 'b', 'c' ], 0 ],
			[ true, 0.1, [ 'control', 'a', 'b', 'c' ], 0.024 ],
			[ false, 0.1, [ 'control', 'a', 'b', 'c' ], 0.025 ],
			[ false, 0.1, [ 'control', 'a', 'b', 'c' ], 0.99 ]
		];
	}

	/**
	 * @dataProvider providerGetBucket
	 */
	public function testGetBucket( $expected, $samplingRatio, array $buckets, $pageRandom ) {
		$subject = new PageSplitTester( $samplingRatio, $buckets );
		$this->assertEquals( $expected, $subject->getBucket( $pageRandom ) );
	}

	public function providerGetBucket() {
		return [
			[ null, 0, [], 0 ],
			[ null, 0, [], 0.99 ],
			[ 'enabled', 0, [ 'enabled' ], 0 ],
			[ 'enabled', 0, [ 'enabled' ], 0.99 ],
			[ 'control', 0, [ 'control', 'treatment' ], 0 ],
			[ 'control', 0, [ 'control', 'treatment' ], 0.49 ],
			[ 'treatment', 0, [ 'control', 'treatment' ], 0.5 ],
			[ 'treatment', 0, [ 'control', 'treatment' ], 0.99 ],
			[ null, 0.5, [], 0 ],
			[ null, 1, [], 0 ],
			[ 'a', 1, [ 'a', 'b', 'c' ], 0 ],
			[ 'a', 1, [ 'a', 'b', 'c' ], 0.33 ],
			[ 'b', 1, [ 'a', 'b', 'c' ], 0.34 ],
			[ 'b', 1, [ 'a', 'b', 'c' ], 0.66 ],
			[ 'c', 1, [ 'a', 'b', 'c' ], 0.67 ],
			[ 'c', 1, [ 'a', 'b', 'c' ], 0.99 ],
			[ 'control', 0.1, [ 'control', 'a', 'b', 'c' ], 0 ],
			[ 'control', 0.1, [ 'control', 'a', 'b', 'c' ], 0.024 ],
			[ 'control', 0.1, [ 'control', 'a', 'b', 'c' ], 0.025 ],
			[ 'control', 0.1, [ 'control', 'a', 'b', 'c' ], 0.24 ],
			[ 'a', 0.1, [ 'control', 'a', 'b', 'c' ], 0.25 ],
			[ 'c', 0.1, [ 'control', 'a', 'b', 'c' ], 0.99 ]
		];
	}

	public function testScenarioAb10() {
		// A/B test with 10% sampling.
		$subject = new PageSplitTester( 0.1, [ 'a', 'b' ] );
		$this->assertEquals( true, $subject->isSampled( 0.00 ) );
		$this->assertEquals( 'a', $subject->getBucket( 0.00 ) );
		$this->assertEquals( true, $subject->isSampled( 0.01 ) );
		$this->assertEquals( 'a', $subject->getBucket( 0.01 ) );
		$this->assertEquals( false, $subject->isSampled( 0.05 ) );
		$this->assertEquals( 'a', $subject->getBucket( 0.05 ) );
		$this->assertEquals( false, $subject->isSampled( 0.1 ) );
		$this->assertEquals( 'a', $subject->getBucket( 0.1 ) );
		$this->assertEquals( true, $subject->isSampled( 0.5 ) );
		$this->assertEquals( 'b', $subject->getBucket( 0.5 ) );
		$this->assertEquals( true, $subject->isSampled( 0.51 ) );
		$this->assertEquals( 'b', $subject->getBucket( 0.51 ) );
		$this->assertEquals( false, $subject->isSampled( 0.9 ) );
		$this->assertEquals( 'b', $subject->getBucket( 0.9 ) );
	}

	public function testScenarioRollout1() {
		// Rollout with 1% sampling.
		$subject = new PageSplitTester( 0.01, [] );
		$this->assertEquals( true, $subject->isSampled( 0.00 ) );
		$this->assertEquals( null, $subject->getBucket( 0.00 ) );
		$this->assertEquals( true, $subject->isSampled( 0.005 ) );
		$this->assertEquals( null, $subject->getBucket( 0.005 ) );
		$this->assertEquals( false, $subject->isSampled( 0.01 ) );
		$this->assertEquals( null, $subject->getBucket( 0.01 ) );
		$this->assertEquals( false, $subject->isSampled( 0.05 ) );
		$this->assertEquals( null, $subject->getBucket( 0.05 ) );
		$this->assertEquals( false, $subject->isSampled( 0.1 ) );
		$this->assertEquals( null, $subject->getBucket( 0.1 ) );
		$this->assertEquals( false, $subject->isSampled( 0.5 ) );
		$this->assertEquals( null, $subject->getBucket( 0.5 ) );
		$this->assertEquals( false, $subject->isSampled( 0.9 ) );
		$this->assertEquals( null, $subject->getBucket( 0.9 ) );
	}

	public function testScenarioSplit50() {
		// A/B/C split test with 50% sampling.
		$subject = new PageSplitTester( 0.5, [ 'a', 'b', 'c' ] );

		$sampled = 0;
		$buckets = [ 'a' => 0, 'b' => 0, 'c' => 0 ];
		$iterations = 100000;
		for ( $i = 0; $i < $iterations; ++$i ) {
			$pageRandom = wfRandom();
			$sampled += $subject->isSampled( $pageRandom ) ? 1 : 0;
			$buckets[ $subject->getBucket( $pageRandom ) ]++;
		}

		$this->assertEquals( 0.50, $sampled / $iterations, '', 0.01 );
		$this->assertEquals( 0.33, $buckets['a'] / $iterations, '', 0.01 );
		$this->assertEquals( 0.33, $buckets['b'] / $iterations, '', 0.01 );
		$this->assertEquals( 0.33, $buckets['c'] / $iterations, '', 0.01 );
	}

}
