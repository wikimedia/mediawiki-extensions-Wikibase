<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Diff\StatementListPatcher;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\DataModel\Services\Diff\StatementListPatcher
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class StatementListPatcherTest extends TestCase {

	public function patchStatementListProvider() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );

		return [
			// Empty diffs
			[
				new StatementList(),
				new Diff(),
				new StatementList(),
			],
			[
				new StatementList( $statement1 ),
				new Diff(),
				new StatementList( $statement1 ),
			],

			// Add operations
			[
				new StatementList(),
				new Diff( [ new DiffOpAdd( $statement1 ) ], true ),
				new StatementList( $statement1 ),
			],
			[
				new StatementList(),
				new Diff( [ new DiffOpAdd( $statement1 ) ], false ),
				new StatementList( $statement1 ),
			],
			[
				new StatementList(),
				new Diff( [ new DiffOpAdd( $statement1 ) ] ),
				new StatementList( $statement1 ),
			],

			// Remove operations
			[
				new StatementList( $statement1 ),
				new Diff( [ new DiffOpRemove( $statement1 ) ], true ),
				new StatementList(),
			],
			[
				new StatementList( $statement1 ),
				new Diff( [ new DiffOpRemove( $statement1 ) ], false ),
				new StatementList(),
			],
			[
				new StatementList( $statement1 ),
				new Diff( [ new DiffOpRemove( $statement1 ) ] ),
				new StatementList(),
			],

			// Mixed operations
			[
				new StatementList( $statement1 ),
				new Diff( [
					new DiffOpRemove( $statement1 ),
					new DiffOpAdd( $statement2 ),
				] ),
				new StatementList( $statement2 ),
			],
			[
				new StatementList( $statement1 ),
				new Diff( [
					new DiffOpChange( $statement1, $statement2 ),
				] ),
				new StatementList( $statement2 ),
			],
		];
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

	public function statementOrderProvider() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ), null, null, 's1' );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ), null, null, 's2' );
		$statement3 = new Statement( new PropertyNoValueSnak( 3 ), null, null, 's3' );

		return [
			'Simple associative add' => [
				new StatementList(),
				new Diff( [
					's1' => new DiffOpAdd( $statement1 ),
				], true ),
				[ 's1' ],
			],
			'Simple non-associative add' => [
				new StatementList(),
				new Diff( [
					's1' => new DiffOpAdd( $statement1 ),
				], false ),
				[ 's1' ],
			],
			'Simple associative remove' => [
				new StatementList( $statement1 ),
				new Diff( [
					's1' => new DiffOpRemove( $statement1 ),
				], true ),
				[],
			],
			'Simple non-associative remove' => [
				new StatementList( $statement1 ),
				new Diff( [
					's1' => new DiffOpRemove( $statement1 ),
				], false ),
				[],
			],

			// Change operations
			'Remove and add' => [
				new StatementList( $statement1 ),
				new Diff( [
					's1' => new DiffOpRemove( $statement1 ),
					's2' => new DiffOpAdd( $statement2 ),
				] ),
				[ 's2' ],
			],
			'Add and remove' => [
				new StatementList( $statement1 ),
				new Diff( [
					's2' => new DiffOpAdd( $statement2 ),
					's1' => new DiffOpRemove( $statement1 ),
				] ),
				[ 's2' ],
			],
			'Simple associative replace' => [
				new StatementList( $statement1 ),
				new Diff( [
					's1' => new DiffOpChange( $statement1, $statement2 ),
				], true ),
				[ 's2' ],
			],
			'Simple non-associative replace' => [
				new StatementList( $statement1 ),
				new Diff( [
					's1' => new DiffOpChange( $statement1, $statement2 ),
				], false ),
				[ 's2' ],
			],
			'Replacing first element retains order' => [
				new StatementList( $statement1, $statement2 ),
				new Diff( [
					's1' => new DiffOpChange( $statement1, $statement3 ),
				] ),
				[ 's3', 's2' ],
			],
			'Replacing last element retains order' => [
				new StatementList( $statement1, $statement2 ),
				new Diff( [
					's2' => new DiffOpChange( $statement2, $statement3 ),
				] ),
				[ 's1', 's3' ],
			],

			// No-ops
			'Empty diff' => [
				new StatementList( $statement1 ),
				new Diff(),
				[ 's1' ],
			],
			'Adding existing element is no-op' => [
				new StatementList( $statement1 ),
				new Diff( [
					's1' => new DiffOpAdd( $statement1 ),
				] ),
				[ 's1' ],
			],
			'Removing non-existing element is no-op' => [
				new StatementList( $statement1 ),
				new Diff( [
					's2' => new DiffOpRemove( $statement2 ),
				] ),
				[ 's1' ],
			],
			'Replacing non-existing element is no-op' => [
				new StatementList( $statement1 ),
				new Diff( [
					's2' => new DiffOpChange( $statement2, $statement3 ),
				] ),
				[ 's1' ],
			],
		];
	}

	/**
	 * @dataProvider statementOrderProvider
	 */
	public function testStatementOrder( StatementList $statements, Diff $patch, array $expectedGuids ) {
		$patcher = new StatementListPatcher();
		$patcher->patchStatementList( $statements, $patch );

		$guids = [];
		foreach ( $statements->toArray() as $statement ) {
			$guids[] = $statement->getGuid();
		}
		$this->assertSame( $expectedGuids, $guids );
	}

	public function testGivenEmptyDiff_listIsReturnedAsIs() {
		$statements = new StatementList();

		$this->assertListResultsFromPatch( $statements, $statements, new Diff() );
	}

	private function assertListResultsFromPatch(
		StatementList $expected,
		StatementList $statements,
		Diff $patch
	) {
		$patcher = new StatementListPatcher();
		$patcher->patchStatementList( $statements, $patch );
		$this->assertEquals( $expected, $statements );
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

		$patch = new Diff( [
			's0' => new DiffOpRemove( $statement0 ),
			's2' => new DiffOpAdd( $statement2 ),
			's3' => new DiffOpAdd( $statement3 ),
		] );

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
