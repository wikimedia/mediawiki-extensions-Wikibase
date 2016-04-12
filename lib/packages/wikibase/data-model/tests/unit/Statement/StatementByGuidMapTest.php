<?php

namespace Wikibase\DataModel\Tests\Statement;

use ArrayObject;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementByGuidMap;

/**
 * @covers Wikibase\DataModel\Statement\StatementByGuidMap
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class StatementByGuidMapTest extends \PHPUnit_Framework_TestCase {

	public function testGivenNotPresentGuid_hasClaimWithGuidReturnsFalse() {
		$statements = new StatementByGuidMap();

		$this->assertFalse( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	public function testGivenPresentGuid_hasStatementWithGuidReturnsTrue() {

		$statements = new StatementByGuidMap( array(
			$this->newStatement( 1, 'some guid' )
		) );

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
	public function testGivenNonStringGuid_hasClaimWithGuidThrowsException( $nonString ) {
		$statements = new StatementByGuidMap();

		$this->setExpectedException( 'InvalidArgumentException' );
		$statements->hasStatementWithGuid( $nonString );
	}

	public function nonStringProvider() {
		return array(
			array( null ),
			array( 42 ),
			array( 4.2 ),
			array( array() ),
			array( (object)array() ),
		);
	}

	public function testGivenGuidOfPresentStatement_getStatementByGuidReturnsStatement() {
		$statement = $this->newStatement( 1, 'some guid' );

		$statements = new StatementByGuidMap( array( $statement ) );

		$this->assertEquals( $statement, $statements->getStatementByGuid( 'some guid' ) );
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

		$this->setExpectedException( 'InvalidArgumentException' );
		$statements->getStatementByGuid( $nonString );
	}

	public function testGivenGuidOfPresentStatement_removeStatementWithGuidRemovesTheStatement() {
		$statement = $this->newStatement( 1, 'some guid' );
		$statements = new StatementByGuidMap( array( $statement ) );

		$statements->removeStatementWithGuid( 'some guid' );

		$this->assertFalse( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	public function testGivenGuidOfNonPresentStatement_removeStatementWithGuidDoesNoOp() {
		$statement = $this->newStatement( 1, 'some guid' );
		$statements = new StatementByGuidMap( array( $statement ) );

		$statements->removeStatementWithGuid( '-- different guid --' );

		$this->assertTrue( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	/**
	 * @dataProvider nonStringProvider
	 */
	public function testGivenNonStringGuid_removeStatementWithGuidThrowsException( $nonString ) {
		$statements = new StatementByGuidMap();

		$this->setExpectedException( 'InvalidArgumentException' );
		$statements->removeStatementWithGuid( $nonString );
	}

	public function testGivenStatementWithNoGuid_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );

		new StatementByGuidMap( array(
			$this->newStatement( 1, null )
		) );
	}

	public function testCanConstructWithStatementTraversable() {
		$traversable = new ArrayObject( array(
			$this->newStatement( 1, 'some guid' )
		) );

		$statementMap = new StatementByGuidMap( $traversable );

		$this->assertTrue( $statementMap->hasStatementWithGuid( 'some guid' ) );
	}

	public function testWhenMapIsEmpty_countReturnsZero() {
		$statements = new StatementByGuidMap();

		$this->assertSame( 0, $statements->count() );
	}

	public function testMapCanBePassedToCount() {
		$statements = new StatementByGuidMap( array(
			$this->newStatement( 1, 'some guid' ),
			$this->newStatement( 2, 'other guid' )
		) );

		$this->assertSame( 2, count( $statements ) );
	}

	public function testMapCanBeIteratedOver() {
		$statement1 = $this->newStatement( 1, 'some guid' );
		$statement2 = $this->newStatement( 2, 'other guid' );

		$statementMap = new StatementByGuidMap( array( $statement1, $statement2 ) );

		$iteratedStatements = array();

		foreach ( $statementMap as $guid => $statement ) {
			$iteratedStatements[$guid] = $statement;
		}

		$expectedStatements = array(
			'some guid' => $statement1,
			'other guid' => $statement2
		);

		$this->assertEquals( $expectedStatements, $iteratedStatements );
	}

	public function testGivenNotPresentStatement_addStatementAddsIt() {
		$statements = new StatementByGuidMap();

		$statements->addStatement( $this->newStatement( 1, 'some guid' ) );

		$this->assertTrue( $statements->hasStatementWithGuid( 'some guid' ) );
	}

	public function testGivenStatementWithPresentGuid_addStatementReplacesThePresentStatement() {
		$statement1 = $this->newStatement( 1, 'some guid' );
		$statement2 = $this->newStatement( 2, 'some guid' );

		$statements = new StatementByGuidMap( array( $statement1 ) );

		$statements->addStatement( $statement2 );

		$this->assertEquals( $statement2, $statements->getStatementByGuid( 'some guid' ) );
	}

	public function testToArray() {
		$statement1 = $this->newStatement( 1, 'some guid' );
		$statement2 = $this->newStatement( 2, 'other guid' );

		$statementMap = new StatementByGuidMap( array( $statement1, $statement2 ) );

		$expectedStatements = array(
			'some guid' => $statement1,
			'other guid' => $statement2
		);

		$this->assertEquals( $expectedStatements, $statementMap->toArray() );
	}

}
