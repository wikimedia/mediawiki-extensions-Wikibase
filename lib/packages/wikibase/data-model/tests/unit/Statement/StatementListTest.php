<?php

namespace Wikibase\DataModel\Tests\Statement;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Statement\StatementList
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementListTest extends \PHPUnit_Framework_TestCase {

	public function testGivenNoStatements_getPropertyIdsReturnsEmptyArray() {
		$list = new StatementList();
		$this->assertSame( array(), $list->getPropertyIds() );
	}

	public function testGivenStatements_getPropertyIdsReturnsArrayWithoutDuplicates() {
		$list = new StatementList(
			$this->getStatement( 1, 'kittens' ),
			$this->getStatement( 3, 'foo' ),
			$this->getStatement( 2, 'bar' ),
			$this->getStatement( 2, 'baz' ),
			$this->getStatement( 1, 'bah' )
		);

		$this->assertEquals(
			array(
				'P1' => new PropertyId( 'P1' ),
				'P3' => new PropertyId( 'P3' ),
				'P2' => new PropertyId( 'P2' ),
			),
			$list->getPropertyIds()
		);
	}

	public function testGivenStatementsWithArrayKeys_reindexesArray() {
		$statement = $this->getStatement( 1, 'guid' );
		$list = new StatementList( array( 'ignore-me' => $statement ) );

		$this->assertSame( array( 0 => $statement ), $list->toArray() );
	}

	/**
	 * @param int $propertyId
	 * @param string $guid
	 * @param int $rank
	 *
	 * @return Statement
	 */
	private function getStatement( $propertyId, $guid, $rank = Statement::RANK_NORMAL ) {
		$statement = $this->getMockBuilder( 'Wikibase\DataModel\Statement\Statement' )
			->disableOriginalConstructor()->getMock();

		$statement->expects( $this->any() )
			->method( 'getGuid' )
			->will( $this->returnValue( $guid ) );

		$statement->expects( $this->any() )
			->method( 'getPropertyId' )
			->will( $this->returnValue( PropertyId::newFromNumber( $propertyId ) ) );

		$statement->expects( $this->any() )
			->method( 'getRank' )
			->will( $this->returnValue( $rank ) );

		return $statement;
	}

	public function testCanIterate() {
		$statement = $this->getStatement( 1, 'kittens' );
		$list = new StatementList( $statement );

		foreach ( $list as $statementFormList ) {
			$this->assertEquals( $statement, $statementFormList );
		}
	}

	public function testGetBestStatementPerProperty() {
		$list = new StatementList(
			$this->getStatement( 1, 'one', Statement::RANK_PREFERRED ),
			$this->getStatement( 1, 'two', Statement::RANK_NORMAL ),
			$this->getStatement( 1, 'three', Statement::RANK_PREFERRED ),

			$this->getStatement( 2, 'four', Statement::RANK_DEPRECATED ),

			$this->getStatement( 3, 'five', Statement::RANK_DEPRECATED ),
			$this->getStatement( 3, 'six', Statement::RANK_NORMAL ),

			$this->getStatement( 4, 'seven', Statement::RANK_PREFERRED ),
			$this->getStatement( 4, 'eight', Claim::RANK_TRUTH )
		);

		$this->assertEquals(
			array(
				$this->getStatement( 1, 'one', Statement::RANK_PREFERRED ),
				$this->getStatement( 1, 'three', Statement::RANK_PREFERRED ),

				$this->getStatement( 3, 'six', Statement::RANK_NORMAL ),

				$this->getStatement( 4, 'eight', Claim::RANK_TRUTH ),
			),
			$list->getBestStatementPerProperty()->toArray()
		);
	}

	public function testGetUniqueMainSnaksReturnsListWithoutDuplicates() {
		$list = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'foo' ),
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' ),
			$this->getStatementWithSnak( 1, 'bar' )
		);

		$this->assertEquals(
			array(
				$this->getStatementWithSnak( 1, 'foo' ),
				$this->getStatementWithSnak( 2, 'foo' ),
				$this->getStatementWithSnak( 2, 'bar' ),
				$this->getStatementWithSnak( 1, 'bar' ),
			),
			array_values( $list->getWithUniqueMainSnaks()->toArray() )
		);
	}

	public function testGetAllSnaksReturnsAllSnaks() {
		$list = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'foo' ),
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' ),
			$this->getStatementWithSnak( 1, 'bar' )
		);

		$this->assertEquals(
			array(
				$this->newSnak( 1, 'foo' ),
				$this->newSnak( 2, 'foo' ),
				$this->newSnak( 1, 'foo' ),
				$this->newSnak( 2, 'bar' ),
				$this->newSnak( 1, 'bar' ),
			),
			$list->getAllSnaks()
		);
	}

	private function getStatementWithSnak( $propertyId, $stringValue ) {
		$snak = $this->newSnak( $propertyId, $stringValue );
		$statement = new Statement( new Claim( $snak ) );
		$statement->setGuid( sha1( $snak->getHash() ) );
		return $statement;
	}

	private function newSnak( $propertyId, $stringValue ) {
		return new PropertyValueSnak( $propertyId, new StringValue( $stringValue ) );
	}

	public function testAddStatementWithOnlyMainSnak() {
		$list = new StatementList();

		$list->addNewStatement( $this->newSnak( 42, 'foo' ) );

		$this->assertEquals(
			new StatementList(
				new Statement( new Claim( $this->newSnak( 42, 'foo' ) ) )
			),
			$list
		);
	}

	public function testAddStatementWithQualifiersAsSnakArray() {
		$list = new StatementList();

		$list->addNewStatement(
			$this->newSnak( 42, 'foo' ),
			array(
				$this->newSnak( 1, 'bar' )
			)
		);

		$this->assertEquals(
			new StatementList(
				new Statement( new Claim(
					$this->newSnak( 42, 'foo' ),
					new SnakList( array(
						$this->newSnak( 1, 'bar' )
					) )
				) )
			),
			$list
		);
	}

	public function testAddStatementWithQualifiersAsSnakList() {
		$list = new StatementList();
		$snakList = new SnakList( array(
			$this->newSnak( 1, 'bar' )
		) );

		$list->addNewStatement(
			$this->newSnak( 42, 'foo' ),
			$snakList
		);

		$this->assertEquals(
			new StatementList(
				new Statement( new Claim(
					$this->newSnak( 42, 'foo' ),
					$snakList
				) )
			),
			$list
		);
	}

	public function testAddStatementWithGuid() {
		$list = new StatementList();

		$list->addNewStatement(
			$this->newSnak( 42, 'foo' ),
			null,
			null,
			'kittens'
		);

		$statement = new Statement( new Claim(
			$this->newSnak( 42, 'foo' ),
			null
		) );

		$statement->setGuid( 'kittens' );

		$this->assertEquals( new StatementList( $statement ), $list );
	}

	public function testCanConstructWithClaimsObjectContainingOnlyStatements() {
		$statementArray = array(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' ),
		);

		$claimsObject = new Claims( $statementArray );

		$list = new StatementList( $claimsObject );

		$this->assertEquals(
			$statementArray,
			array_values( $list->toArray() )
		);
	}

	public function testGivenTraversableWithNonStatements_constructorThrowsException() {
		$claim = new Claim( new PropertyValueSnak( 42, new StringValue( 'foo' ) ) );
		$claim->setGuid( 'meh' );

		$claimArray = array(
			$this->getStatementWithSnak( 1, 'foo' ),
			$claim,
			$this->getStatementWithSnak( 2, 'bar' ),
		);

		$claimsObject = new Claims( $claimArray );

		$this->setExpectedException( 'InvalidArgumentException' );
		new StatementList( $claimsObject );
	}

	public function testGivenNonTraversableOrArgList_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new StatementList( null );
	}

	public function testCanConstructWithStatement() {
		$statement = new Statement( new Claim( $this->newSnak( 42, 'foo' ) ) );

		$this->assertEquals(
			new StatementList( array( $statement ) ),
			new StatementList( $statement )
		);
	}

	public function testCanConstructWithStatementArgumentList() {
		$statement0 = new Statement( new Claim( $this->newSnak( 42, 'foo' ) ) );
		$statement1 = new Statement( new Claim( $this->newSnak( 42, 'bar' ) ) );
		$statement2 = new Statement( new Claim( $this->newSnak( 42, 'baz' ) ) );

		$this->assertEquals(
			new StatementList( array( $statement0, $statement1, $statement2 ) ),
			new StatementList( $statement0, $statement1, $statement2 )
		);
	}

	public function testGivenArgumentListWithNonStatement_constructorThrowsException() {
		$statement0 = new Statement( new Claim( $this->newSnak( 42, 'foo' ) ) );
		$statement1 = new Statement( new Claim( $this->newSnak( 42, 'bar' ) ) );
		$statement2 = new Statement( new Claim( $this->newSnak( 42, 'baz' ) ) );

		$this->setExpectedException( 'InvalidArgumentException' );
		new StatementList( $statement0, $statement1, array(), $statement2 );
	}

	public function testCountForEmptyList() {
		$list = new StatementList();
		$this->assertSame( 0, count( $list ) );
		$this->assertSame( 0, $list->count() );
	}

	public function testCountForNonEmptyList() {
		$list = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' )
		);

		$this->assertSame( 2, $list->count() );
	}

	/**
	 * @dataProvider statementArrayProvider
	 */
	public function testGivenIdenticalLists_equalsReturnsTrue( array $statements ) {
		$firstStatements = new StatementList( $statements );
		$secondStatements = new StatementList( $statements );

		$this->assertTrue( $firstStatements->equals( $secondStatements ) );
	}

	public function statementArrayProvider() {
		return array(
			array( array(
				$this->getStatementWithSnak( 1, 'foo' ),
				$this->getStatementWithSnak( 2, 'bar' ),
			) ),
			array( array(
				$this->getStatementWithSnak( 1, 'foo' ),
			) ),
			array( array(
			) ),
		);
	}

	public function testGivenDifferentLists_equalsReturnsFalse() {
		$firstStatements = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' )
		);

		$secondStatements = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'SPAM' )
		);

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testGivenListsWithDifferentDuplicates_equalsReturnsFalse() {
		$firstStatements = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' )
		);

		$secondStatements = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' ),
			$this->getStatementWithSnak( 2, 'bar' )
		);

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testGivenListsWithDifferentOrder_equalsReturnsFalse() {
		$firstStatements = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' ),
			$this->getStatementWithSnak( 3, 'baz' )
		);

		$secondStatements = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 3, 'baz' ),
			$this->getStatementWithSnak( 2, 'bar' )
		);

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testEmptyListDoesNotEqualNonEmptyList() {
		$firstStatements = new StatementList();

		$secondStatements = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 3, 'baz' ),
			$this->getStatementWithSnak( 2, 'bar' )
		);

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testNonEmptyListDoesNotEqualEmptyList() {
		$firstStatements = new StatementList(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 3, 'baz' ),
			$this->getStatementWithSnak( 2, 'bar' )
		);

		$secondStatements = new StatementList();

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testEmptyListIsEmpty() {
		$list = new StatementList();

		$this->assertTrue( $list->isEmpty() );
	}

	public function testNonEmptyListIsNotEmpty() {
		$list = new StatementList( $this->getStatementWithSnak( 1, 'foo' ) );

		$this->assertFalse( $list->isEmpty() );
	}

	public function testGetMainSnaks() {
		$list = new StatementList();

		$list->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$list->addNewStatement( new PropertyNoValueSnak( 1337 ), array( new PropertyNoValueSnak( 32202 ) ) );
		$list->addNewStatement( new PropertyNoValueSnak( 9001 ) );

		$this->assertEquals(
			array(
				new PropertyNoValueSnak( 42 ),
				new PropertyNoValueSnak( 1337 ),
				new PropertyNoValueSnak( 9001 ),
			),
			$list->getMainSnaks()
		);
	}

	public function testGivenNotKnownPropertyId_getWithPropertyIdReturnsEmptyList() {
		$list = new StatementList();
		$list->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertEquals(
			new StatementList(),
			$list->getWithPropertyId( new PropertyId( 'P2' ) )
		);
	}

	public function testGivenKnownPropertyId_getWithPropertyIdReturnsListWithOnlyMatchingStatements() {
		$list = new StatementList();
		$list->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$list->addNewStatement( new PropertyNoValueSnak( 9001 ) );
		$list->addNewStatement( new PropertySomeValueSnak( 42 ) );
		$list->addNewStatement( new PropertySomeValueSnak( 9001 ) );

		$expected = new StatementList();
		$expected->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$expected->addNewStatement( new PropertySomeValueSnak( 42 ) );

		$this->assertEquals(
			$expected,
			$list->getWithPropertyId( new PropertyId( 'P42' ) )
		);
	}

	public function testGivenInvalidRank_getWithRankReturnsEmptyList() {
		$list = new StatementList();
		$this->assertEquals( new StatementList(), $list->getWithRank( 42 ) );
	}

	public function testGivenValidRank_getWithRankReturnsOnlyMatchingStatements() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement->setRank( Statement::RANK_PREFERRED );

		$secondStatement = new Statement( new Claim( new PropertyNoValueSnak( 1337 ) ) );
		$secondStatement->setRank( Statement::RANK_NORMAL );

		$thirdStatement = new Statement( new Claim( new PropertyNoValueSnak( 9001 ) ) );
		$thirdStatement->setRank( Statement::RANK_DEPRECATED );

		$list = new StatementList( $statement, $secondStatement, $thirdStatement );

		$this->assertEquals(
			new StatementList( $statement ),
			$list->getWithRank( Statement::RANK_PREFERRED )
		);

		$this->assertEquals(
			new StatementList( $secondStatement, $thirdStatement ),
			$list->getWithRank( array( Statement::RANK_NORMAL, Statement::RANK_DEPRECATED ) )
		);
	}

	public function testWhenListIsEmpty_getBestStatementsReturnsEmptyList() {
		$list = new StatementList();
		$this->assertEquals( new StatementList(), $list->getBestStatements() );
	}

	public function testWhenOnlyDeprecatedStatements_getBestStatementsReturnsEmptyList() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement->setRank( Statement::RANK_DEPRECATED );

		$secondStatement = new Statement( new Claim( new PropertyNoValueSnak( 9001 ) ) );
		$secondStatement->setRank( Statement::RANK_DEPRECATED );

		$list = new StatementList( $statement, $secondStatement );
		$this->assertEquals( new StatementList(), $list->getBestStatements() );
	}

	public function testWhenPreferredStatements_getBestStatementsReturnsOnlyThose() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement->setRank( Statement::RANK_PREFERRED );

		$secondStatement = new Statement( new Claim( new PropertyNoValueSnak( 1337 ) ) );
		$secondStatement->setRank( Statement::RANK_NORMAL );

		$thirdStatement = new Statement( new Claim( new PropertyNoValueSnak( 9001 ) ) );
		$thirdStatement->setRank( Statement::RANK_DEPRECATED );

		$fourthStatement = new Statement( new Claim( new PropertyNoValueSnak( 23 ) ) );
		$fourthStatement->setRank( Statement::RANK_PREFERRED );

		$list = new StatementList( $statement, $secondStatement, $thirdStatement, $fourthStatement );
		$this->assertEquals(
			new StatementList( $statement, $fourthStatement ),
			$list->getBestStatements()
		);
	}

	public function testWhenNoPreferredStatements_getBestStatementsReturnsOnlyNormalOnes() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement->setRank( Statement::RANK_NORMAL );

		$secondStatement = new Statement( new Claim( new PropertyNoValueSnak( 1337 ) ) );
		$secondStatement->setRank( Statement::RANK_NORMAL );

		$thirdStatement = new Statement( new Claim( new PropertyNoValueSnak( 9001 ) ) );
		$thirdStatement->setRank( Statement::RANK_DEPRECATED );

		$list = new StatementList( $statement, $secondStatement, $thirdStatement );
		$this->assertEquals(
			new StatementList( $statement, $secondStatement ),
			$list->getBestStatements()
		);
	}

}
