<?php

namespace Wikibase\Repo\Tests\Rdf;

use Wikibase\Repo\Rdf\HashDedupeBag;

/**
 * @covers \Wikibase\Repo\Rdf\HashDedupeBag
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HashDedupeBagTest extends \PHPUnit\Framework\TestCase {

	public function testAlreadySeen() {
		$bag = new HashDedupeBag( 2 );

		$this->assertFalse( $bag->alreadySeen( 'XYZ' ) );
		$this->assertTrue( $bag->alreadySeen( 'XYZ' ) );
		$this->assertFalse( $bag->alreadySeen( 'XAB' ) );
		$this->assertTrue( $bag->alreadySeen( 'XAB' ) );
	}

	public function testAlreadySeenWithNamespace() {
		$bag = new HashDedupeBag( 2 );

		$this->assertFalse( $bag->alreadySeen( 'XYZ', 'A' ) );
		$this->assertFalse( $bag->alreadySeen( 'XYZ', 'B' ) );
		$this->assertTrue( $bag->alreadySeen( 'XYZ', 'A' ) );
		$this->assertTrue( $bag->alreadySeen( 'XYZ', 'B' ) );
	}

	public function testGivenConflictingHashNamespaceCombinations_alreadySeenReturnsFalse() {
		$bag = new HashDedupeBag( 2 );

		$this->assertFalse( $bag->alreadySeen( 'YZ', 'X' ) );
		$this->assertFalse( $bag->alreadySeen( 'Z', 'XY' ) );
		$this->assertFalse( $bag->alreadySeen( 'YZ', 'X' ) );
		$this->assertFalse( $bag->alreadySeen( 'Z', 'XY' ) );
	}

}
