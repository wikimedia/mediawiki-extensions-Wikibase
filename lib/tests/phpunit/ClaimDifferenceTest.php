<?php

namespace Wikibase\Lib\Test;

use Diff\Diff;
use Diff\DiffOpChange;
use Wikibase\ClaimDifference;

/**
 * @covers Wikibase\ClaimDifference
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimDifferenceTest extends \MediaWikiTestCase {

	public function atomicClaimDifferenceProvider() {
		$claimDifferenceObjects = array();
		$changeOp = new DiffOpChange( "old", "new" );
		$diff = new Diff( array ( $changeOp ) );

		$claimDifferenceObjects[] = new ClaimDifference( $changeOp );
		$claimDifferenceObjects[] = new ClaimDifference( null, $diff );
		$claimDifferenceObjects[] = new ClaimDifference( null, null, $diff );
		$claimDifferenceObjects[] = new ClaimDifference( null, null, null, $changeOp );

		return $this->arrayWrap( $claimDifferenceObjects );
	}

	public function nonAtomicClaimDifferenceProvider() {
		$claimDifferenceObjects = array();
		$changeOp = new DiffOpChange( "old", "new" );
		$diff = new Diff( array ( $changeOp ) );

		$claimDifferenceObjects[] = new ClaimDifference();
		$claimDifferenceObjects[] = new ClaimDifference( $changeOp, $diff, null, null );
		$claimDifferenceObjects[] = new ClaimDifference( $changeOp, null, $diff, null );
		$claimDifferenceObjects[] = new ClaimDifference( $changeOp, null, null, $changeOp );
		$claimDifferenceObjects[] = new ClaimDifference( $changeOp, $diff, $diff, null );
		$claimDifferenceObjects[] = new ClaimDifference( $changeOp, $diff, null, $changeOp );
		$claimDifferenceObjects[] = new ClaimDifference( $changeOp, null, $diff, $changeOp );
		$claimDifferenceObjects[] = new ClaimDifference( $changeOp, $diff, $diff, $changeOp );
		$claimDifferenceObjects[] = new ClaimDifference( null, null, $diff, $changeOp );
		$claimDifferenceObjects[] = new ClaimDifference( null, $diff, null, $changeOp );
		$claimDifferenceObjects[] = new ClaimDifference( null, $diff, $diff, null );
		$claimDifferenceObjects[] = new ClaimDifference( null, $diff, $diff, $changeOp );
		$claimDifferenceObjects[] = new ClaimDifference( null, new Diff(), null, null );
		$claimDifferenceObjects[] = new ClaimDifference( null, new Diff(), null, null );

		return $this->arrayWrap( $claimDifferenceObjects );
	}

	/**
	 * @dataProvider atomicClaimDifferenceProvider
	 *
	 * @param ClaimDifference $claimDifference
	 */
	public function testIsAtomic( $claimDifference ) {
		$this->assertTrue( $claimDifference->isAtomic(), "isAtomic reports claimDifference as non-atomic, although it is" );
	}

	/**
	 * @dataProvider nonAtomicClaimDifferenceProvider
	 * 
	 * @param ClaimDifference $claimDifference
	 */
	public function testIsNotAtomic( $claimDifference ) {
		$this->assertFalse( $claimDifference->isAtomic(), "isAtomic reports claimDifference as atomic, although it is not" );
	}

}
