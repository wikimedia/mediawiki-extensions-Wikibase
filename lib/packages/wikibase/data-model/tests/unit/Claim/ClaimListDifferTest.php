<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\ClaimListDiffer;
use Wikibase\DataModel\Claim\Claims;

/**
 * @covers Wikibase\DataModel\Claim\ClaimListDiffer
 * @uses Wikibase\DataModel\Claim\Claims
 * @uses Diff\DiffOp\Diff\Diff
 * @uses Diff\DiffOp\DiffOpAdd
 * @uses Diff\DiffOp\DiffOpChange
 * @uses Diff\DiffOp\DiffOpRemove
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimListDifferTest extends \PHPUnit_Framework_TestCase {

	public function testGivenTwoEmptyLists_diffIsEmpty() {
		$this->assertResultsInDiff( new Claims(), new Claims(), new Diff() );
	}

	private function assertResultsInDiff( Claims $fromClaims, Claims $toClaims, Diff $diff ) {
		$differ = new ClaimListDiffer();

		$actual = $differ->getDiff( $fromClaims, $toClaims );

		$this->assertEquals( $diff, $actual );
	}

	public function testGivenTwoIdenticalLists_diffIsEmpty() {
		$claims = new Claims( array(
			$this->getStubClaim( 'zero', 'first' ),
			$this->getStubClaim( 'one', 'second' ),
		) );

		$this->assertResultsInDiff( $claims, $claims, new Diff() );
	}

	private function getStubClaim( $guid, $hash ) {
		$claim = $this->getMockBuilder( 'Wikibase\DataModel\Claim\Claim' )
			->disableOriginalConstructor()->getMock();

		$claim->expects( $this->any() )
			->method( 'getGuid' )
			->will( $this->returnValue( $guid ) );

		$claim->expects( $this->any() )
			->method( 'getHash' )
			->will( $this->returnValue( $hash ) );

		return $claim;
	}

	public function testGivenToListWithExtraClaim_additionOperationInDiff() {
		$fromClaims = new Claims( array(
			$this->getStubClaim( 'zero', 'first' ),
			$this->getStubClaim( 'one', 'second' ),
		) );

		$toClaims = new Claims( array(
			$this->getStubClaim( 'zero', 'first' ),
			$this->getStubClaim( 'two', 'third' ),
			$this->getStubClaim( 'one', 'second' ),
		) );

		$diff = new Diff( array(
			'two' => new DiffOpAdd( $this->getStubClaim( 'two', 'third' ) ),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

	public function testGivenToListWithLessClaims_removalOperationsInDiff() {
		$fromClaims = new Claims( array(
			$this->getStubClaim( 'zero', 'first' ),
			$this->getStubClaim( 'one', 'second' ),
			$this->getStubClaim( 'two', 'third' ),
		) );

		$toClaims = new Claims( array(
			$this->getStubClaim( 'one', 'second' ),
		) );

		$diff = new Diff( array(
			'zero' => new DiffOpRemove( $this->getStubClaim( 'zero', 'first' ) ),
			'two' => new DiffOpRemove( $this->getStubClaim( 'zero', 'third' ) ),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

	public function testGivenListWithChangedClaims_changeOperationsInDiff() {
		$fromClaims = new Claims( array(
			$this->getStubClaim( 'zero', 'first' ),
			$this->getStubClaim( 'one', 'second' ),
			$this->getStubClaim( 'two', 'third' ),
		) );

		$toClaims = new Claims( array(
			$this->getStubClaim( 'zero', 'FIRST' ),
			$this->getStubClaim( 'one', 'second' ),
			$this->getStubClaim( 'two', 'THIRD' ),
		) );

		$diff = new Diff( array(
			'zero' => new DiffOpChange(
					$this->getStubClaim( 'zero', 'first' ),
					$this->getStubClaim( 'zero', 'FIRST' )
				),
			'two' => new DiffOpChange(
					$this->getStubClaim( 'zero', 'third' ),
					$this->getStubClaim( 'zero', 'THIRD' )
				),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

}
