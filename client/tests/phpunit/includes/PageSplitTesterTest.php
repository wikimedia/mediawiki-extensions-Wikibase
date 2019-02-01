<?php

namespace Wikibase\Client\Tests;

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
	 * @expectedException \Wikimedia\Assert\ParameterAssertionException
	 */
	public function testConstructInvalidStates( $samplingRatio, array $buckets, $msg ) {
		new PageSplitTester( $samplingRatio, $buckets );
	}

	public function providerConstructInvalidStates() {
		return [
			[ -1, [], 'Out of range: negative integer' ],
			[ -0.1, [], 'Out of range: negative float' ],
			[ 1.1, [], 'Out of range: positive float' ],
			[ 2, [], 'Out of range: positive integer' ]
		];
	}

	/**
	 * @dataProvider providerConstructValidStates
	 */
	public function testConstructValidStates( $samplingRatio, array $buckets, $msg ) {
		new PageSplitTester( $samplingRatio, $buckets );
		$this->assertEquals( true, true, $msg );
	}

	public function providerConstructValidStates() {
		return [
			[ 0, [], 'Defaults' ],
			[ 0, [ 'control', 'treatment' ], 'No sampling, A/B test' ],
			[ 0.5, [], '50% sampling, no buckets' ],
			[ 1, [], '100% sampling, no buckets' ],
			[ 1, [ 'control', 'treatment' ], '100% sampling, A/B test' ],
			[ 0.1, [ 'control', 'a', 'b', 'c' ], '10% sampling, split test' ]
		];
	}

	/**
	 * @dataProvider providerIsSample
	 */
	public function testIsSampled( $expected, $samplingRatio, array $buckets, $pageRandom, $msg ) {
		$subject = new PageSplitTester( $samplingRatio, $buckets );
		$this->assertEquals( $expected, $subject->isSampled( $pageRandom ), $msg );
	}

	public function providerIsSample() {
		return [
			[ false, 0, [], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ false, 0, [], 0.5, 'scaledRandom: 0.5, bucket: 0, sample: 0' ],
			[ false, 0, [ 'control', 'treatment' ], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ false, 0, [ 'control', 'treatment' ], 0.49, 'scaledRandom: 0.98, bucket: 0, sample: 0.98' ],
			[ false, 0, [ 'control', 'treatment' ], 0.99, 'scaledRandom: 1.98; bucket: 1, sample: 0.98' ],
			[ true, 0.5, [], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ false, 0.5, [], 0.5, 'scaledRandom: 0.5, bucket: 0, sample: 0.5' ],
			[ false, 0.5, [], 0.99, 'scaledRandom: 0.99, bucket: 0, sample: 0.99' ],
			[ true, 1, [], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ true, 1, [], 0.5, 'scaledRandom: 0.5, bucket: 0, sample: 0.5' ],
			[ true, 1, [], 0.99, 'scaledRandom: 0.99, bucket: 0, sample: 0.99' ],
			[ true, 1, [ 'a', 'b', 'c' ], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ true, 1, [ 'a', 'b', 'c' ], 0.33, 'scaledRandom: 0.99, bucket: 0, sample: 0.99' ],
			[ true, 1, [ 'a', 'b', 'c' ], 0.99, 'scaledRandom: 2.97, bucket: 2, sample: 0.97' ],
			[ true, 0.1, [ 'control', 'a', 'b', 'c' ], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ true, 0.1, [ 'control', 'a', 'b', 'c' ], 0.024, 'scaledRandom: 0.096, bucket: 0, sample: 0.096' ],
			[ false, 0.1, [ 'control', 'a', 'b', 'c' ], 0.025, 'scaledRandom: 0.10, bucket: 0, sample: 0.10' ],
			[ false, 0.1, [ 'control', 'a', 'b', 'c' ], 0.99, 'scaledRandom: 3.96, bucket: 3, sample: 0.96' ]
		];
	}

	/**
	 * @dataProvider providerGetBucket
	 */
	public function testGetBucket( $expected, $samplingRatio, array $buckets, $pageRandom, $msg ) {
		$subject = new PageSplitTester( $samplingRatio, $buckets );
		$this->assertEquals( $expected, $subject->getBucket( $pageRandom ), $msg );
	}

	public function providerGetBucket() {
		return [
			[ null, 0, [], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ null, 0, [], 0.99, 'scaledRandom: 0, bucket: 0, sample: 0.99' ],
			[ 'enabled', 0, [ 'enabled' ], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ 'enabled', 0, [ 'enabled' ], 0.99, 'scaledRandom: 0, bucket: 0, sample: 0.99' ],
			[ 'control', 0, [ 'control', 'treatment' ], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ 'control', 0, [ 'control', 'treatment' ], 0.49, 'scaledRandom: 0.98, bucket: 0, sample: 0.98' ],
			[ 'treatment', 0, [ 'control', 'treatment' ], 0.5, 'scaledRandom: 1, bucket: 1, sample: 0' ],
			[ 'treatment', 0, [ 'control', 'treatment' ], 0.99, 'scaledRandom: 1.98, bucket: 1, sample: 0.98' ],
			[ null, 0.5, [], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ null, 1, [], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ 'a', 1, [ 'a', 'b', 'c' ], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ 'a', 1, [ 'a', 'b', 'c' ], 0.33, 'scaledRandom: 0.99, bucket: 0, sample: 0.99' ],
			[ 'b', 1, [ 'a', 'b', 'c' ], 0.34, 'scaledRandom: 1.02, bucket: 1, sample: 0.02' ],
			[ 'b', 1, [ 'a', 'b', 'c' ], 0.66, 'scaledRandom: 1.98, bucket: 1, sample: 0.98' ],
			[ 'c', 1, [ 'a', 'b', 'c' ], 0.67, 'scaledRandom: 2.01, bucket: 2, sample: 0.01' ],
			[ 'c', 1, [ 'a', 'b', 'c' ], 0.99, 'scaledRandom: 2.97, bucket: 2, sample: 0.97' ],
			[ 'control', 0.1, [ 'control', 'a', 'b', 'c' ], 0, 'scaledRandom: 0, bucket: 0, sample: 0' ],
			[ 'control', 0.1, [ 'control', 'a', 'b', 'c' ], 0.024, 'scaledRandom: 0.096, bucket: 0, sample: 0.096' ],
			[ 'control', 0.1, [ 'control', 'a', 'b', 'c' ], 0.025, 'scaledRandom: 0.1, bucket: 0, sample: 0.1' ],
			[ 'control', 0.1, [ 'control', 'a', 'b', 'c' ], 0.24, 'scaledRandom: 0.96, bucket: 0, sample: 0.96' ],
			[ 'a', 0.1, [ 'control', 'a', 'b', 'c' ], 0.25, 'scaledRandom: 1, bucket: 1, sample: 0' ],
			[ 'c', 0.1, [ 'control', 'a', 'b', 'c' ], 0.99, 'scaledRandom: 3.96, bucket: 3, sample: 0.96' ]
		];
	}

	public function testScenarioAb1() {
		// "control" / "treatment" A/B test with 1% sampling.
		$sampling = 0.01;
		$buckets = [ /*A*/ 'control', /*B*/ 'treatment' ];
		$subject = new PageSplitTester( $sampling, $buckets );

		// Supply page_random at different values. [0, .005) and [.5, .505) are sampled,
		// [.005, .5) and [.505, 1) are unsampled.
		$this->assertEquals( true, $subject->isSampled( 0.000 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.001 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.002 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.003 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.004 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.005 ) ); // Unsampled
		$this->assertEquals( false, $subject->isSampled( 0.008 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.009 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.010 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.011 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.012 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.013 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.015 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.018 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.019 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.100 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.200 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.490 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.500 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.501 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.502 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.503 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.504 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.505 ) ); // Unsampled
		$this->assertEquals( false, $subject->isSampled( 0.508 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.509 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.510 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.800 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.999 ) ); // ''

		// Supply page_random at different values. [0, .5) are "control", [.5, 1) are "treatment".
		$this->assertEquals( 'control', $subject->getBucket( 0.000 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.001 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.002 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.003 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.004 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.005 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.008 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.009 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.010 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.011 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.012 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.013 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.015 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.018 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.019 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.100 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.200 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.490 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.500 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.501 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.502 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.503 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.504 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.505 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.508 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.509 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.510 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.800 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.999 ) );

		// Thus, pages sampled at 1% in "treatment" may be found for page_random in [.5, .505).
	}

	public function testScenarioAb5() {
		// "control" / "treatment" A/B test with 1% sampling.
		$sampling = 0.05;
		$buckets = [ /*A*/ 'control', /*B*/ 'treatment' ];
		$subject = new PageSplitTester( $sampling, $buckets );

		// Supply page_random at different values. [0, .025) and [.5, .525) are sampled,
		// [.025, .5) and [.525, 1) are unsampled.
		$this->assertEquals( true, $subject->isSampled( 0.000 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.001 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.002 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.003 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.004 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.005 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.008 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.009 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.010 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.011 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.012 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.013 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.015 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.018 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.019 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.024 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.025 ) ); // Unsampled
		$this->assertEquals( false, $subject->isSampled( 0.100 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.200 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.490 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.500 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.501 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.502 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.503 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.504 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.505 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.508 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.509 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.510 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.524 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.525 ) ); // Unsampled
		$this->assertEquals( false, $subject->isSampled( 0.800 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.999 ) ); // ''

		// Supply page_random at different values. [0, .5) are "control", [.5, 1) are "treatment".
		$this->assertEquals( 'control', $subject->getBucket( 0.000 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.001 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.002 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.003 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.004 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.005 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.008 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.009 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.010 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.011 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.012 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.013 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.015 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.018 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.019 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.100 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.200 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.490 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.500 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.501 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.502 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.503 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.504 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.505 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.508 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.509 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.510 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.800 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.999 ) );

		// Thus, pages sampled at 5% in "treatment" may be found for page_random in [.5, .525).
	}

	public function testScenarioAb25() {
		// "control" / "treatment" A/B test with 1% sampling.
		$sampling = 0.25;
		$buckets = [ /*A*/ 'control', /*B*/ 'treatment' ];
		$subject = new PageSplitTester( $sampling, $buckets );

		// Supply page_random at different values. [0, .125) and [.5, .625) are sampled,
		// [.125, .5) and [.625, 1) are unsampled.
		$this->assertEquals( true, $subject->isSampled( 0.000 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.001 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.002 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.003 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.004 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.005 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.008 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.009 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.010 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.011 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.012 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.013 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.015 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.018 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.019 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.024 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.025 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.100 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.124 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.125 ) ); // Unsampled
		$this->assertEquals( false, $subject->isSampled( 0.200 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.490 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.500 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.501 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.502 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.503 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.504 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.505 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.508 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.509 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.510 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.524 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.525 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.624 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.625 ) ); // Unsampled
		$this->assertEquals( false, $subject->isSampled( 0.800 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.999 ) ); // ''

		// Supply page_random at different values. [0, .5) are "control", [.5, 1) are "treatment".
		$this->assertEquals( 'control', $subject->getBucket( 0.000 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.001 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.002 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.003 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.004 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.005 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.008 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.009 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.010 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.011 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.012 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.013 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.015 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.018 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.019 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.100 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.200 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.490 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.500 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.501 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.502 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.503 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.504 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.505 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.508 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.509 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.510 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.800 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.999 ) );

		// Thus, pages sampled at 25% in "treatment" may be found for page_random in [.5, .625).
	}

	public function testScenarioAb50() {
		// "control" / "treatment" A/B test with 1% sampling.
		$sampling = 0.5;
		$buckets = [ /*A*/ 'control', /*B*/ 'treatment' ];
		$subject = new PageSplitTester( $sampling, $buckets );

		// Supply page_random at different values. [0, .25) and [.5, .75) are sampled,
		// [.25, .5) and [.75, 1) are unsampled.
		$this->assertEquals( true, $subject->isSampled( 0.000 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.001 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.002 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.003 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.004 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.005 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.008 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.009 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.010 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.011 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.012 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.013 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.015 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.018 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.019 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.024 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.025 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.100 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.124 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.125 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.200 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.24 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.25 ) ); // 'Unsampled
		$this->assertEquals( false, $subject->isSampled( 0.490 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.500 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.501 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.502 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.503 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.504 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.505 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.508 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.509 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.510 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.524 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.525 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.624 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.625 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.74 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.75 ) ); // Unsampled
		$this->assertEquals( false, $subject->isSampled( 0.800 ) ); // ''
		$this->assertEquals( false, $subject->isSampled( 0.999 ) ); // ''

		// Supply page_random at different values. [0, .5) are "control", [.5, 1) are "treatment".
		$this->assertEquals( 'control', $subject->getBucket( 0.000 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.001 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.002 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.003 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.004 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.005 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.008 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.009 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.010 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.011 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.012 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.013 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.015 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.018 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.019 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.100 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.200 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.490 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.500 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.501 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.502 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.503 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.504 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.505 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.508 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.509 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.510 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.800 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.999 ) );

		// Thus, pages sampled at 50% in "treatment" may be found for page_random in [.5, .75).
	}

	public function testScenarioAb100() {
		// "control" / "treatment" A/B test with 1% sampling.
		$sampling = 1;
		$buckets = [ /*A*/ 'control', /*B*/ 'treatment' ];
		$subject = new PageSplitTester( $sampling, $buckets );

		// Supply page_random at different values. [0, 1) are sampled.
		$this->assertEquals( true, $subject->isSampled( 0.000 ) ); // Sampled
		$this->assertEquals( true, $subject->isSampled( 0.001 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.002 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.003 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.004 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.005 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.008 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.009 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.010 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.011 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.012 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.013 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.015 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.018 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.019 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.024 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.025 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.100 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.124 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.125 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.200 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.24 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.25 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.490 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.500 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.501 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.502 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.503 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.504 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.505 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.508 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.509 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.510 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.524 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.525 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.624 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.625 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.74 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.75 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.800 ) ); // ''
		$this->assertEquals( true, $subject->isSampled( 0.999 ) ); // ''

		// Supply page_random at different values. [0, .5) are "control", [.5, 1) are "treatment".
		$this->assertEquals( 'control', $subject->getBucket( 0.000 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.001 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.002 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.003 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.004 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.005 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.008 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.009 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.010 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.011 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.012 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.013 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.015 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.018 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.019 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.100 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.200 ) );
		$this->assertEquals( 'control', $subject->getBucket( 0.490 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.500 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.501 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.502 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.503 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.504 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.505 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.508 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.509 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.510 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.800 ) );
		$this->assertEquals( 'treatment', $subject->getBucket( 0.999 ) );

		// Thus, pages sampled at 50% in "treatment" may be found for page_random in [.5, 1).
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

	// This is probabilistic and may cause a false positive with a very low probability. Increase the
	// iterations or tolerance if this occurs.
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
