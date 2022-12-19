<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Diff\ClaimDifference;

/**
 * @covers \Wikibase\Repo\Diff\ClaimDifference
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimDifferenceTest extends MediaWikiIntegrationTestCase {

	public function testGetReferenceChanges() {
		$expected = new Diff( [
			new DiffOpAdd( new Reference() ),
		], false );

		$difference = new ClaimDifference( null, null, $expected );

		$actual = $difference->getReferenceChanges();

		$this->assertInstanceOf( Diff::class, $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetQualifierChanges() {
		$expected = new Diff( [
			new DiffOpAdd( new PropertyNoValueSnak( 42 ) ),
		], false );

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
		$diff = new Diff( [ $changeOp ] );

		return [
			[ new ClaimDifference( $changeOp ) ],
			[ new ClaimDifference( null, $diff ) ],
			[ new ClaimDifference( null, null, $diff ) ],
			[ new ClaimDifference( null, null, null, $changeOp ) ],
		];
	}

	public function nonAtomicClaimDifferenceProvider() {
		$changeOp = new DiffOpChange( "old", "new" );
		$diff = new Diff( [ $changeOp ] );

		return [
			[ new ClaimDifference() ],
			[ new ClaimDifference( $changeOp, $diff, null, null ) ],
			[ new ClaimDifference( $changeOp, null, $diff, null ) ],
			[ new ClaimDifference( $changeOp, null, null, $changeOp ) ],
			[ new ClaimDifference( $changeOp, $diff, $diff, null ) ],
			[ new ClaimDifference( $changeOp, $diff, null, $changeOp ) ],
			[ new ClaimDifference( $changeOp, null, $diff, $changeOp ) ],
			[ new ClaimDifference( $changeOp, $diff, $diff, $changeOp ) ],
			[ new ClaimDifference( null, null, $diff, $changeOp ) ],
			[ new ClaimDifference( null, $diff, null, $changeOp ) ],
			[ new ClaimDifference( null, $diff, $diff, null ) ],
			[ new ClaimDifference( null, $diff, $diff, $changeOp ) ],
			[ new ClaimDifference( null, new Diff(), null, null ) ],
			[ new ClaimDifference( null, new Diff(), null, null ) ],
		];
	}

	/**
	 * @dataProvider atomicClaimDifferenceProvider
	 */
	public function testIsAtomic( ClaimDifference $claimDifference ) {
		$this->assertTrue( $claimDifference->isAtomic(), "isAtomic reports claimDifference as non-atomic, although it is" );
	}

	/**
	 * @dataProvider nonAtomicClaimDifferenceProvider
	 */
	public function testIsNotAtomic( ClaimDifference $claimDifference ) {
		$this->assertFalse( $claimDifference->isAtomic(), "isAtomic reports claimDifference as atomic, although it is not" );
	}

}
