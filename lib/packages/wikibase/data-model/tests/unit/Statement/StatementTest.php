<?php declare( strict_types=1 );

namespace Wikibase\DataModel\Tests\Statement;

use DataValues\StringValue;
use Generator;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers \Wikibase\DataModel\Statement\Statement
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementTest extends \PHPUnit\Framework\TestCase {

	public function testMinimalConstructor() {
		$mainSnak = new PropertyNoValueSnak( 1 );
		$statement = new Statement( $mainSnak );
		$this->assertTrue( $mainSnak->equals( $statement->getMainSnak() ) );
	}

	/**
	 * @dataProvider provideValidConstructorArguments
	 */
	public function testConstructorWithValidArguments(
		Snak $mainSnak,
		?SnakList $qualifiers,
		?ReferenceList $references,
		$guid
	) {
		$statement = new Statement( $mainSnak, $qualifiers, $references, $guid );
		$this->assertTrue( $statement->getMainSnak()->equals( $mainSnak ) );
		$this->assertTrue( $statement->getQualifiers()->equals( $qualifiers ?: new SnakList() ) );
		$this->assertTrue( $statement->getReferences()->equals( $references ?: new ReferenceList() ) );
		$this->assertSame( $guid, $statement->getGuid() );
	}

	/**
	 * @return array array of arrays:
	 * [ [ Snak $mainSnak, ?SnakList $qualifiers, ?ReferenceList $references, ?string $guid ], ... ]
	 */
	public function provideValidConstructorArguments(): array {
		$snak = new PropertyNoValueSnak( 1 );
		$qualifiers = new SnakList( [ $snak ] );
		$references = new ReferenceList( [ new Reference( [ $snak ] ) ] );

		return [
			'main snak' => [ $snak, null, null, null ],
			'main snak and guid' => [ $snak, null, null, 'guid' ],
			'main snak, qualifiers, references, and guid' => [ $snak, $qualifiers, $references, 'guid' ],
		];
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testSetGuid( Statement $statement ) {
		$statement->setGuid( 'foo-bar-baz' );
		$this->assertSame( 'foo-bar-baz', $statement->getGuid() );
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testGetGuid( Statement $statement ) {
		$guid = $statement->getGuid();
		$this->assertTrue( $guid === null || is_string( $guid ) );
		$this->assertSame( $guid, $statement->getGuid() );

		$statement->setGuid( 'foobar' );
		$this->assertSame( 'foobar', $statement->getGuid() );
	}

	public function testHashStability() {
		$mainSnak = new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) );
		$statement = new Statement( $mainSnak );
		$this->assertSame( '50c73da6759fd31868fb0cc9c218969fa776f62c', $statement->getHash() );
	}

	public function testSetAndGetMainSnak() {
		$mainSnak = new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) );
		$statement = new Statement( $mainSnak );
		$this->assertSame( $mainSnak, $statement->getMainSnak() );
	}

	public function testSetAndGetQualifiers() {
		$qualifiers = new SnakList( [
			new PropertyValueSnak( new NumericPropertyId( 'P42' ), new StringValue( 'a' ) ),
		] );

		$statement = new Statement(
			new PropertyNoValueSnak( new NumericPropertyId( 'P42' ) ),
			$qualifiers
		);

		$this->assertSame( $qualifiers, $statement->getQualifiers() );
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testSerialize( Statement $statement ) {
		$copy = unserialize( serialize( $statement ) );

		$this->assertSame( $statement->getHash(), $copy->getHash(), 'Serialization round-trip should not affect hash' );
	}

	public function testGuidDoesNotAffectHash() {
		$statement0 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement0->setGuid( 'statement0' );

		$statement1 = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement1->setGuid( 'statement1' );

		$this->assertSame( $statement0->getHash(), $statement1->getHash() );
	}

	public function provideStatement(): Generator {
		$propertyId = new NumericPropertyId( 'P42' );
		$baseStatement = new Statement( new PropertyNoValueSnak( $propertyId ) );

		yield 'Statement with PropertyNoValueSnak' => [ $baseStatement ];

		$statement = clone $baseStatement;
		$statement->setRank( Statement::RANK_PREFERRED );
		yield 'Statement with PropertyNoValueSnak and preferred rank' => [ $statement ];

		$statement = clone $statement;
		$statement->setQualifiers(
			new SnakList( [
				new PropertyValueSnak(
					new NumericPropertyId( 'P1' ),
					new StringValue( 'Qualifier Snak StringValue' )
				),
			] )
		);
		yield 'Statement with PropertyNoValueSnak, preferred rank, and Qualifier' => [ $statement ];

		$statement = clone $baseStatement;
		$statement->setReferences(
			new ReferenceList( [
				new Reference( [
					new PropertyValueSnak(
						new NumericPropertyId( 'P2' ),
						new StringValue( 'Reference Snak StringValue' )
					),
				] ),
			] )
		);
		yield 'Statement with PropertyNoValueSnak and Reference' => [ $statement ];
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testGetReferences( Statement $statement ) {
		$this->assertInstanceOf( ReferenceList::class, $statement->getReferences() );
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testSetReferences( Statement $statement ) {
		$references = new ReferenceList( [
			new Reference( [
				new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'a' ) ),
			] ),
		] );

		$statement->setReferences( $references );

		$this->assertSame( $references, $statement->getReferences() );
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testAddNewReferenceWithVariableArgumentsSyntax( Statement $statement ) {
		$snak1 = new PropertyNoValueSnak( 256 );
		$snak2 = new PropertySomeValueSnak( 42 );
		$statement->addNewReference( $snak1, $snak2 );

		$expectedSnaks = [ $snak1, $snak2 ];
		$this->assertTrue( $statement->getReferences()->hasReference( new Reference( $expectedSnaks ) ) );
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testGetRank( Statement $statement ) {
		$rank = $statement->getRank();
		$this->assertIsInt( $rank );

		$ranks = [ Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED ];
		$this->assertContains( $rank, $ranks );
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testSetRank( Statement $statement ) {
		$statement->setRank( Statement::RANK_DEPRECATED );
		$this->assertSame( Statement::RANK_DEPRECATED, $statement->getRank() );
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testSetInvalidRank( Statement $statement ) {
		$this->expectException( InvalidArgumentException::class );
		$statement->setRank( 9001 );
	}

	/**
	 * @dataProvider provideStatement
	 */
	public function testGetPropertyId( Statement $statement ) {
		$this->assertSame(
			$statement->getMainSnak()->getPropertyId(),
			$statement->getPropertyId()
		);
	}

	/**
	 * @dataProvider provideStatement
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
			new SnakList( [
				new PropertyNoValueSnak( 1337 ),
			] ),
			new ReferenceList( [
				new Reference( [ new PropertyNoValueSnak( 1337 ) ] ),
			] )
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

	public function testStatementWithDifferentQualifiers_equalsReturnsFalse() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( [
				new PropertyNoValueSnak( 1337 ),
			] )
		);

		$differentStatement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( [
				new PropertyNoValueSnak( 32202 ),
			] )
		);

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	public function testGivenStatementWithDifferentGuids_equalsReturnsFalse() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );

		$differentStatement = new Statement( new PropertyNoValueSnak( 42 ) );
		$differentStatement->setGuid( 'kittens' );

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	public function testStatementWithDifferentReferences_equalsReturnsFalse() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList(),
			new ReferenceList( [
				new Reference( [ new PropertyNoValueSnak( 1337 ) ] ),
			] )
		);

		$differentStatement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList(),
			new ReferenceList( [
				new Reference( [ new PropertyNoValueSnak( 32202 ) ] ),
			] )
		);

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	public function testEquals() {
		$statement = $this->newStatement();
		$target = $this->newStatement();

		$this->assertTrue( $statement->equals( $target ) );
	}

	/**
	 * @dataProvider provideNonEqualStatements
	 */
	public function testNotEquals( Statement $statement, Statement $target ) {
		$this->assertFalse( $statement->equals( $target ) );
	}

	/**
	 * @return array array of arrays: [ [ Statement $statement, Statement $target ], ... ]
	 */
	public function provideNonEqualStatements(): array {
		$statement = $this->newStatement();

		$statementWithoutQualifiers = $this->newStatement();
		$statementWithoutQualifiers->setQualifiers( new SnakList() );

		$statementWithoutReferences = $this->newStatement();
		$statementWithoutReferences->setReferences( new ReferenceList() );

		$statementWithPreferredRank = $this->newStatement();
		$statementWithPreferredRank->setRank( Statement::RANK_PREFERRED );

		$statementMainSnakNotEqual = $this->newStatement();
		$statementMainSnakNotEqual->setMainSnak( new PropertyNoValueSnak( 9000 ) );

		return [
			'qualifiers not equal' => [ $statement, $statementWithoutQualifiers ],
			'references not equal' => [ $statement, $statementWithoutReferences ],
			'rank not equal' => [ $statement, $statementWithPreferredRank ],
			'main snak not equal' => [ $statement, $statementMainSnakNotEqual ],
		];
	}

	private function newStatement(): Statement {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( [ new PropertyNoValueSnak( 23 ) ] ),
			new ReferenceList( [
				new Reference( [ new PropertyNoValueSnak( 1337 ) ] ),
			] )
		);
		$statement->setRank( Statement::RANK_NORMAL );

		return $statement;
	}

	public function testHashesOfDifferentStatementsAreNotTheSame() {
		$this->assertNotSame(
			( new Statement( new PropertyNoValueSnak( 1 ) ) )->getHash(),
			( new Statement( new PropertyNoValueSnak( 2 ) ) )->getHash()
		);
	}

	public function testHashesOfEqualStatementsAreTheSame() {
		$this->assertSame(
			( new Statement( new PropertyNoValueSnak( 1 ) ) )->getHash(),
			( new Statement( new PropertyNoValueSnak( 1 ) ) )->getHash()
		);
	}

}
