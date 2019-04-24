<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Diff\StatementListDiffer;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\DataModel\Services\Diff\StatementListDiffer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementListDifferTest extends TestCase {

	public function testGivenTwoEmptyLists_diffIsEmpty() {
		$this->assertResultsInDiff( new StatementList(), new StatementList(), new Diff() );
	}

	private function assertResultsInDiff( StatementList $fromStatements, StatementList $toStatements, Diff $diff ) {
		$differ = new StatementListDiffer();

		$actual = $differ->getDiff( $fromStatements, $toStatements );

		$this->assertEquals( $diff, $actual );
	}

	public function testGivenTwoIdenticalLists_diffIsEmpty() {
		$statements = new StatementList(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'one', 'second' )
		);

		$this->assertResultsInDiff( $statements, $statements, new Diff() );
	}

	private function getNewStatement( $guid, $hash ) {
		$statement = new Statement( new PropertyValueSnak( 1, new StringValue( $hash ) ) );
		$statement->setGuid( $guid );
		return $statement;
	}

	public function testGivenToListWithExtraStatement_additionOperationInDiff() {
		$fromStatements = new StatementList(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'one', 'second' )
		);

		$toStatements = new StatementList(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'two', 'third' ),
			$this->getNewStatement( 'one', 'second' )
		);

		$diff = new Diff( [
			'two' => new DiffOpAdd( $this->getNewStatement( 'two', 'third' ) ),
		] );

		$this->assertResultsInDiff( $fromStatements, $toStatements, $diff );
	}

	public function testGivenToListWithLessStatements_removalOperationsInDiff() {
		$fromStatements = new StatementList(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'one', 'second' ),
			$this->getNewStatement( 'two', 'third' )
		);

		$toStatements = new StatementList(
			$this->getNewStatement( 'one', 'second' )
		);

		$diff = new Diff( [
			'zero' => new DiffOpRemove( $this->getNewStatement( 'zero', 'first' ) ),
			'two' => new DiffOpRemove( $this->getNewStatement( 'two', 'third' ) ),
		] );

		$this->assertResultsInDiff( $fromStatements, $toStatements, $diff );
	}

	public function testGivenListWithChangedStatements_changeOperationsInDiff() {
		$fromStatements = new StatementList(
			$this->getNewStatement( 'zero', 'first' ),
			$this->getNewStatement( 'one', 'second' ),
			$this->getNewStatement( 'two', 'third' )
		);

		$toStatements = new StatementList(
			$this->getNewStatement( 'zero', 'FIRST' ),
			$this->getNewStatement( 'one', 'second' ),
			$this->getNewStatement( 'two', 'THIRD' )
		);

		$diff = new Diff( [
			'zero' => new DiffOpChange(
					$this->getNewStatement( 'zero', 'first' ),
					$this->getNewStatement( 'zero', 'FIRST' )
				),
			'two' => new DiffOpChange(
					$this->getNewStatement( 'two', 'third' ),
					$this->getNewStatement( 'two', 'THIRD' )
				),
		] );

		$this->assertResultsInDiff( $fromStatements, $toStatements, $diff );
	}

}
