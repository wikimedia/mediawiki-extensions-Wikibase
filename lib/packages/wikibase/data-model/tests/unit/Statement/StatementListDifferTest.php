<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
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
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'one', 'second' ),
		) );

		$this->assertResultsInDiff( $claims, $claims, new Diff() );
	}

	private function getNewStatement( $guid, $hash ) {
		$statement = new Statement( new Claim( new PropertyValueSnak( 1, new StringValue( $hash ) ) ) );
		$statement->setGuid( $guid );
		return $statement;
	}

	public function testGivenToListWithExtraClaim_additionOperationInDiff() {
		$fromClaims = new StatementList( array(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'one', 'second' ),
		) );

		$toClaims = new StatementList( array(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'two', 'third' ),
			$this->getNewStatement( 'one', 'second' ),
		) );

		$diff = new Diff( array(
			'two' => new DiffOpAdd( $this->getNewStatement( 'two', 'third' ) ),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

	public function testGivenToListWithLessClaims_removalOperationsInDiff() {
		$fromClaims = new StatementList( array(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'one', 'second' ),
			$this->getNewStatement( 'two', 'third' ),
		) );

		$toClaims = new StatementList( array(
			$this->getNewStatement( 'one', 'second' ),
		) );

		$diff = new Diff( array(
			'zero' => new DiffOpRemove( $this->getNewStatement( 'zero', 'first' ) ),
			'two' => new DiffOpRemove( $this->getNewStatement( 'two', 'third' ) ),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

	public function testGivenListWithChangedClaims_changeOperationsInDiff() {
		$fromClaims = new StatementList( array(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'one', 'second' ),
			$this->getNewStatement( 'two', 'third' ),
		) );

		$toClaims = new StatementList( array(
			$this->getNewStatement( 'zero', 'FIRST' ),
			$this->getNewStatement( 'one', 'second' ),
			$this->getNewStatement( 'two', 'THIRD' ),
		) );

		$diff = new Diff( array(
			'zero' => new DiffOpChange(
					$this->getNewStatement( 'zero', 'first' ),
					$this->getNewStatement( 'zero', 'FIRST' )
				),
			'two' => new DiffOpChange(
					$this->getNewStatement( 'two', 'third' ),
					$this->getNewStatement( 'two', 'THIRD' )
				),
		) );

		$this->assertResultsInDiff( $fromClaims, $toClaims, $diff );
	}

}
