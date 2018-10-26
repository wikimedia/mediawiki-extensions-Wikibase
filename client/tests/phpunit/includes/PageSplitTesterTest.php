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
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructInvalidStates( $samplingRatio, array $buckets ) {
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
			[ expected => false, samplingRatio => 0, buckets => [], pageRandom => 0 ],
			[ expected => false, samplingRatio => 0, buckets => [], pageRandom => 0.5 ],
			[ expected => false, samplingRatio => 0, buckets => [ 'control', 'treatment' ], pageRandom => 0 ],
			[ expected => false, samplingRatio => 0, buckets => [ 'control', 'treatment' ], pageRandom => 0.49 ],
			[ expected => false, samplingRatio => 0, buckets => [ 'control', 'treatment' ], pageRandom => 0.99 ],
			[ expected => true, samplingRatio => 0.5, buckets => [], pageRandom => 0 ],
			[ expected => false, samplingRatio => 0.5, buckets => [], pageRandom => 0.5 ],
			[ expected => false, samplingRatio => 0.5, buckets => [], pageRandom => 0.99 ],
			[ expected => true, samplingRatio => 1, buckets => [], pageRandom => 0 ],
			[ expected => true, samplingRatio => 1, buckets => [], pageRandom => 0.5 ],
			[ expected => true, samplingRatio => 1, buckets => [], pageRandom => 0.99 ],
			[ expected => true, samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0 ],
			[ expected => true, samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0.33 ],
			[ expected => true, samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0.99 ],
			[ expected => true, samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0 ],
			[ expected => true, samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0.024 ],
			[ expected => false, samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0.025 ],
			[ expected => false, samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0.99 ]
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
			[ expected => null, samplingRatio => 0, buckets => [], pageRandom => 0 ],
			[ expected => null, samplingRatio => 0, buckets => [], pageRandom => 0.99 ],
			[ expected => 'enabled', samplingRatio => 0, buckets => [ 'enabled' ], pageRandom => 0 ],
			[ expected => 'enabled', samplingRatio => 0, buckets => [ 'enabled' ], pageRandom => 0.99 ],
			[ expected => 'control', samplingRatio => 0, buckets => [ 'control', 'treatment' ], pageRandom => 0 ],
			[ expected => 'control', samplingRatio => 0, buckets => [ 'control', 'treatment' ], pageRandom => 0.49 ],
			[ expected => 'treatment', samplingRatio => 0, buckets => [ 'control', 'treatment' ], pageRandom => 0.5 ],
			[ expected => 'treatment', samplingRatio => 0, buckets => [ 'control', 'treatment' ], pageRandom => 0.99 ],
			[ expected => null, samplingRatio => 0.5, buckets => [], pageRandom => 0 ],
			[ expected => null, samplingRatio => 1, buckets => [], pageRandom => 0 ],
			[ expected => 'a', samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0 ],
			[ expected => 'a', samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0.33 ],
			[ expected => 'b', samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0.34 ],
			[ expected => 'b', samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0.66 ],
			[ expected => 'c', samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0.67 ],
			[ expected => 'c', samplingRatio => 1, buckets => [ 'a', 'b', 'c' ], pageRandom => 0.99 ],
			[ expected => 'control', samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0 ],
			[ expected => 'control', samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0.024 ],
			[ expected => 'control', samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0.025 ],
			[ expected => 'control', samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0.24 ],
			[ expected => 'a', samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0.25 ],
			[ expected => 'c', samplingRatio => 0.1, buckets => [ 'control', 'a', 'b', 'c' ], pageRandom => 0.99 ]
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

	public function testScenarioRollout100() {
		// Drop buckets from [ 'control', 'treatment' ], a 50 / 50 split, to [ 'treatment' ] and
		// increase sampling to 100%.
		$subject = new PageSplitTester( 1, [ 'treatment' ] );
		$this->assertEquals( true, $subject->isSampled( 0.0 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.0 ) );
		$this->assertEquals( true, $subject->isSampled( 0.9 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.9 ) );
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
