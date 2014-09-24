<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Diff\ClaimDifference;

/**
 * @covers Wikibase\Repo\Diff\ClaimDifference
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimDifferenceTest extends \MediaWikiTestCase {

	public function testGetReferenceChanges() {
		$expected = new Diff( array(
			new DiffOpAdd( new Reference() )
		), false );

		$difference = new ClaimDifference( null, null, $expected );

		$actual = $difference->getReferenceChanges();

		$this->assertInstanceOf( 'Diff\DiffOp\Diff\Diff', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetQualifierChanges() {
		$expected = new Diff( array(
			new DiffOpAdd( new PropertyNoValueSnak( 42 ) )
		), false );

		$difference = new ClaimDifference( null, $expected );

		$actual = $difference->getQualifierChanges();

		$this->assertInstanceOf( 'Diff\DiffOp\Diff\Diff', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetMainSnakChange() {
		$expected = new DiffOpChange(
			new PropertyNoValueSnak( 42 ),
			new PropertyNoValueSnak( 43 )
		);

		$difference = new ClaimDifference( $expected );

		$actual = $difference->getMainSnakChange();

		$this->assertInstanceOf( 'Diff\DiffOp\DiffOpChange', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetRankChange() {
		$expected = new DiffOpChange(
			Statement::RANK_PREFERRED,
			Statement::RANK_DEPRECATED
		);

		$difference = new ClaimDifference( null, null, null, $expected );

		$actual = $difference->getRankChange();

		$this->assertInstanceOf( 'Diff\DiffOp\DiffOpChange', $actual );
		$this->assertEquals( $expected, $actual );
	}

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
