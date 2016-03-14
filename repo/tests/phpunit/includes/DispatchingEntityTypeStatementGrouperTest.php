<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\DispatchingEntityTypeStatementGrouper;

/**
 * @covers Wikibase\Repo\DispatchingEntityTypeStatementGrouper
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class DispatchingEntityTypeStatementGrouperTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param int $count
	 *
	 * @return StatementGrouper
	 */
	private function newGrouper( $count ) {
		$grouper = $this->getMock( StatementGrouper::class );

		$grouper->expects( $this->exactly( $count ) )
			->method( 'groupStatements' );

		return $grouper;
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( array $groupers ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new DispatchingEntityTypeStatementGrouper( $groupers );
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

		$grouper = new DispatchingEntityTypeStatementGrouper( array() );
		$groups = $grouper->groupStatements( $statements );

		$this->assertSame( array( 'statements' => $statements ), $groups );
	}

	public function testUsesFirstStatementsGuid() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'Q1$' );
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'P1$' );

		$grouper = new DispatchingEntityTypeStatementGrouper( array(
			'item' => $this->newGrouper( 1 ),
			'property' => $this->newGrouper( 0 ),
		) );
		$grouper->groupStatements( $statements );
	}

}
