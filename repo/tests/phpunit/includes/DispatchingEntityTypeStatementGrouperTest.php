<?php

namespace Wikibase\Repo\Tests;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\DispatchingEntityTypeStatementGrouper;

/**
 * @covers \Wikibase\Repo\DispatchingEntityTypeStatementGrouper
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class DispatchingEntityTypeStatementGrouperTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param int $count
	 *
	 * @return StatementGrouper
	 */
	private function newGrouper( $count ) {
		$grouper = $this->createMock( StatementGrouper::class );

		$grouper->expects( $this->exactly( $count ) )
			->method( 'groupStatements' );

		return $grouper;
	}

	private function getStatementGuidParser() {
		return new StatementGuidParser( new ItemIdParser() );
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( array $groupers ) {
		$this->expectException( InvalidArgumentException::class );
		new DispatchingEntityTypeStatementGrouper(
			$this->getStatementGuidParser(),
			$groupers
		);
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ [ $this->newGrouper( 0 ) ] ],
			[ [ 'item' => 'invalid' ] ],
		];
	}

	public function testFallsBackToNullGrouper() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'Q1$' );

		$grouper = new DispatchingEntityTypeStatementGrouper(
			$this->getStatementGuidParser(),
			[]
		);
		$groups = $grouper->groupStatements( $statements );

		$this->assertSame( [ 'statements' => $statements ], $groups );
	}

	public function testUsesFirstStatementsGuid() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'Q1$' );
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'P1$' );

		$grouper = new DispatchingEntityTypeStatementGrouper(
			$this->getStatementGuidParser(),
			[
				'item' => $this->newGrouper( 1 ),
				'property' => $this->newGrouper( 0 ),
			]
		);
		$grouper->groupStatements( $statements );
	}

}
