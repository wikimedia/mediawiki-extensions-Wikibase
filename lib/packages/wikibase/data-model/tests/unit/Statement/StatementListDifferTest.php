<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListDiffer;

/**
 * @covers Wikibase\DataModel\Statement\StatementListDiffer
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementListDifferTest extends \PHPUnit_Framework_TestCase {

	public function testGivenTwoEmptyLists_diffIsEmpty() {
		$this->assertResultsInDiff( new StatementList(), new StatementList(), new Diff() );
	}

	private function assertResultsInDiff( StatementList $fromClaims, StatementList $toClaims, Diff $diff ) {
		$differ = new StatementListDiffer();

		$actual = $differ->getDiff( $fromClaims, $toClaims );

		$this->assertEquals( $diff, $actual );
	}

	public function testGivenTwoIdenticalLists_diffIsEmpty() {
		$claims = new StatementList( array(
			$this->getStubStatement( 'zero', 'first' ),
			$this->getStubStatement( 'one', 'second' ),
		) );

		$this->assertResultsInDiff( $claims, $claims, new Diff() );
	}

	private function getStubStatement( $guid, $hash ) {
		$claim = $this->getMockBuilder( 'Wikibase\DataModel\Statement\Statement' )
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
		$fromClaims = new StatementList( array(
			$this->getStubStatement( 'zero', 'first' ),
			$this->getStubStatement( 'one', 'second' ),
		) );

		$toClaims = new StatementList( array(
			$this->getStubStatement( 'zero', 'first' ),
			$this->getStubStatement( 'two', 'third' ),
			$this->getStubStatement( 'one', 'second' ),
		) );

		$diff = new Diff( array(
			'two' => new DiffOpAdd( $this->getStubStatement( 'two', 'third' ) ),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

	public function testGivenToListWithLessClaims_removalOperationsInDiff() {
		$fromClaims = new StatementList( array(
			$this->getStubStatement( 'zero', 'first' ),
			$this->getStubStatement( 'one', 'second' ),
			$this->getStubStatement( 'two', 'third' ),
		) );

		$toClaims = new StatementList( array(
			$this->getStubStatement( 'one', 'second' ),
		) );

		$diff = new Diff( array(
			'zero' => new DiffOpRemove( $this->getStubStatement( 'zero', 'first' ) ),
			'two' => new DiffOpRemove( $this->getStubStatement( 'zero', 'third' ) ),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

	public function testGivenListWithChangedClaims_changeOperationsInDiff() {
		$fromClaims = new StatementList( array(
			$this->getStubStatement( 'zero', 'first' ),
			$this->getStubStatement( 'one', 'second' ),
			$this->getStubStatement( 'two', 'third' ),
		) );

		$toClaims = new StatementList( array(
			$this->getStubStatement( 'zero', 'FIRST' ),
			$this->getStubStatement( 'one', 'second' ),
			$this->getStubStatement( 'two', 'THIRD' ),
		) );

		$diff = new Diff( array(
			'zero' => new DiffOpChange(
					$this->getStubStatement( 'zero', 'first' ),
					$this->getStubStatement( 'zero', 'FIRST' )
				),
			'two' => new DiffOpChange(
					$this->getStubStatement( 'zero', 'third' ),
					$this->getStubStatement( 'zero', 'THIRD' )
				),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

}
