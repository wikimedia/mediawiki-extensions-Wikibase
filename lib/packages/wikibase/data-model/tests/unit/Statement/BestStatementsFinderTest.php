<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\BestStatementsFinder;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Statement\BestStatementsFinder
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BestStatementsFinderTest extends \PHPUnit_Framework_TestCase {

	public function provideGetBestStatementsPerProperty() {
		$cases = array();

		$cases['find normal values'] = array(
			array(
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's1' ),
				$this->getStatementMock( 'P23', Statement::RANK_NORMAL, 's2' ),
				$this->getStatementMock( 'P10', Statement::RANK_NORMAL, 's3' ),
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's4' ),
			),
			array( 's1', 's2', 's3', 's4' )
		);

		$cases['find preferred values'] = array(
			array(
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's1' ),
				$this->getStatementMock( 'P23', Statement::RANK_PREFERRED, 's2' ),
				$this->getStatementMock( 'P10', Statement::RANK_NORMAL, 's3' ),
				$this->getStatementMock( 'P42', Statement::RANK_PREFERRED, 's4' ),
			),
			array( 's2', 's3', 's4' )
		);

		$cases['filter deprecated values'] = array(
			array(
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's1' ),
				$this->getStatementMock( 'P23', Statement::RANK_DEPRECATED, 's2' ),
				$this->getStatementMock( 'P10', Statement::RANK_NORMAL, 's3' ),
				$this->getStatementMock( 'P42', Statement::RANK_DEPRECATED, 's4' ),
			),
			array( 's1', 's3' )
		);

		$cases['find preferred and filter deprecated values'] = array(
			array(
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's1' ),
				$this->getStatementMock( 'P23', Statement::RANK_DEPRECATED, 's2' ),
				$this->getStatementMock( 'P10', Statement::RANK_NORMAL, 's3' ),
				$this->getStatementMock( 'P42', Statement::RANK_DEPRECATED, 's4' ),
				$this->getStatementMock( 'P23', Statement::RANK_PREFERRED, 's5' ),
				$this->getStatementMock( 'P42', Statement::RANK_PREFERRED, 's6' ),
				$this->getStatementMock( 'P23', Statement::RANK_PREFERRED, 's7' ),
			),
			array( 's3', 's5', 's6', 's7' )
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetBestStatementsPerProperty
	 */
	public function testGetBestStatementsPerProperty( $statements, $expectedGuids ) {
		$bestStatementFinder = new BestStatementsFinder( $statements );
		$bestStatements = $bestStatementFinder->getBestStatementsPerProperty();
		$guids = array_map( function( Statement $statement ) {
			return $statement->getGuid();
		}, $bestStatements );
		sort( $guids );
		sort( $expectedGuids );
		$this->assertEquals( $expectedGuids, $guids );
	}

	public function provideGetBestStatementsForProperty() {
		$cases = array();

		$cases['find normal values'] = array(
			array(
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's1' ),
				$this->getStatementMock( 'P23', Statement::RANK_NORMAL, 's2' ),
				$this->getStatementMock( 'P10', Statement::RANK_NORMAL, 's3' ),
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's4' ),
			),
			'P42',
			array( 's1', 's4' )
		);

		$cases['find preferred values'] = array(
			array(
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's1' ),
				$this->getStatementMock( 'P23', Statement::RANK_PREFERRED, 's2' ),
				$this->getStatementMock( 'P10', Statement::RANK_NORMAL, 's3' ),
				$this->getStatementMock( 'P42', Statement::RANK_PREFERRED, 's4' ),
			),
			'P42',
			array( 's4' )
		);

		$cases['filter deprecated values'] = array(
			array(
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's1' ),
				$this->getStatementMock( 'P23', Statement::RANK_DEPRECATED, 's2' ),
				$this->getStatementMock( 'P10', Statement::RANK_NORMAL, 's3' ),
				$this->getStatementMock( 'P42', Statement::RANK_DEPRECATED, 's4' ),
			),
			'P42',
			array( 's1' )
		);

		$cases['find preferred and filter deprecated values'] = array(
			array(
				$this->getStatementMock( 'P42', Statement::RANK_NORMAL, 's1' ),
				$this->getStatementMock( 'P23', Statement::RANK_DEPRECATED, 's2' ),
				$this->getStatementMock( 'P10', Statement::RANK_NORMAL, 's3' ),
				$this->getStatementMock( 'P42', Statement::RANK_DEPRECATED, 's4' ),
				$this->getStatementMock( 'P23', Statement::RANK_PREFERRED, 's5' ),
				$this->getStatementMock( 'P42', Statement::RANK_PREFERRED, 's6' ),
				$this->getStatementMock( 'P42', Statement::RANK_PREFERRED, 's7' ),
			),
			'P42',
			array( 's6', 's7' )
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetBestStatementsForProperty
	 */
	public function testGetBestStatementsForProperty( $statements, $propertyId, $expectedGuids ) {
		$bestStatementFinder = new BestStatementsFinder( $statements );
		$bestStatements = $bestStatementFinder->getBestStatementsForProperty( new PropertyId( $propertyId ) );
		$guids = array_map( function( Statement $statement ) {
			return $statement->getGuid();
		}, $bestStatements );
		sort( $guids );
		sort( $expectedGuids );
		$this->assertEquals( $expectedGuids, $guids );
	}

	private function getStatementMock( $propertyId, $rank, $guid ) {
		$statement = $this->getMockBuilder( 'Wikibase\DataModel\Statement\Statement' )
			->disableOriginalConstructor()
			->getMock();

		$statement->expects( $this->any() )
			->method( 'getPropertyId' )
			->will( $this->returnValue( new PropertyId( $propertyId) ) );

		$statement->expects( $this->any() )
			->method( 'getRank' )
			->will( $this->returnValue( $rank ) );

		$statement->expects( $this->any() )
			->method( 'getGuid' )
			->will( $this->returnValue( $guid ) );

		return $statement;
	}

}
