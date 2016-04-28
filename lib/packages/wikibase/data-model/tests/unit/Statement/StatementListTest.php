<?php

namespace Wikibase\DataModel\Tests\Statement;

use ArrayObject;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\ReferencedStatementFilter;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Statement\StatementList
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class StatementListTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param int $propertyId
	 * @param string|null $guid
	 * @param int $rank
	 *
	 * @return Statement
	 */
	private function getStatement( $propertyId, $guid, $rank = Statement::RANK_NORMAL ) {
		$statement = $this->getMockBuilder( 'Wikibase\DataModel\Statement\Statement' )
			->disableOriginalConstructor()
			->getMock();

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

	private function getStatementWithSnak( $propertyId, $stringValue ) {
		$snak = $this->newSnak( $propertyId, $stringValue );
		$statement = new Statement( $snak );
		$statement->setGuid( sha1( $snak->getHash() ) );
		return $statement;
	}

	private function newSnak( $propertyId, $stringValue ) {
		return new PropertyValueSnak( $propertyId, new StringValue( $stringValue ) );
	}

	public function testConstructorAcceptsDuplicatesWithNoGuid() {
		$list = new StatementList(
			$this->getStatement( 1, null ),
			$this->getStatement( 1, null )
		);

		$this->assertSame( 2, $list->count() );
	}

	public function testConstructorAcceptsDuplicatesWithSameGuid() {
		$list = new StatementList(
			$this->getStatement( 1, 'duplicate' ),
			$this->getStatement( 1, 'duplicate' )
		);

		$this->assertSame( 2, $list->count() );
	}

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

	public function testGivenStatementsWithArrayKeys_toArrayReturnsReindexedArray() {
		$statement = $this->getStatement( 1, 'guid' );
		$list = new StatementList( array( 'ignore-me' => $statement ) );

		$this->assertSame( array( 0 => $statement ), $list->toArray() );
	}

	public function testGivenSparseArray_toArrayReturnsReindexedArray() {
		$statement = $this->getStatement( 1, 'guid' );
		$list = new StatementList( array( 1 => $statement ) );

		$this->assertSame( array( 0 => $statement ), $list->toArray() );
	}

	public function testCanIterate() {
		$statement = $this->getStatement( 1, 'kittens' );
		$list = new StatementList( $statement );

		foreach ( $list as $statementFormList ) {
			$this->assertEquals( $statement, $statementFormList );
		}
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

	public function testAddStatementWithOnlyMainSnak() {
		$list = new StatementList();

		$list->addNewStatement( $this->newSnak( 42, 'foo' ) );

		$this->assertEquals(
			new StatementList( new Statement( $this->newSnak( 42, 'foo' ) ) ),
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
				new Statement(
					$this->newSnak( 42, 'foo' ),
					new SnakList( array(
						$this->newSnak( 1, 'bar' )
					) )
				)
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
			new StatementList( new Statement( $this->newSnak( 42, 'foo' ), $snakList ) ),
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

		$statement = new Statement( $this->newSnak( 42, 'foo' ) );

		$statement->setGuid( 'kittens' );

		$this->assertEquals( new StatementList( $statement ), $list );
	}

	public function testGivenNegativeIndex_addStatementFails() {
		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$list = new StatementList();

		$this->setExpectedException( 'InvalidArgumentException' );
		$list->addStatement( $statement, -1 );
	}

	public function testGivenLargeIndex_addStatementAppends() {
		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$list = new StatementList();

		$list->addStatement( $statement, 1000 );
		$this->assertEquals( new StatementList( $statement ), $list );
	}

	public function testGivenZeroIndex_addStatementPrepends() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );
		$list = new StatementList( $statement2 );

		$list->addStatement( $statement1, 0 );
		$this->assertEquals( new StatementList( $statement1, $statement2 ), $list );
	}

	public function testGivenValidIndex_addStatementInserts() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );
		$statement3 = new Statement( new PropertyNoValueSnak( 3 ) );
		$list = new StatementList( $statement1, $statement3 );

		$list->addStatement( $statement2, 1 );
		$this->assertEquals( new StatementList( $statement1, $statement2, $statement3 ), $list );
		$this->assertSame( array( 0, 1, 2 ), array_keys( $list->toArray() ), 'array keys' );
	}

	public function testGivenGuidOfPresentStatement_statementIsRemoved() {
		$statement1 = new Statement( $this->newSnak( 24, 'foo' ), null, null, 'foo' );
		$statement2 = new Statement( $this->newSnak( 32, 'bar' ), null, null, 'bar' );
		$statement3 = new Statement( $this->newSnak( 32, 'bar' ), null, null, 'bar' );

		$list = new StatementList( array( $statement1, $statement2, $statement3 ) );
		$list->removeStatementsWithGuid( 'foo' );

		$this->assertEquals( new StatementList( $statement2, $statement3 ), $list );
	}

	public function testGivenGuidOfMultipleStatements_multipleStatementsAreRemoved() {
		$statement1 = new Statement( $this->newSnak( 24, 'foo' ), null, null, 'foo' );
		$statement2 = new Statement( $this->newSnak( 32, 'bar' ), null, null, 'bar' );
		$statement3 = new Statement( $this->newSnak( 32, 'bar' ), null, null, 'bar' );

		$list = new StatementList( array( $statement1, $statement2, $statement3 ) );
		$list->removeStatementsWithGuid( 'bar' );

		$this->assertEquals( new StatementList( $statement1 ), $list );
	}

	public function testGivenNotPresentGuid_listIsNotModified() {
		$statement1 = new Statement( $this->newSnak( 24, 'foo' ), null, null, 'foo' );
		$statement2 = new Statement( $this->newSnak( 32, 'bar' ), null, null, 'bar' );
		$statement3 = new Statement( $this->newSnak( 32, 'bar' ), null, null, 'bar' );

		$list = new StatementList( array( $statement1, $statement2, $statement3 ) );
		$list->removeStatementsWithGuid( 'baz' );

		$this->assertEquals( new StatementList( $statement1, $statement2, $statement3 ), $list );
	}

	public function testGivenNullGuid_allStatementsWithNoGuidAreRemoved() {
		$statement1 = new Statement( $this->newSnak( 24, 'foo' ), null, null, 'foo' );
		$statement2 = new Statement( $this->newSnak( 32, 'bar' ) );
		$statement3 = new Statement( $this->newSnak( 32, 'bar' ) );

		$list = new StatementList( array( $statement1, $statement2, $statement3 ) );
		$list->removeStatementsWithGuid( null );

		$this->assertEquals( new StatementList( $statement1 ), $list );
	}

	public function testCanConstructWithTraversableContainingOnlyStatements() {
		$statementArray = array(
			$this->getStatementWithSnak( 1, 'foo' ),
			$this->getStatementWithSnak( 2, 'bar' ),
		);

		$object = new ArrayObject( $statementArray );
		$list = new StatementList( $object );

		$this->assertEquals(
			$statementArray,
			array_values( $list->toArray() )
		);
	}

	public function testGivenTraversableWithNonStatements_constructorThrowsException() {
		$traversable = new ArrayObject( array(
			$this->getStatementWithSnak( 1, 'foo' ),
			new \stdClass(),
			$this->getStatementWithSnak( 2, 'bar' ),
		) );

		$this->setExpectedException( 'InvalidArgumentException' );
		new StatementList( $traversable );
	}

	public function testGivenNonTraversableOrArgList_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new StatementList( null );
	}

	public function testCanConstructWithStatement() {
		$statement = new Statement( $this->newSnak( 42, 'foo' ) );

		$this->assertEquals(
			new StatementList( array( $statement ) ),
			new StatementList( $statement )
		);
	}

	public function testCanConstructWithStatementArgumentList() {
		$statement0 = new Statement( $this->newSnak( 42, 'foo' ) );
		$statement1 = new Statement( $this->newSnak( 42, 'bar' ) );
		$statement2 = new Statement( $this->newSnak( 42, 'baz' ) );

		$this->assertEquals(
			new StatementList( array( $statement0, $statement1, $statement2 ) ),
			new StatementList( $statement0, $statement1, $statement2 )
		);
	}

	public function testGivenArgumentListWithNonStatement_constructorThrowsException() {
		$statement0 = new Statement( $this->newSnak( 42, 'foo' ) );
		$statement1 = new Statement( $this->newSnak( 42, 'bar' ) );
		$statement2 = new Statement( $this->newSnak( 42, 'baz' ) );

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

	public function testGivenNotKnownPropertyId_getByPropertyIdReturnsEmptyList() {
		$list = new StatementList();
		$list->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertEquals(
			new StatementList(),
			$list->getByPropertyId( new PropertyId( 'P2' ) )
		);
	}

	public function testGivenKnownPropertyId_getByPropertyIdReturnsListWithOnlyMatchingStatements() {
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
			$list->getByPropertyId( new PropertyId( 'P42' ) )
		);
	}

	public function testGivenInvalidRank_getByRankReturnsEmptyList() {
		$list = new StatementList();
		$this->assertEquals( new StatementList(), $list->getByRank( 42 ) );
	}

	public function testGivenValidRank_getByRankReturnsOnlyMatchingStatements() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_PREFERRED );

		$secondStatement = new Statement( new PropertyNoValueSnak( 1337 ) );
		$secondStatement->setRank( Statement::RANK_NORMAL );

		$thirdStatement = new Statement( new PropertyNoValueSnak( 9001 ) );
		$thirdStatement->setRank( Statement::RANK_DEPRECATED );

		$list = new StatementList( $statement, $secondStatement, $thirdStatement );

		$this->assertEquals(
			new StatementList( $statement ),
			$list->getByRank( Statement::RANK_PREFERRED )
		);

		$this->assertEquals(
			new StatementList( $secondStatement, $thirdStatement ),
			$list->getByRank( array( Statement::RANK_NORMAL, Statement::RANK_DEPRECATED ) )
		);
	}

	public function testWhenListIsEmpty_getBestStatementsReturnsEmptyList() {
		$list = new StatementList();
		$this->assertEquals( new StatementList(), $list->getBestStatements() );
	}

	public function testWhenOnlyDeprecatedStatements_getBestStatementsReturnsEmptyList() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_DEPRECATED );

		$secondStatement = new Statement( new PropertyNoValueSnak( 9001 ) );
		$secondStatement->setRank( Statement::RANK_DEPRECATED );

		$list = new StatementList( $statement, $secondStatement );
		$this->assertEquals( new StatementList(), $list->getBestStatements() );
	}

	public function testWhenPreferredStatements_getBestStatementsReturnsOnlyThose() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_PREFERRED );

		$secondStatement = new Statement( new PropertyNoValueSnak( 1337 ) );
		$secondStatement->setRank( Statement::RANK_NORMAL );

		$thirdStatement = new Statement( new PropertyNoValueSnak( 9001 ) );
		$thirdStatement->setRank( Statement::RANK_DEPRECATED );

		$fourthStatement = new Statement( new PropertyNoValueSnak( 23 ) );
		$fourthStatement->setRank( Statement::RANK_PREFERRED );

		$list = new StatementList( $statement, $secondStatement, $thirdStatement, $fourthStatement );
		$this->assertEquals(
			new StatementList( $statement, $fourthStatement ),
			$list->getBestStatements()
		);
	}

	public function testWhenNoPreferredStatements_getBestStatementsReturnsOnlyNormalOnes() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_NORMAL );

		$secondStatement = new Statement( new PropertyNoValueSnak( 1337 ) );
		$secondStatement->setRank( Statement::RANK_NORMAL );

		$thirdStatement = new Statement( new PropertyNoValueSnak( 9001 ) );
		$thirdStatement->setRank( Statement::RANK_DEPRECATED );

		$list = new StatementList( $statement, $secondStatement, $thirdStatement );
		$this->assertEquals(
			new StatementList( $statement, $secondStatement ),
			$list->getBestStatements()
		);
	}

	public function testGivenNotPresentStatement_getFirstStatementWithGuidReturnsNull() {
		$statements = new StatementList();

		$this->assertNull( $statements->getFirstStatementWithGuid( 'kittens' ) );
	}

	public function testGivenPresentStatement_getFirstStatementWithGuidReturnsStatement() {
		$statement1 = $this->getStatement( 1, 'guid1' );
		$statement2 = $this->getStatement( 2, 'guid2' );
		$statement3 = $this->getStatement( 3, 'guid3' );
		$statements = new StatementList( $statement1, $statement2, $statement3 );

		$actual = $statements->getFirstStatementWithGuid( 'guid2' );
		$this->assertSame( $statement2, $actual );
	}

	public function testGivenDoublyPresentStatement_getFirstStatementWithGuidReturnsFirstMatch() {
		$statement1 = $this->getStatement( 1, 'guid1' );
		$statement2 = $this->getStatement( 2, 'guid2' );
		$statement3 = $this->getStatement( 3, 'guid3' );
		$statement4 = $this->getStatement( 2, 'guid2' );
		$statements = new StatementList( $statement1, $statement2, $statement3, $statement4 );

		$actual = $statements->getFirstStatementWithGuid( 'guid2' );
		$this->assertSame( $statement2, $actual );
	}

	public function testGivenStatementsWithNoGuid_getFirstStatementWithGuidReturnsFirstMatch() {
		$statement1 = $this->getStatement( 1, null );
		$statement2 = $this->getStatement( 2, null );
		$statements = new StatementList( $statement1, $statement2 );

		$actual = $statements->getFirstStatementWithGuid( null );
		$this->assertSame( $statement1, $actual );
	}

	public function testGivenInvalidGuid_getFirstStatementWithGuidReturnsNull() {
		$statements = new StatementList();

		$this->assertNull( $statements->getFirstStatementWithGuid( false ) );
	}

	public function testFilter() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );
		$statement3 = new Statement( new PropertyNoValueSnak( 3 ) );
		$statement4 = new Statement( new PropertyNoValueSnak( 4 ) );

		$statement2->setReferences( new ReferenceList( array(
			new Reference( array( new PropertyNoValueSnak( 20 ) ) )
		) ) );

		$statement3->setReferences( new ReferenceList( array(
			new Reference( array( new PropertyNoValueSnak( 30 ) ) )
		) ) );

		$statements = new StatementList( $statement1, $statement2, $statement3, $statement4 );

		$this->assertEquals(
			new StatementList( $statement2, $statement3 ),
			$statements->filter( new ReferencedStatementFilter() )
		);
	}

}
