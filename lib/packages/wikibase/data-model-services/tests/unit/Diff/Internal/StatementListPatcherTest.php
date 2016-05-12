<?php

namespace Wikibase\DataModel\Services\Tests\Diff\Internal;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Services\Diff\Internal\StatementListPatcher;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Services\Diff\Internal\StatementListPatcher
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class StatementListPatcherTest extends \PHPUnit_Framework_TestCase {

	public function patchStatementListProvider() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );

		return array(
			// Empty diffs
			array(
				new StatementList(),
				new Diff(),
				new StatementList()
			),
			array(
				new StatementList( $statement1 ),
				new Diff(),
				new StatementList( $statement1 )
			),

			// Add operations
			array(
				new StatementList(),
				new Diff( array( new DiffOpAdd( $statement1 ) ), true ),
				new StatementList( $statement1 )
			),
			array(
				new StatementList(),
				new Diff( array( new DiffOpAdd( $statement1 ) ), false ),
				new StatementList( $statement1 )
			),
			array(
				new StatementList(),
				new Diff( array( new DiffOpAdd( $statement1 ) ) ),
				new StatementList( $statement1 )
			),

			// Remove operations
			array(
				new StatementList( $statement1 ),
				new Diff( array( new DiffOpRemove( $statement1 ) ), true ),
				new StatementList()
			),
			array(
				new StatementList( $statement1 ),
				new Diff( array( new DiffOpRemove( $statement1 ) ), false ),
				new StatementList()
			),
			array(
				new StatementList( $statement1 ),
				new Diff( array( new DiffOpRemove( $statement1 ) ) ),
				new StatementList()
			),

			// Mixed operations
			array(
				new StatementList( $statement1 ),
				new Diff( array(
					new DiffOpRemove( $statement1 ),
					new DiffOpAdd( $statement2 ),
				) ),
				new StatementList( $statement2 )
			),
			array(
				new StatementList( $statement1 ),
				new Diff( array(
					new DiffOpChange( $statement1, $statement2 ),
				) ),
				new StatementList( $statement2 )
			),
		);
	}

	/**
	 * @dataProvider patchStatementListProvider
	 */
	public function testPatchStatementList(
		StatementList $statements,
		Diff $patch,
		StatementList $expected
	) {
		$patcher = new StatementListPatcher();
		$patcher->patchStatementList( $statements, $patch );
		$this->assertEquals( $expected, $statements );
	}

	/**
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function statementOrderProvider() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ), null, null, 's1' );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ), null, null, 's2' );
		$statement3 = new Statement( new PropertyNoValueSnak( 3 ), null, null, 's3' );

		return array(
			'Simple associative add' => array(
				new StatementList(),
				new Diff( array(
					's1' => new DiffOpAdd( $statement1 ),
				), true ),
				array( 's1' )
			),
			'Simple non-associative add' => array(
				new StatementList(),
				new Diff( array(
					's1' => new DiffOpAdd( $statement1 ),
				), false ),
				array( 's1' )
			),
			'Simple associative remove' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's1' => new DiffOpRemove( $statement1 ),
				), true ),
				array()
			),
			'Simple non-associative remove' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's1' => new DiffOpRemove( $statement1 ),
				), false ),
				array()
			),

			// Change operations
			'Remove and add' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's1' => new DiffOpRemove( $statement1 ),
					's2' => new DiffOpAdd( $statement2 ),
				) ),
				array( 's2' )
			),
			'Add and remove' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's2' => new DiffOpAdd( $statement2 ),
					's1' => new DiffOpRemove( $statement1 ),
				) ),
				array( 's2' )
			),
			'Simple associative replace' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's1' => new DiffOpChange( $statement1, $statement2 ),
				), true ),
				array( 's2' )
			),
			'Simple non-associative replace' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's1' => new DiffOpChange( $statement1, $statement2 ),
				), false ),
				array( 's2' )
			),
			'Replacing first element retains order' => array(
				new StatementList( $statement1, $statement2 ),
				new Diff( array(
					's1' => new DiffOpChange( $statement1, $statement3 ),
				) ),
				array( 's3', 's2' )
			),
			'Replacing last element retains order' => array(
				new StatementList( $statement1, $statement2 ),
				new Diff( array(
					's2' => new DiffOpChange( $statement2, $statement3 ),
				) ),
				array( 's1', 's3' )
			),

			// No-ops
			'Empty diff' => array(
				new StatementList( $statement1 ),
				new Diff(),
				array( 's1' )
			),
			'Adding existing element is no-op' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's1' => new DiffOpAdd( $statement1 ),
				) ),
				array( 's1' )
			),
			'Removing non-existing element is no-op' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's2' => new DiffOpRemove( $statement2 ),
				) ),
				array( 's1' )
			),
			'Replacing non-existing element is no-op' => array(
				new StatementList( $statement1 ),
				new Diff( array(
					's2' => new DiffOpChange( $statement2, $statement3 ),
				) ),
				array( 's1' )
			),
		);
	}

	/**
	 * @dataProvider statementOrderProvider
	 */
	public function testStatementOrder( StatementList $statements, Diff $patch, array $expectedGuids ) {
		$patcher = new StatementListPatcher();
		$patchedStatements = $patcher->getPatchedStatementList( $statements, $patch );

		$guids = array();
		foreach ( $patchedStatements->toArray() as $statement ) {
			$guids[] = $statement->getGuid();
		}
		$this->assertSame( $expectedGuids, $guids );
	}

	public function testGivenEmptyDiff_listIsReturnedAsIs() {
		$statements = new StatementList();

		$this->assertListResultsFromPatch( $statements, $statements, new Diff() );
	}

	private function assertListResultsFromPatch( StatementList $expected, StatementList $original, Diff $patch ) {
		$patcher = new StatementListPatcher();
		$clone = clone $original;
		$this->assertEquals( $expected, $patcher->getPatchedStatementList( $original, $patch ) );
		$this->assertEquals( $clone, $original, 'original must not change' );
	}

	public function testFoo() {
		$statement0 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement0->setGuid( 's0' );

		$statement1 = new Statement( new PropertySomeValueSnak( 42 ) );
		$statement1->setGuid( 's1' );

		$statement2 = new Statement( new PropertyValueSnak( 42, new StringValue( 'ohi' ) ) );
		$statement2->setGuid( 's2' );

		$statement3 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement3->setGuid( 's3' );

		$patch = new Diff( array(
			's0' => new DiffOpRemove( $statement0 ),
			's2' => new DiffOpAdd( $statement2 ),
			's3' => new DiffOpAdd( $statement3 )
		) );

		$source = new StatementList();
		$source->addStatement( $statement0 );
		$source->addStatement( $statement1 );

		$expected = new StatementList();
		$expected->addStatement( $statement1 );
		$expected->addStatement( $statement2 );
		$expected->addStatement( $statement3 );

		$this->assertListResultsFromPatch( $expected, $source, $patch );
	}

}
