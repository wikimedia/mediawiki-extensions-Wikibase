<?php

namespace Wikibase\Lib\Test;

use Diff\Diff;
use Diff\DiffOpChange;
use Wikibase\ClaimDifference;

/**
 * Tests for the ClaimDifference class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
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
