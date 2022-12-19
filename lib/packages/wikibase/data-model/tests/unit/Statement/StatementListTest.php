<?php declare( strict_types=1 );

namespace Wikibase\DataModel\Tests\Statement;

use ArrayObject;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementGuidChangedException;
use Wikibase\DataModel\Exception\StatementNotFoundException;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\ReferencedStatementFilter;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\DataModel\Statement\StatementList
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class StatementListTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param string $propertyId
	 * @param string|null $guid
	 * @param int $rank
	 *
	 * @return Statement
	 */
	private function getStatement(
		string $propertyId, ?string $guid, int $rank = Statement::RANK_NORMAL
	): Statement {
		$statement = $this->createMock( Statement::class );

		$statement->expects( $this->any() )
			->method( 'getGuid' )
			->will( $this->returnValue( $guid ) );

		$statement->expects( $this->any() )
			->method( 'getPropertyId' )
			->will( $this->returnValue( new NumericPropertyId( $propertyId ) ) );

		$statement->expects( $this->any() )
			->method( 'getRank' )
			->will( $this->returnValue( $rank ) );

		return $statement;
	}

	/**
	 * @param string $propertyId
	 * @param string $stringValue
	 *
	 * @return Statement
	 */
	private function getStatementWithSnak( string $propertyId, string $stringValue ): Statement {
		$snak = $this->newSnak( $propertyId, $stringValue );
		$statement = new Statement( $snak );
		$statement->setGuid( sha1( $snak->getHash() ) );
		return $statement;
	}

	/**
	 * @param string $propertyId
	 * @param string $stringValue
	 *
	 * @return Snak
	 */
	private function newSnak( string $propertyId, string $stringValue ) {
		return new PropertyValueSnak(
			new NumericPropertyId( $propertyId ),
			new StringValue( $stringValue )
		);
	}

	public function testConstructorAcceptsDuplicatesWithNoGuid() {
		$list = new StatementList(
			$this->getStatement( 'P1', null ),
			$this->getStatement( 'P1', null )
		);

		$this->assertSame( 2, $list->count() );
	}

	public function testConstructorAcceptsDuplicatesWithSameGuid() {
		$list = new StatementList(
			$this->getStatement( 'P1', 'duplicate' ),
			$this->getStatement( 'P1', 'duplicate' )
		);

		$this->assertSame( 2, $list->count() );
	}

	public function testGivenNoStatements_getPropertyIdsReturnsEmptyArray() {
		$list = new StatementList();
		$this->assertSame( [], $list->getPropertyIds() );
		$this->assertSame( [], $list->toArray() );
	}

	public function testGivenStatements_getPropertyIdsReturnsArrayWithoutDuplicates() {
		$list = new StatementList(
			$this->getStatement( 'P1', 'kittens' ),
			$this->getStatement( 'P3', 'foo' ),
			$this->getStatement( 'P2', 'bar' ),
			$this->getStatement( 'P2', 'baz' ),
			$this->getStatement( 'P1', 'bah' )
		);

		$this->assertEquals(
			[
				'P1' => new NumericPropertyId( 'P1' ),
				'P3' => new NumericPropertyId( 'P3' ),
				'P2' => new NumericPropertyId( 'P2' ),
			],
			$list->getPropertyIds()
		);
	}

	public function testCanIterate() {
		$statement = $this->getStatement( 'P1', 'kittens' );
		$list = new StatementList( $statement );

		foreach ( $list as $statementFormList ) {
			$this->assertSame( $statement, $statementFormList );
		}
	}

	public function testGetUniqueMainSnaksReturnsListWithoutDuplicates() {
		$list = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'foo' ),
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'bar' ),
			$this->getStatementWithSnak( 'P1', 'bar' )
		);

		$this->assertEquals(
			[
				$this->getStatementWithSnak( 'P1', 'foo' ),
				$this->getStatementWithSnak( 'P2', 'foo' ),
				$this->getStatementWithSnak( 'P2', 'bar' ),
				$this->getStatementWithSnak( 'P1', 'bar' ),
			],
			array_values( $list->getWithUniqueMainSnaks()->toArray() )
		);
	}

	public function testGetAllSnaksReturnsAllSnaks() {
		$list = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'foo' ),
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'bar' ),
			$this->getStatementWithSnak( 'P1', 'bar' )
		);

		$this->assertEquals(
			[
				$this->newSnak( 'P1', 'foo' ),
				$this->newSnak( 'P2', 'foo' ),
				$this->newSnak( 'P1', 'foo' ),
				$this->newSnak( 'P2', 'bar' ),
				$this->newSnak( 'P1', 'bar' ),
			],
			$list->getAllSnaks()
		);
	}

	public function testAddStatementWithOnlyMainSnak() {
		$list = new StatementList();

		$list->addNewStatement( $this->newSnak( 'P42', 'foo' ) );

		$this->assertEquals(
			new StatementList( new Statement( $this->newSnak( 'P42', 'foo' ) ) ),
			$list
		);
	}

	public function testAddStatementWithQualifiersAsSnakArray() {
		$list = new StatementList();

		$list->addNewStatement(
			$this->newSnak( 'P42', 'foo' ),
			[
				$this->newSnak( 'P1', 'bar' ),
			]
		);

		$this->assertEquals(
			new StatementList(
				new Statement(
					$this->newSnak( 'P42', 'foo' ),
					new SnakList( [
						$this->newSnak( 'P1', 'bar' ),
					] )
				)
			),
			$list
		);
	}

	public function testAddStatementWithQualifiersAsSnakList() {
		$list = new StatementList();
		$snakList = new SnakList( [
			$this->newSnak( 'P1', 'bar' ),
		] );

		$list->addNewStatement(
			$this->newSnak( 'P42', 'foo' ),
			$snakList
		);

		$this->assertEquals(
			new StatementList( new Statement( $this->newSnak( 'P42', 'foo' ), $snakList ) ),
			$list
		);
	}

	public function testAddStatementWithGuid() {
		$list = new StatementList();

		$list->addNewStatement(
			$this->newSnak( 'P42', 'foo' ),
			null,
			null,
			'kittens'
		);

		$statement = new Statement( $this->newSnak( 'P42', 'foo' ) );

		$statement->setGuid( 'kittens' );

		$this->assertEquals( new StatementList( $statement ), $list );
	}

	public function testGivenNegativeIndex_addStatementFails() {
		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$list = new StatementList();

		$this->expectException( InvalidArgumentException::class );
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
		$this->assertSame( [ 0, 1, 2 ], array_keys( $list->toArray() ), 'array keys' );
	}

	public function testReplaceStatement() {
		$statementGuid = new StatementGuid( new ItemId( 'Q123' ), 'AAA-BBB-CCC' );
		$index = 2;
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );
		$statement3 = new Statement( new PropertyNoValueSnak( 3 ) );
		$oldStatement = new Statement(
			$this->newSnak( 'P42', 'foo' ),
			null,
			null,
			(string)$statementGuid
		);
		$newStatement = new Statement( $this->newSnak( 'P42', 'bar' ) );

		$list = new StatementList( $statement1, $statement2, $statement3 );
		$list->addStatement( $oldStatement, $index );

		$list->replaceStatement( $statementGuid, $newStatement );

		$this->assertEquals( 4, $list->count() );
		$replacedStatement = $list->toArray()[$index];
		$this->assertEquals( $newStatement, $replacedStatement );
		$this->assertEquals( (string)$statementGuid, $replacedStatement->getGuid() );
	}

	public function testGivenNotPresentGuid_replaceStatementThrows() {
		$list = new StatementList();
		$statementId = new StatementGuid( new ItemId( 'Q42' ), 'this-guid-does-not-exist' );

		$this->expectException( StatementNotFoundException::class );
		$this->expectExceptionMessageMatches( '/' . preg_quote( (string)$statementId ) . '/' );

		$list->replaceStatement( $statementId, new Statement( new PropertyNoValueSnak( 42 ) ) );
	}

	public function testGivenNewStatementWithDifferentStatementId_replaceStatementThrows(): void {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAA-BBB-CCC' );
		$originalStatement = new Statement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ),
			null,
			null,
			(string)$statementId
		);
		$newStatement = new Statement(
			new PropertySomeValueSnak( new NumericPropertyId( 'P321' ) ),
			null,
			null,
			'Q123$XXX-YYY-ZZZ'
		);
		$list = new StatementList( $originalStatement );

		$this->expectException( StatementGuidChangedException::class );
		$this->expectExceptionMessage(
			'The new Statement must not have a different Statement GUID than the original'
		);

		$list->replaceStatement( $statementId, $newStatement );
	}

	public function testGivenNewStatementWithDifferentProperty_replaceStatementThrows(): void {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAA-BBB-CCC' );
		$originalStatement = new Statement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P123' ) ),
			null,
			null,
			(string)$statementId
		);
		$newStatement = new Statement( new PropertySomeValueSnak( new NumericPropertyId( 'P321' ) ) );
		$list = new StatementList( $originalStatement );

		$this->expectException( PropertyChangedException::class );
		$this->expectExceptionMessage( 'The new Statement must not have a different Property than the original' );

		$list->replaceStatement( $statementId, $newStatement );
	}

	public function testGivenGuidOfPresentStatement_statementIsRemoved() {
		$statement1 = new Statement( $this->newSnak( 'P24', 'foo' ), null, null, 'foo' );
		$statement2 = new Statement( $this->newSnak( 'P32', 'bar' ), null, null, 'bar' );
		$statement3 = new Statement( $this->newSnak( 'P32', 'bar' ), null, null, 'bar' );

		$list = new StatementList( $statement1, $statement2, $statement3 );
		$list->removeStatementsWithGuid( 'foo' );

		$this->assertEquals( new StatementList( $statement2, $statement3 ), $list );
	}

	public function testGivenGuidOfMultipleStatements_multipleStatementsAreRemoved() {
		$statement1 = new Statement( $this->newSnak( 'P24', 'foo' ), null, null, 'foo' );
		$statement2 = new Statement( $this->newSnak( 'P32', 'bar' ), null, null, 'bar' );
		$statement3 = new Statement( $this->newSnak( 'P32', 'bar' ), null, null, 'bar' );

		$list = new StatementList( $statement1, $statement2, $statement3 );
		$list->removeStatementsWithGuid( 'bar' );

		$this->assertEquals( new StatementList( $statement1 ), $list );
	}

	public function testGivenNotPresentGuid_listIsNotModified() {
		$statement1 = new Statement( $this->newSnak( 'P24', 'foo' ), null, null, 'foo' );
		$statement2 = new Statement( $this->newSnak( 'P32', 'bar' ), null, null, 'bar' );
		$statement3 = new Statement( $this->newSnak( 'P32', 'bar' ), null, null, 'bar' );

		$list = new StatementList( $statement1, $statement2, $statement3 );
		$list->removeStatementsWithGuid( 'baz' );

		$this->assertEquals( new StatementList( $statement1, $statement2, $statement3 ), $list );
	}

	public function testGivenNullGuid_allStatementsWithNoGuidAreRemoved() {
		$statement1 = new Statement( $this->newSnak( 'P24', 'foo' ), null, null, 'foo' );
		$statement2 = new Statement( $this->newSnak( 'P32', 'bar' ) );
		$statement3 = new Statement( $this->newSnak( 'P32', 'bar' ) );

		$list = new StatementList( $statement1, $statement2, $statement3 );
		$list->removeStatementsWithGuid( null );

		$this->assertEquals( new StatementList( $statement1 ), $list );
	}

	public function testCanConstructWithUnpackedTraversableContainingOnlyStatements() {
		$statementArray = [
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'bar' ),
		];

		$object = new ArrayObject( $statementArray );
		/** @noinspection PhpParamsInspection */
		$list = new StatementList( ...$object );

		$this->assertSame(
			$statementArray,
			array_values( $list->toArray() )
		);
	}

	public function testCanConstructWithStatement() {
		$statement = new Statement( $this->newSnak( 'P42', 'foo' ) );

		$this->assertEquals(
			new StatementList( $statement ),
			new StatementList( $statement )
		);
	}

	public function testCanConstructWithStatementArgumentList() {
		$statement0 = new Statement( $this->newSnak( 'P42', 'foo' ) );
		$statement1 = new Statement( $this->newSnak( 'P42', 'bar' ) );
		$statement2 = new Statement( $this->newSnak( 'P42', 'baz' ) );

		$this->assertEquals(
			new StatementList( $statement0, $statement1, $statement2 ),
			new StatementList( $statement0, $statement1, $statement2 )
		);
	}

	public function testCountForEmptyList() {
		$list = new StatementList();
		$this->assertCount( 0, $list );
		$this->assertSame( 0, $list->count() );
	}

	public function testCountForNonEmptyList() {
		$list = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'bar' )
		);

		$this->assertSame( 2, $list->count() );
	}

	/**
	 * @dataProvider provideArrayOfStatements
	 */
	public function testGivenIdenticalLists_equalsReturnsTrue( array $statements ) {
		$firstStatements = new StatementList( ...$statements );
		$secondStatements = new StatementList( ...$statements );

		$this->assertTrue( $firstStatements->equals( $secondStatements ) );
	}

	public function provideArrayOfStatements(): array {
		return [
			"two statements" => [
				[
					$this->getStatementWithSnak( 'P1', 'foo' ),
					$this->getStatementWithSnak( 'P2', 'bar' ),
				],
			],
			"one statement" => [ [ $this->getStatementWithSnak( 'P1', 'foo' ) ] ],
			"no statements (empty array)" => [ [] ],
		];
	}

	public function testGivenDifferentLists_equalsReturnsFalse() {
		$firstStatements = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'bar' )
		);

		$secondStatements = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'SPAM' )
		);

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testGivenListsWithDifferentDuplicates_equalsReturnsFalse() {
		$firstStatements = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'bar' )
		);

		$secondStatements = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'bar' ),
			$this->getStatementWithSnak( 'P2', 'bar' )
		);

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testGivenListsWithDifferentOrder_equalsReturnsFalse() {
		$firstStatements = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P2', 'bar' ),
			$this->getStatementWithSnak( 'P3', 'baz' )
		);

		$secondStatements = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P3', 'baz' ),
			$this->getStatementWithSnak( 'P2', 'bar' )
		);

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testEmptyListDoesNotEqualNonEmptyList() {
		$firstStatements = new StatementList();

		$secondStatements = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P3', 'baz' ),
			$this->getStatementWithSnak( 'P2', 'bar' )
		);

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testNonEmptyListDoesNotEqualEmptyList() {
		$firstStatements = new StatementList(
			$this->getStatementWithSnak( 'P1', 'foo' ),
			$this->getStatementWithSnak( 'P3', 'baz' ),
			$this->getStatementWithSnak( 'P2', 'bar' )
		);

		$secondStatements = new StatementList();

		$this->assertFalse( $firstStatements->equals( $secondStatements ) );
	}

	public function testEmptyListIsEmpty() {
		$list = new StatementList();

		$this->assertTrue( $list->isEmpty() );
	}

	public function testNonEmptyListIsNotEmpty() {
		$list = new StatementList( $this->getStatementWithSnak( 'P1', 'foo' ) );

		$this->assertFalse( $list->isEmpty() );
	}

	public function testGetMainSnaks() {
		$list = new StatementList();

		$list->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$list->addNewStatement( new PropertyNoValueSnak( 1337 ), [ new PropertyNoValueSnak( 32202 ) ] );
		$list->addNewStatement( new PropertyNoValueSnak( 9001 ) );

		$this->assertEquals(
			[
				new PropertyNoValueSnak( 42 ),
				new PropertyNoValueSnak( 1337 ),
				new PropertyNoValueSnak( 9001 ),
			],
			$list->getMainSnaks()
		);
	}

	public function testGivenNotKnownPropertyId_getByPropertyIdReturnsEmptyList() {
		$list = new StatementList();
		$list->addNewStatement( new PropertyNoValueSnak( 42 ) );

		$this->assertEquals(
			new StatementList(),
			$list->getByPropertyId( new NumericPropertyId( 'P2' ) )
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
			$list->getByPropertyId( new NumericPropertyId( 'P42' ) )
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
			$list->getByRank( [ Statement::RANK_NORMAL, Statement::RANK_DEPRECATED ] )
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
		$statement1 = $this->getStatement( 'P1', 'guid1' );
		$statement2 = $this->getStatement( 'P2', 'guid2' );
		$statement3 = $this->getStatement( 'P3', 'guid3' );
		$statements = new StatementList( $statement1, $statement2, $statement3 );

		$actual = $statements->getFirstStatementWithGuid( 'guid2' );
		$this->assertSame( $statement2, $actual );
	}

	public function testGivenDoublyPresentStatement_getFirstStatementWithGuidReturnsFirstMatch() {
		$statement1 = $this->getStatement( 'P1', 'guid1' );
		$statement2 = $this->getStatement( 'P2', 'guid2' );
		$statement3 = $this->getStatement( 'P3', 'guid3' );
		$statement4 = $this->getStatement( 'P2', 'guid2' );
		$statements = new StatementList( $statement1, $statement2, $statement3, $statement4 );

		$actual = $statements->getFirstStatementWithGuid( 'guid2' );
		$this->assertSame( $statement2, $actual );
	}

	public function testGivenStatementsWithNoGuid_getFirstStatementWithGuidReturnsFirstMatch() {
		$statement1 = $this->getStatement( 'P1', null );
		$statement2 = $this->getStatement( 'P2', null );
		$statements = new StatementList( $statement1, $statement2 );

		$actual = $statements->getFirstStatementWithGuid( null );
		$this->assertSame( $statement1, $actual );
	}

	public function testGivenInvalidGuid_getFirstStatementWithGuidReturnsNull() {
		$statements = new StatementList();

		$this->assertNull( $statements->getFirstStatementWithGuid( 'not-a-valid-guid' ) );
	}

	public function testFilter() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );
		$statement3 = new Statement( new PropertyNoValueSnak( 3 ) );
		$statement4 = new Statement( new PropertyNoValueSnak( 4 ) );

		$statement2->setReferences(
			new ReferenceList( [
				new Reference( [ new PropertyNoValueSnak( 20 ) ] ),
			] )
		);

		$statement3->setReferences(
			new ReferenceList( [
				new Reference( [ new PropertyNoValueSnak( 30 ) ] ),
			] )
		);

		$statements = new StatementList( $statement1, $statement2, $statement3, $statement4 );

		$this->assertEquals(
			new StatementList( $statement2, $statement3 ),
			$statements->filter( new ReferencedStatementFilter() )
		);
	}

	public function testClear() {
		$statement1 = $this->getStatement( 'P1', null );
		$statement2 = $this->getStatement( 'P2', null );
		$statements = new StatementList( $statement1, $statement2 );

		$statements->clear();

		$this->assertEquals( new StatementList(), $statements );
	}

}
