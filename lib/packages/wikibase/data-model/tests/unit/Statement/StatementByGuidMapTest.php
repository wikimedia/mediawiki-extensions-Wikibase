<?php

namespace Wikibase\DataModel\Tests\Statement;

use ArrayObject;
use InvalidArgumentException;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementByGuidMap;

/**
 * @covers \Wikibase\DataModel\Statement\StatementByGuidMap
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class StatementByGuidMapTest extends \PHPUnit\Framework\TestCase {

	public function testGivenNotPresentGuid_hasStatementWithGuidReturnsFalse() {
		$statements = new StatementByGuidMap();

		$this->assertFalse( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	public function testGivenPresentGuid_hasStatementWithGuidReturnsTrue() {

		$statements = new StatementByGuidMap( [
			$this->newStatement( 1, 'some guid' ),
		] );

		$this->assertTrue( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	private function newStatement( $propertyId, $guid ) {
		$statement = new Statement( new PropertyNoValueSnak( $propertyId ) );
		$statement->setGuid( $guid );
		return $statement;
	}

	/**
	 * @dataProvider nonStringProvider
	 */
	public function testGivenNonStringGuid_hasStatementWithGuidThrowsException( $nonString ) {
		$statements = new StatementByGuidMap();

		$this->expectException( InvalidArgumentException::class );
		$statements->hasStatementWithGuid( $nonString );
	}

	public function nonStringProvider() {
		return [
			[ null ],
			[ 42 ],
			[ 4.2 ],
			[ [] ],
			[ (object)[] ],
		];
	}

	public function testGivenGuidOfPresentStatement_getStatementByGuidReturnsStatement() {
		$statement = $this->newStatement( 1, 'some guid' );

		$statements = new StatementByGuidMap( [ $statement ] );

		$this->assertSame( $statement, $statements->getStatementByGuid( 'some guid' ) );
	}

	public function testGivenGuidOfNotPresentStatement_getStatementByGuidReturnsNull() {
		$statements = new StatementByGuidMap();

		$this->assertNull( $statements->getStatementByGuid( 'some guid' ) );
	}

	/**
	 * @dataProvider nonStringProvider
	 */
	public function testGivenNonStringGuid_getStatementByGuidThrowsException( $nonString ) {
		$statements = new StatementByGuidMap();

		$this->expectException( InvalidArgumentException::class );
		$statements->getStatementByGuid( $nonString );
	}

	public function testGivenGuidOfPresentStatement_removeStatementWithGuidRemovesTheStatement() {
		$statement = $this->newStatement( 1, 'some guid' );
		$statements = new StatementByGuidMap( [ $statement ] );

		$statements->removeStatementWithGuid( 'some guid' );

		$this->assertFalse( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	public function testGivenGuidOfNonPresentStatement_removeStatementWithGuidDoesNoOp() {
		$statement = $this->newStatement( 1, 'some guid' );
		$statements = new StatementByGuidMap( [ $statement ] );

		$statements->removeStatementWithGuid( '-- different guid --' );

		$this->assertTrue( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	/**
	 * @dataProvider nonStringProvider
	 */
	public function testGivenNonStringGuid_removeStatementWithGuidThrowsException( $nonString ) {
		$statements = new StatementByGuidMap();

		$this->expectException( InvalidArgumentException::class );
		$statements->removeStatementWithGuid( $nonString );
	}

	public function testGivenStatementWithNoGuid_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );

		new StatementByGuidMap( [
			$this->newStatement( 1, null ),
		] );
	}

	public function testCanConstructWithStatementTraversable() {
		$traversable = new ArrayObject( [
			$this->newStatement( 1, 'some guid' ),
		] );

		$statementMap = new StatementByGuidMap( $traversable );

		$this->assertTrue( $statementMap->hasStatementWithGuid( 'some guid' ) );
	}

	public function testWhenMapIsEmpty_countReturnsZero() {
		$statements = new StatementByGuidMap();

		$this->assertSame( 0, $statements->count() );
	}

	public function testMapCanBePassedToCount() {
		$statements = new StatementByGuidMap( [
			$this->newStatement( 1, 'some guid' ),
			$this->newStatement( 2, 'other guid' ),
		] );

		$this->assertCount( 2, $statements );
	}

	public function testMapCanBeIteratedOver() {
		$statement1 = $this->newStatement( 1, 'some guid' );
		$statement2 = $this->newStatement( 2, 'other guid' );

		$statementMap = new StatementByGuidMap( [ $statement1, $statement2 ] );

		$iteratedStatements = [];

		foreach ( $statementMap as $guid => $statement ) {
			$iteratedStatements[$guid] = $statement;
		}

		$expectedStatements = [
			'some guid' => $statement1,
			'other guid' => $statement2,
		];

		$this->assertSame( $expectedStatements, $iteratedStatements );
	}

	public function testGivenNotPresentStatement_addStatementAddsIt() {
		$statements = new StatementByGuidMap();

		$statements->addStatement( $this->newStatement( 1, 'some guid' ) );

		$this->assertTrue( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	public function testGivenStatementWithPresentGuid_addStatementReplacesThePresentStatement() {
		$statement1 = $this->newStatement( 1, 'some guid' );
		$statement2 = $this->newStatement( 2, 'some guid' );

		$statements = new StatementByGuidMap( [ $statement1 ] );

		$statements->addStatement( $statement2 );

		$this->assertSame( $statement2, $statements->getStatementByGuid( 'some guid' ) );
	}

	public function testToArray() {
		$statement1 = $this->newStatement( 1, 'some guid' );
		$statement2 = $this->newStatement( 2, 'other guid' );

		$statementMap = new StatementByGuidMap( [ $statement1, $statement2 ] );

		$expectedStatements = [
			'some guid' => $statement1,
			'other guid' => $statement2,
		];

		$this->assertSame( $expectedStatements, $statementMap->toArray() );
	}

}
