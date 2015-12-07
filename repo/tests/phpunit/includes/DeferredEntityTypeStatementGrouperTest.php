<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\DeferredEntityTypeStatementGrouper;

/**
 * @covers Wikibase\Repo\DeferredEntityTypeStatementGrouper
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DeferredEntityTypeStatementGrouperTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param int $count
	 *
	 * @return StatementGrouper
	 */
	private function newGrouper( $count ) {
		$grouper = $this->getMock(
			'Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper'
		);

		$grouper->expects( $this->exactly( $count ) )
			->method( 'groupStatements' );

		return $grouper;
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( array $groupers ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new DeferredEntityTypeStatementGrouper( $groupers );
	}

	public function invalidConstructorArgumentProvider() {
		return array(
			array( array( $this->newGrouper( 0 ) ) ),
			array( array( 'item' => 'invalid' ) ),
		);
	}

	public function testFallsBackToNullGrouper() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'Q1$' );

		$grouper = new DeferredEntityTypeStatementGrouper( array() );
		$groups = $grouper->groupStatements( $statements );
		$this->assertEquals( array( 'statements' => $statements ), $groups );
	}

	public function testUsesFirstStatementsGuid() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'Q1$' );
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'P1$' );

		$grouper = new DeferredEntityTypeStatementGrouper( array(
			'item' => $this->newGrouper( 1 ),
			'property' => $this->newGrouper( 0 ),
		) );
		$grouper->groupStatements( $statements );
	}

}
