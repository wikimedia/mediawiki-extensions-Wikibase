<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Statement\Statement
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseStatement
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorTakesGuidFromClaim() {
		$claim = new Claim( new PropertyNoValueSnak( new PropertyId( 'P42' ) ) );
		$claim->setGuid( 'meh' );
		$statement = new Statement( $claim );
		$this->assertEquals( 'meh', $statement->getGuid() );
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
		$snak = new PropertyNoValueSnak( new PropertyId( 'P42' ) );
		$statement = new Statement( new Claim( $snak ) );
		$this->assertSame( $snak, $statement->getMainSnak() );
	}

	public function testSetAndGetQualifiers() {
		$qualifiers = new SnakList( array(
			new PropertyValueSnak( new PropertyId( 'P42' ), new StringValue( 'a' ) )
		) );

		$statement = new Statement( new Claim(
			new PropertyNoValueSnak( new PropertyId( 'P42' ) ),
			$qualifiers
		) );

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
		$statement0 = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement0->setGuid( 'statement0' );

		$statement1 = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement1->setGuid( 'statement1' );

		$this->assertEquals( $statement0->getHash(), $statement1->getHash() );
	}

	public function testSetInvalidGuidCausesException() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );

		$this->setExpectedException( 'InvalidArgumentException' );
		$statement->setGuid( 42 );
	}

	public function instanceProvider() {
		$instances = array();

		$id42 = new PropertyId( 'P42' );

		$baseInstance = new Statement( new Claim( new PropertyNoValueSnak( $id42 ) ) );

		$instances[] = $baseInstance;

		$instance = clone $baseInstance;
		$instance->setRank( Statement::RANK_PREFERRED );

		$instances[] = $instance;

		$newInstance = clone $instance;

		$instances[] = $newInstance;

		$instance = clone $baseInstance;

		$instance->setReferences( new ReferenceList( array(
			new Reference( new SnakList(
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
			new Reference( new SnakList(
				new PropertyValueSnak(
					new PropertyId( 'P1' ),
					new StringValue( 'a' )
				)
			) ) )
		);


		$statement->setReferences( $references );

		$this->assertEquals( $references, $statement->getReferences() );
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
	public function testSetRankToTruth( Statement $statement ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		$statement->setRank( Statement::RANK_TRUTH );
	}

	public function testStatementRankCompatibility() {
		$this->assertEquals( Claim::RANK_DEPRECATED, Statement::RANK_DEPRECATED );
		$this->assertEquals( Claim::RANK_PREFERRED, Statement::RANK_PREFERRED );
		$this->assertEquals( Claim::RANK_NORMAL, Statement::RANK_NORMAL );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testIsClaim( Statement $statement ) {
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claim', $statement );
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

		$this->assertGreaterThanOrEqual( $c, count( $snaks ), "At least one snak per Qualifier and Reference" );
	}

	public function testGivenNonStatement_equalsReturnsFalse() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );

		$this->assertFalse( $statement->equals( null ) );
		$this->assertFalse( $statement->equals( 42 ) );
		$this->assertFalse( $statement->equals( new \stdClass() ) );
	}

	public function testGivenSameStatement_equalsReturnsTrue() {
		$statement = new Statement(
			new Claim(
				new PropertyNoValueSnak( 42 ),
				new SnakList( array(
					new PropertyNoValueSnak( 1337 ),
				) )
			),
			new ReferenceList( array(
				new PropertyNoValueSnak( 1337 ),
			) )
		);

		$statement->setGuid( 'kittens' );

		$this->assertTrue( $statement->equals( $statement ) );
		$this->assertTrue( $statement->equals( clone $statement ) );
	}

	public function testGivenStatementWithDifferentProperty_equalsReturnsFalse() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$this->assertFalse( $statement->equals( new Statement( new Claim( new PropertyNoValueSnak( 43 ) ) ) ) );
	}

	public function testGivenStatementWithDifferentSnakType_equalsReturnsFalse() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$this->assertFalse( $statement->equals( new Statement( new Claim( new PropertySomeValueSnak( 42 ) ) ) ) );
	}

	public function testStatementClaimWithDifferentQualifiers_equalsReturnsFalse() {
		$statement = new Statement( new Claim(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 1337 ),
			) )
		) );

		$differentStatement = new Statement( new Claim(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 32202 ),
			) )
		) );

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	public function testGivenStatementWithDifferentGuids_equalsReturnsFalse() {
		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );

		$differentStatement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$differentStatement->setGuid( 'kittens' );

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	public function testStatementClaimWithDifferentReferences_equalsReturnsFalse() {
		$statement = new Statement(
			new Claim(
				new PropertyNoValueSnak( 42 ),
				new SnakList( array() )
			),
			new ReferenceList( array(
				new PropertyNoValueSnak( 1337 ),
			) )
		);

		$differentStatement = new Statement(
			new Claim(
				new PropertyNoValueSnak( 42 ),
				new SnakList( array() )
			),
			new ReferenceList( array(
				new PropertyNoValueSnak( 32202 ),
			) )
		);

		$this->assertFalse( $statement->equals( $differentStatement ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetClaim( Statement $statement ) {
		$mainSnak = new PropertyNoValueSnak( new PropertyId( 'P42' ) );
		$qualifiers = new SnakList( array( new PropertyNoValueSnak( 23 ) ) );
		$claim = new Claim( $mainSnak, $qualifiers );

		$statement->setClaim( $claim );

		$this->assertEquals( $mainSnak, $statement->getClaim()->getMainSnak() );
		$this->assertEquals( $mainSnak, $statement->getMainSnak() );
		$this->assertEquals( $qualifiers, $statement->getClaim()->getQualifiers() );
		$this->assertEquals( $qualifiers, $statement->getQualifiers() );
	}

	public function testGetClaim() {
		$qualifiers = new SnakList( array( new PropertyNoValueSnak( 23 ) ) );

		$statement = new Statement(
			new Claim( new PropertyNoValueSnak( 42 ), $qualifiers ),
			new ReferenceList( array(
				new PropertyNoValueSnak( 1337 ),
			) )
		);
		$statement->setGuid( '~=[,,_,,]:3' );

		$claim = $statement->getClaim();
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claim', $claim );

		$this->assertEquals( '~=[,,_,,]:3', $claim->getGuid() );
		$this->assertEquals( new PropertyNoValueSnak( 42 ), $claim->getMainSnak() );
		$this->assertSame( $qualifiers, $claim->getQualifiers() );
	}

}
