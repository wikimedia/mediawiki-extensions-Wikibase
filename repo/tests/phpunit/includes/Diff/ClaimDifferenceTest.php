<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Diff\ClaimDifference;

/**
 * @covers Wikibase\Repo\Diff\ClaimDifference
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseClaim
 *
 * @license GPL-2.0+
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

		$this->assertInstanceOf( Diff::class, $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetQualifierChanges() {
		$expected = new Diff( array(
			new DiffOpAdd( new PropertyNoValueSnak( 42 ) )
		), false );

		$difference = new ClaimDifference( null, $expected );

		$actual = $difference->getQualifierChanges();

		$this->assertInstanceOf( Diff::class, $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetMainSnakChange() {
		$expected = new DiffOpChange(
			new PropertyNoValueSnak( 42 ),
			new PropertyNoValueSnak( 43 )
		);

		$difference = new ClaimDifference( $expected );

		$actual = $difference->getMainSnakChange();

		$this->assertInstanceOf( DiffOpChange::class, $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetRankChange() {
		$expected = new DiffOpChange(
			Statement::RANK_PREFERRED,
			Statement::RANK_DEPRECATED
		);

		$difference = new ClaimDifference( null, null, null, $expected );

		$actual = $difference->getRankChange();

		$this->assertInstanceOf( DiffOpChange::class, $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function atomicClaimDifferenceProvider() {
		$changeOp = new DiffOpChange( "old", "new" );
		$diff = new Diff( array( $changeOp ) );

		return array(
			array( new ClaimDifference( $changeOp ) ),
			array( new ClaimDifference( null, $diff ) ),
			array( new ClaimDifference( null, null, $diff ) ),
			array( new ClaimDifference( null, null, null, $changeOp ) ),
		);
	}

	public function nonAtomicClaimDifferenceProvider() {
		$changeOp = new DiffOpChange( "old", "new" );
		$diff = new Diff( array( $changeOp ) );

		return array(
			array( new ClaimDifference() ),
			array( new ClaimDifference( $changeOp, $diff, null, null ) ),
			array( new ClaimDifference( $changeOp, null, $diff, null ) ),
			array( new ClaimDifference( $changeOp, null, null, $changeOp ) ),
			array( new ClaimDifference( $changeOp, $diff, $diff, null ) ),
			array( new ClaimDifference( $changeOp, $diff, null, $changeOp ) ),
			array( new ClaimDifference( $changeOp, null, $diff, $changeOp ) ),
			array( new ClaimDifference( $changeOp, $diff, $diff, $changeOp ) ),
			array( new ClaimDifference( null, null, $diff, $changeOp ) ),
			array( new ClaimDifference( null, $diff, null, $changeOp ) ),
			array( new ClaimDifference( null, $diff, $diff, null ) ),
			array( new ClaimDifference( null, $diff, $diff, $changeOp ) ),
			array( new ClaimDifference( null, new Diff(), null, null ) ),
			array( new ClaimDifference( null, new Diff(), null, null ) ),
		);
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
