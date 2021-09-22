<?php

namespace Wikibase\DataModel\Services\Tests\Statement\Grouper;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Statement\Grouper\FilteringStatementGrouper;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementFilter;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\DataModel\Services\Statement\Grouper\FilteringStatementGrouper
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FilteringStatementGrouperTest extends TestCase {

	/**
	 * @param string $propertyId
	 *
	 * @return StatementFilter
	 */
	private function newStatementFilter( $propertyId = 'P1' ) {
		$filter = $this->createMock( StatementFilter::class );

		$filter->expects( $this->any() )
			->method( 'statementMatches' )
			->will( $this->returnCallback( static function( Statement $statement ) use ( $propertyId ) {
				return $statement->getPropertyId()->getSerialization() === $propertyId;
			} ) );

		return $filter;
	}

	public function testConstructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new FilteringStatementGrouper( [ 'invalid' ] );
	}

	public function testDoesNotAcceptTwoDefaults() {
		$this->expectException( InvalidArgumentException::class );
		new FilteringStatementGrouper( [ 'default1' => null, 'default2' => null ] );
	}

	public function testDefaultGroupIsAlwaysThere() {
		$grouper = new FilteringStatementGrouper( [] );
		$groups = $grouper->groupStatements( new StatementList() );

		$this->assertArrayHasKey( 'statements', $groups );
	}

	public function testCanOverrideDefaultGroup() {
		$grouper = new FilteringStatementGrouper( [
			'default' => null,
		] );
		$groups = $grouper->groupStatements( new StatementList() );

		$this->assertArrayHasKey( 'default', $groups );
		$this->assertArrayNotHasKey( 'statements', $groups );
	}

	public function testAllGroupsAreAlwaysThere() {
		$grouper = new FilteringStatementGrouper( [
			'p1' => $this->newStatementFilter(),
		] );
		$groups = $grouper->groupStatements( new StatementList() );

		$this->assertArrayHasKey( 'p1', $groups );
	}

	public function testDefaultGroupIsLast() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ) );

		$grouper = new FilteringStatementGrouper( [
			'p1' => $this->newStatementFilter(),
		] );
		$groups = $grouper->groupStatements( $statements );

		$this->assertSame( [ 'p1', 'statements' ], array_keys( $groups ) );
	}

	public function testCanOverrideDefaultGroupPosition() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ) );

		$grouper = new FilteringStatementGrouper( [
			'statements' => null,
			'p1' => $this->newStatementFilter(),
		] );
		$groups = $grouper->groupStatements( $statements );

		$this->assertSame( [ 'statements', 'p1' ], array_keys( $groups ) );
	}

	public function testCanRepurposeDefaultGroup() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ) );

		$grouper = new FilteringStatementGrouper( [
			'statements' => $this->newStatementFilter(),
		] );
		$groups = $grouper->groupStatements( $statements );

		$this->assertCount( 1, $groups );
		$this->assertArrayHasKey( 'statements', $groups );
		$this->assertCount( 1, $groups['statements'] );
	}

	public function testGroupStatements() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );
		$statement3 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statements = new StatementList( $statement1, $statement2, $statement3 );

		$grouper = new FilteringStatementGrouper( [
			'p1' => $this->newStatementFilter( 'P1' ),
			'p2' => $this->newStatementFilter( 'P2' ),
			'p3' => $this->newStatementFilter( 'P3' ),
		] );

		$expected = [
			'p1' => new StatementList( $statement1, $statement3 ),
			'p2' => new StatementList( $statement2 ),
			'p3' => new StatementList(),
			'statements' => new StatementList(),
		];

		$groups = $grouper->groupStatements( $statements );
		$this->assertEquals( $expected, $groups, 'first call' );

		$groups = $grouper->groupStatements( $statements );
		$this->assertEquals( $expected, $groups, 'second call' );
	}

}
