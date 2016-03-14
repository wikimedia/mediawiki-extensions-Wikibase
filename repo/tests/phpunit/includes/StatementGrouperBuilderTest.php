<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\StatementGrouperBuilder;

/**
 * @covers Wikibase\Repo\StatementGrouperBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class StatementGrouperBuilderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param array[] $specifications
	 *
	 * @return StatementGrouperBuilder
	 */
	private function newInstance( array $specifications ) {
		$lookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup' );

		$lookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( true ) );

		return new StatementGrouperBuilder( $specifications, $lookup );
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
		$builder = $this->newInstance( array() );
		$grouper = $builder->getStatementGrouper();
		$this->assertInstanceOf( 'Wikibase\Repo\DispatchingEntityTypeStatementGrouper', $grouper );
	}

	public function testAcceptsNullGrouper() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( array(
			'item' => null,
		) );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertSame( array( 'statements' => $statements ), $groups );
	}

	public function testAcceptsDefaultFilter() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( array(
			'item' => array(
				'default' => null,
			)
		) );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertEquals( array( 'default' => $statements ), $groups );
	}

	public function testAcceptsNullFilter() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( array(
			'item' => array(
				'custom' => array( 'type' => null ),
			)
		) );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertEquals( array(
			'custom' => $statements,
			'statements' => new StatementList(),
		), $groups );
	}

	public function testIncompleteDataTypeFilter() {
		$builder = $this->newInstance( array(
			'item' => array(
				'custom' => array( 'type' => 'dataType' ),
			)
		) );
		$this->setExpectedException( InvalidArgumentException::class );
		$builder->getStatementGrouper();
	}

	public function testDataTypeFilter() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( array(
			'item' => array(
				'custom' => array( 'type' => 'dataType', 'dataTypes' => array( 'string' ) ),
			)
		) );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertEquals( array(
			'custom' => $statements,
			'statements' => new StatementList(),
		), $groups );
	}

	public function testIncompletePropertySetFilter() {
		$builder = $this->newInstance( array(
			'item' => array(
				'custom' => array( 'type' => 'propertySet' ),
			)
		) );
		$this->setExpectedException( InvalidArgumentException::class );
		$builder->getStatementGrouper();
	}

	public function testPropertySetFilter() {
		$statements = $this->newStatementList();

		$builder = $this->newInstance( array(
			'item' => array(
				'custom' => array( 'type' => 'propertySet', 'propertyIds' => array( 'P1' ) ),
			)
		) );
		$grouper = $builder->getStatementGrouper();
		$groups = $grouper->groupStatements( $statements );

		$this->assertEquals( array(
			'custom' => $statements,
			'statements' => new StatementList(),
		), $groups );
	}

	public function testInvalidFilterType() {
		$builder = $this->newInstance( array(
			'item' => array(
				'custom' => array( 'type' => 'invalid' ),
			)
		) );
		$this->setExpectedException( InvalidArgumentException::class );
		$builder->getStatementGrouper();
	}

}
