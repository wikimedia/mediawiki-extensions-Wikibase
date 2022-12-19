<?php

namespace Wikibase\Repo\Tests;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\DispatchingEntityTypeStatementGrouper;
use Wikibase\Repo\StatementGrouperBuilder;

/**
 * @covers \Wikibase\Repo\StatementGrouperBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class StatementGrouperBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param array[] $specifications
	 *
	 * @return StatementGrouperBuilder
	 */
	private function newInstance( array $specifications ) {
		$lookup = $this->createMock( PropertyDataTypeLookup::class );

		$lookup->method( 'getDataTypeIdForProperty' )
			->willReturn( true );

		return new StatementGrouperBuilder(
			$specifications,
			$lookup,
			new StatementGuidParser( new ItemIdParser() )
		);
	}

	/**
	 * @return StatementList
	 */
	private function newStatementList() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ), null, null, 'Q1$' );
		return $statements;
	}

	public function testAcceptsEmptyArray() {
		$builder = $this->newInstance( [] );
		$grouper = $builder->getStatementGrouper();
		$this->assertInstanceOf( DispatchingEntityTypeStatementGrouper::class, $grouper );
	}

	public function testAcceptsNullGrouper() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( [
			'item' => null,
		] );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertSame( [ 'statements' => $statements ], $groups );
	}

	public function testAcceptsDefaultFilter() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( [
			'item' => [
				'default' => null,
			],
		] );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertEquals( [ 'default' => $statements ], $groups );
	}

	public function testAcceptsNullFilter() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( [
			'item' => [
				'custom' => [ 'type' => null ],
			],
		] );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertEquals( [
			'custom' => $statements,
			'statements' => new StatementList(),
		], $groups );
	}

	public function testIncompleteDataTypeFilter() {
		$builder = $this->newInstance( [
			'item' => [
				'custom' => [ 'type' => 'dataType' ],
			],
		] );
		$this->expectException( InvalidArgumentException::class );
		$builder->getStatementGrouper();
	}

	public function testDataTypeFilter() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( [
			'item' => [
				'custom' => [ 'type' => 'dataType', 'dataTypes' => [ 'string' ] ],
			],
		] );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertEquals( [
			'custom' => $statements,
			'statements' => new StatementList(),
		], $groups );
	}

	public function testIncompletePropertySetFilter() {
		$builder = $this->newInstance( [
			'item' => [
				'custom' => [ 'type' => 'propertySet' ],
			],
		] );
		$this->expectException( InvalidArgumentException::class );
		$builder->getStatementGrouper();
	}

	public function testPropertySetFilter() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( [
			'item' => [
				'custom' => [ 'type' => 'propertySet', 'propertyIds' => [ 'P1' ] ],
			],
		] );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertEquals( [
			'custom' => $statements,
			'statements' => new StatementList(),
		], $groups );
	}

	public function testInvalidFilterType() {
		$builder = $this->newInstance( [
			'item' => [
				'custom' => [ 'type' => 'invalid' ],
			],
		] );
		$this->expectException( InvalidArgumentException::class );
		$builder->getStatementGrouper();
	}

}
