<?php

namespace Wikibase\DataModel\Tests\Statement;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Statement\Statement
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseStatement
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementTest extends \PHPUnit_Framework_TestCase {

	public function testMinimalConstructor() {
		$mainSnak = new PropertyNoValueSnak( 1 );
		$statement = new Statement( $mainSnak );
		$this->assertTrue( $mainSnak->equals( $statement->getMainSnak() ) );
	}

	/**
	 * @dataProvider validConstructorArgumentsProvider
	 */
	public function testConstructorWithValidArguments(
		Snak $mainSnak,
		SnakList $qualifiers = null,
		ReferenceList $references = null,
		$guid
	) {
		$statement = new Statement( $mainSnak, $qualifiers, $references, $guid );
		$this->assertTrue( $statement->getMainSnak()->equals( $mainSnak ) );
		$this->assertTrue( $statement->getQualifiers()->equals( $qualifiers ?: new SnakList() ) );
		$this->assertTrue( $statement->getReferences()->equals( $references ?: new ReferenceList() ) );
		$this->assertSame( $guid, $statement->getGuid() );
	}

	public function validConstructorArgumentsProvider() {
		$snak = new PropertyNoValueSnak( 1 );
		$qualifiers = new SnakList( array( $snak ) );
		$references = new ReferenceList( array( new Reference( array( $snak ) ) ) );

		return array(
			array( $snak, null, null, null ),
			array( $snak, null, null, 'guid' ),
			array( $snak, $qualifiers, $references, 'guid' ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetGuid( Statement $statement ) {
		$statement->setGuid( 'foo-bar-baz' );
		$this->assertEquals( 'foo-bar-baz', $statement->getGuid() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetGuid( Statement $statement ) {
		$guid = $statement->getGuid();
		$this->assertTrue( $guid === null || is_string( $guid ) );
		$this->assertEquals( $guid, $statement->getGuid() );

		$statement->setGuid( 'foobar' );
		$this->assertEquals( 'foobar', $statement->getGuid() );
	}

	public function testSetAndGetMainSnak() {
		$mainSnak = new PropertyNoValueSnak( new PropertyId( 'P42' ) );
		$statement = new Statement( $mainSnak );
		$this->assertSame( $mainSnak, $statement->getMainSnak() );
	}

	public function testSetAndGetQualifiers() {
		$qualifiers = new SnakList( array(
			new PropertyValueSnak( new PropertyId( 'P42' ), new StringValue( 'a' ) )
		) );

		$statement = new Statement(
			new PropertyNoValueSnak( new PropertyId( 'P42' ) ),
			$qualifiers
		);

		$this->assertSame( $qualifiers, $statement->getQualifiers() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSerialize( Statement $statement ) {
		$copy = unserialize( serialize( $statement ) );

		$this->assertEquals( $statement->getHash(), $copy->getHash(), 'Serialization roundtrip should not affect hash' );
	}

	public function testGuidDoesNotAffectHash() {
		$statement0 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement0->setGuid( 'statement0' );

		$statement1 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement1->setGuid( 'statement1' );

		$this->assertEquals( $statement0->getHash(), $statement1->getHash() );
	}

	/**
	 * @dataProvider invalidGuidProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidGuid_constructorThrowsException( $guid ) {
		new Statement( new PropertyNoValueSnak( 1 ), null, null, $guid );
	}

	/**
	 * @dataProvider invalidGuidProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidGuid_setGuidThrowsException( $guid ) {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( $guid );
	}

	public function invalidGuidProvider() {
		$snak = new PropertyNoValueSnak( 1 );

		return array(
			array( false ),
			array( 1 ),
			array( $snak ),
			array( new Statement( $snak ) ),
		);
	}

	public function instanceProvider() {
		$instances = array();

		$propertyId = new PropertyId( 'P42' );
		$baseInstance = new Statement( new PropertyNoValueSnak( $propertyId ) );

		$instances[] = $baseInstance;

		$instance = clone $baseInstance;
		$instance->setRank( Statement::RANK_PREFERRED );

		$instances[] = $instance;

		$newInstance = clone $instance;

		$instances[] = $newInstance;

		$instance = clone $baseInstance;

		$instance->setReferences( new ReferenceList( array(
			new Reference( array(
				new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) )
			) )
		) ) );

		$instances[] = $instance;

		$argLists = array();

		foreach ( $instances as $instance ) {
			$argLists[] = array( $instance );
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetReferences( Statement $statement ) {
		$this->assertInstanceOf( 'Wikibase\DataModel\ReferenceList', $statement->getReferences() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetReferences( Statement $statement ) {
		$references = new ReferenceList( array(
			new Reference( array(
				new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) ),
			) )
		) );

		$statement->setReferences( $references );

		$this->assertEquals( $references, $statement->getReferences() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testAddNewReferenceWithVariableArgumentsSyntax( Statement $statement ) {
		$snak1 = new PropertyNoValueSnak( 256 );
		$snak2 = new PropertySomeValueSnak( 42 );
		$statement->addNewReference( $snak1, $snak2 );

		$expectedSnaks = array( $snak1, $snak2 );
		$this->assertTrue( $statement->getReferences()->hasReference( new Reference( $expectedSnaks ) ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testAddNewReferenceWithAnArrayOfSnaks( Statement $statement ) {
		$snaks = array(
			new PropertyNoValueSnak( 256 ),
			new PropertySomeValueSnak( 42 ),
		);
		$statement->addNewReference( $snaks );

		$this->assertTrue( $statement->getReferences()->hasReference( new Reference( $snaks ) ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetRank( Statement $statement ) {
		$rank = $statement->getRank();
		$this->assertInternalType( 'integer', $rank );

		$ranks = array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED );
		$this->assertTrue( in_array( $rank, $ranks ), true );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetRank( Statement $statement ) {
		$statement->setRank( Statement::RANK_DEPRECATED );
		$this->assertEquals( Statement::RANK_DEPRECATED, $statement->getRank() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetInvalidRank( Statement $statement ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		$statement->setRank( 9001 );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetPropertyId( Statement $statement ) {
		$this->assertEquals(
			$statement->getMainSnak()->getPropertyId(),
			$statement->getPropertyId()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetAllSnaks( Statement $statement ) {
		$snaks = $statement->getAllSnaks();

		$c = count( $statement->getQualifiers() ) + 1;

		/* @var Reference $reference */
		foreach ( $statement->getReferences() as $reference ) {
			$c += count( $reference->getSnaks() );
		}

		$this->assertGreaterThanOrEqual( $c, count( $snaks ), 'At least one snak per Qualifier and Reference' );
	}

	public function testGivenNonStatement_equalsReturnsFalse() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );

		$this->assertFalse( $statement->equals( null ) );
		$this->assertFalse( $statement->equals( 42 ) );
		$this->assertFalse( $statement->equals( new \stdClass() ) );
	}

	public function testGivenSameStatement_equalsReturnsTrue() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 1337 ),
			) ),
			new ReferenceList( array(
				new Reference( array( new PropertyNoValueSnak( 1337 ) ) ),
			) )
		);

		$statement->setGuid( 'kittens' );

		$this->assertTrue( $statement->equals( $statement ) );
		$this->assertTrue( $statement->equals( clone $statement ) );
	}

	public function testGivenStatementWithDifferentProperty_equalsReturnsFalse() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$this->assertFalse( $statement->equals( new Statement( new PropertyNoValueSnak( 43 ) ) ) );
	}

	public function testGivenStatementWithDifferentSnakType_equalsReturnsFalse() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$this->assertFalse( $statement->equals( new Statement( new PropertySomeValueSnak( 42 ) ) ) );
	}

	public function testStatementClaimWithDifferentQualifiers_equalsReturnsFalse() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 1337 ),
			) )
		);

		$differentStatement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 32202 ),
			) )
		);

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	public function testGivenStatementWithDifferentGuids_equalsReturnsFalse() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );

		$differentStatement = new Statement( new PropertyNoValueSnak( 42 ) );
		$differentStatement->setGuid( 'kittens' );

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	public function testStatementClaimWithDifferentReferences_equalsReturnsFalse() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList(),
			new ReferenceList( array(
				new Reference( array( new PropertyNoValueSnak( 1337 ) ) ),
			) )
		);

		$differentStatement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList(),
			new ReferenceList( array(
				new Reference( array( new PropertyNoValueSnak( 32202 ) ) ),
			) )
		);

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	public function testEquals() {
		$statement = $this->newStatement();
		$target = $this->newStatement();

		$this->assertTrue( $statement->equals( $target ) );
	}

	/**
	 * @dataProvider notEqualsProvider
	 */
	public function testNotEquals( Statement $statement, Statement $target, $message ) {
		$this->assertFalse( $statement->equals( $target ), $message );
	}

	public function notEqualsProvider() {
		$statement = $this->newStatement();

		$statementWithoutQualifiers = $this->newStatement();
		$statementWithoutQualifiers->setQualifiers( new SnakList() );

		$statementWithoutReferences = $this->newStatement();
		$statementWithoutReferences->setReferences( new ReferenceList() );

		$statementWithPreferredRank = $this->newStatement();
		$statementWithPreferredRank->setRank( Statement::RANK_PREFERRED );

		$statementMainSnakNotEqual = $this->newStatement();
		$statementMainSnakNotEqual->setMainSnak( new PropertyNoValueSnak( 9000 ) );

		return array(
			array( $statement, $statementWithoutQualifiers, 'qualifiers not equal' ),
			array( $statement, $statementWithoutReferences, 'references not equal' ),
			array( $statement, $statementWithPreferredRank, 'rank not equal' ),
			array( $statement, $statementMainSnakNotEqual, 'main snak not equal' )
		);
	}

	private function newStatement() {
		$qualifiers = new SnakList( array( new PropertyNoValueSnak( 23 ) ) );

		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			$qualifiers,
			new ReferenceList( array(
				new Reference( array( new PropertyNoValueSnak( 1337 ) ) ),
			) )
		);

		$statement->setRank( Statement::RANK_NORMAL );

		return $statement;
	}

}
