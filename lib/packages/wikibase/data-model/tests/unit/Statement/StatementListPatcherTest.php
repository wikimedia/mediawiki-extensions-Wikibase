<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListPatcher;

/**
 * @covers Wikibase\DataModel\Statement\StatementListPatcher
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementListPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_listIsReturnedAsIs() {
		$statements = new StatementList();

		$this->assertListResultsFromPatch( $statements, $statements, new Diff() );
	}

	private function assertListResultsFromPatch( StatementList $expected, StatementList $original, Diff $patch ) {
		$patcher = new StatementListPatcher();
		$this->assertEquals( $expected, $patcher->getPatchedStatementList( $original, $patch ) );
	}

	public function testFoo() {
		$statement0 = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement0->setGuid( 's0' );

		$statement1 = new Statement( new Claim( new PropertySomeValueSnak( 42 ) ) );
		$statement1->setGuid( 's1' );

		$statement2 = new Statement( new Claim( new PropertyValueSnak( 42, new StringValue( 'ohi' ) ) ) );
		$statement2->setGuid( 's2' );

		$statement3 = new Statement( new Claim( new PropertyNoValueSnak( 1 ) ) );
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
