<?php

namespace Wikibase\DataModel\Tests\Claim;

use InvalidArgumentException;
use ReflectionClass;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Claim\Claims
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ClaimsTest extends \PHPUnit_Framework_TestCase {

	protected $guidCounter = 0;

	protected function makeClaim( Snak $mainSnak, $guid = null ) {
		if ( $guid === null ) {
			$this->guidCounter++;
			$guid = 'TEST$statement-' . $this->guidCounter;
		}

		$statement = new Statement( $mainSnak );
		$statement->setGuid( $guid );

		return $statement;
	}

	protected function makeStatement( Snak $mainSnak, $guid = null ) {
		if ( $guid === null ) {
			$this->guidCounter++;
			$guid = 'TEST$statement-' . $this->guidCounter;
		}

		$statement = new Statement( $mainSnak );
		$statement->setGuid( $guid );

		return $statement;
	}

	public function testArrayObjectNotConstructedFromObject() {
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( 1 ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( 2 ) );

		$statementList = new StatementList();
		$statementList->addStatement( $statement1 );

		$statements = new Claims( $statementList );
		// According to the documentation append() "cannot be called when the ArrayObject was
		// constructed from an object." This test makes sure it was not constructed from an object.
		$statements->append( $statement2 );

		$this->assertSame( 2, $statements->count() );
	}

	/**
	 * @dataProvider constructorErrorProvider
	 */
	public function testConstructorError() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$class = new ReflectionClass( 'Wikibase\DataModel\Claim\Claims' );
		$class->newInstanceArgs( func_get_args() );
	}

	public function constructorErrorProvider() {
		return array(
			array( 17 ),
			array( array( 'foo' ) ),
		);
	}

	public function testHasClaim() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$this->assertFalse( $statements->hasClaim( $statement1 ) );
		$this->assertFalse( $statements->hasClaim( $statement2 ) );

		$statements->addClaim( $statement1 );
		$this->assertTrue( $statements->hasClaim( $statement1 ) );
		$this->assertFalse( $statements->hasClaim( $statement2 ) );

		$statements->addClaim( $statement2 );
		$this->assertTrue( $statements->hasClaim( $statement1 ) );
		$this->assertTrue( $statements->hasClaim( $statement2 ) );

		// no guid
		$statement0 = new Statement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$this->assertFalse( $statements->hasClaim( $statement0 ) );
	}

	public function testHasClaimWithGuid() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$this->assertFalse( $statements->hasClaimWithGuid( $statement1->getGuid() ) );
		$this->assertFalse( $statements->hasClaimWithGuid( $statement2->getGuid() ) );

		$statements->addClaim( $statement1 );
		$this->assertTrue( $statements->hasClaimWithGuid( $statement1->getGuid() ) );
		$this->assertFalse( $statements->hasClaimWithGuid( $statement2->getGuid() ) );

		$statements->addClaim( $statement2 );
		$this->assertTrue( $statements->hasClaimWithGuid( $statement1->getGuid() ) );
		$this->assertTrue( $statements->hasClaimWithGuid( $statement2->getGuid() ) );
	}

	public function testRemoveClaim() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements->addClaim( $statement1 );
		$statements->addClaim( $statement2 );
		$this->assertSame( 2, $statements->count() );

		$statements->removeClaim( $statement1 );
		$this->assertFalse( $statements->hasClaim( $statement1 ) );
		$this->assertNull( $statements->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertSame( 1, $statements->count() );

		$statements->removeClaim( $statement2 );
		$this->assertFalse( $statements->hasClaim( $statement2 ) );
		$this->assertNull( $statements->getClaimWithGuid( $statement2->getGuid() ) );
		$this->assertSame( 0, $statements->count() );

		// no guid
		$statement0 = new Statement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statements->removeClaim( $statement0 );
	}

	public function testRemoveClaimWithGuid() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements->addClaim( $statement1 );
		$statements->addClaim( $statement2 );
		$this->assertSame( 2, $statements->count() );

		$statements->removeClaimWithGuid( $statement1->getGuid() );
		$this->assertFalse( $statements->hasClaim( $statement1 ) );
		$this->assertNull( $statements->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertSame( 1, $statements->count() );

		$statements->removeClaimWithGuid( $statement2->getGuid() );
		$this->assertFalse( $statements->hasClaim( $statement2 ) );
		$this->assertNull( $statements->getClaimWithGuid( $statement2->getGuid() ) );
		$this->assertSame( 0, $statements->count() );
	}

	public function testOffsetUnset() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements->addClaim( $statement1 );
		$statements->addClaim( $statement2 );
		$this->assertSame( 2, $statements->count() );

		$statements->offsetUnset( $statement1->getGuid() );
		$this->assertFalse( $statements->hasClaim( $statement1 ) );
		$this->assertNull( $statements->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertSame( 1, $statements->count() );

		$statements->offsetUnset( $statement2->getGuid() );
		$this->assertFalse( $statements->hasClaim( $statement2 ) );
		$this->assertNull( $statements->getClaimWithGuid( $statement2->getGuid() ) );
		$this->assertSame( 0, $statements->count() );
	}

	public function testGetClaimWithGuid() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements->addClaim( $statement1 );
		$this->assertSame( $statement1, $statements->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertNull( $statements->getClaimWithGuid( $statement2->getGuid() ) );

		$statements->addClaim( $statement2 );
		$this->assertSame( $statement1, $statements->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertSame( $statement2, $statements->getClaimWithGuid( $statement2->getGuid() ) );
	}

	public function testOffsetGet() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements->addClaim( $statement1 );
		$this->assertSame( $statement1, $statements->offsetGet( $statement1->getGuid() ) );

		$statements->addClaim( $statement2 );
		$this->assertSame( $statement1, $statements->offsetGet( $statement1->getGuid() ) );
		$this->assertSame( $statement2, $statements->offsetGet( $statement2->getGuid() ) );
	}

	public function testAddClaim() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements->addClaim( $statement1 );
		$statements->addClaim( $statement2 );

		$this->assertSame( 2, $statements->count() );
		$this->assertEquals( $statement1, $statements[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $statements[$statement2->getGuid()] );

		$statements->addClaim( $statement1 );
		$statements->addClaim( $statement2 );

		$this->assertSame( 2, $statements->count() );

		$this->assertNotNull( $statements->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertNotNull( $statements->getClaimWithGuid( $statement2->getGuid() ) );

		// Insert claim at the beginning:
		$statement3 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P17' ) ) );
		$statements->addClaim( $statement3, 0 );
		$this->assertEquals( 0, $statements->indexOf( $statement3 ), 'Inserting statement at the beginning failed' );

		// Insert claim at another index:
		$statement4 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P18' ) ) );
		$statements->addClaim( $statement4, 1 );
		$this->assertEquals( 1, $statements->indexOf( $statement4 ), 'Inserting statement at index 1 failed' );

		// Insert claim with an index out of bounds:
		$statement5 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P19' ) ) );
		$statements->addClaim( $statement5, 99999 );
		$this->assertEquals( 4,
			$statements->indexOf( $statement5 ),
			'Appending statement by specifying an index out of bounds failed'
		);
	}

	public function testIndexOf() {
		$statements = new Claims();
		$statementArray = array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P3' ) ) ),
		);
		$excludedClaim = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P99' ) ) );

		foreach( $statementArray as $statement ) {
			$statements->addClaim( $statement );
		}

		$this->assertFalse( $statements->indexOf( $excludedClaim ) );

		$i = 0;
		foreach( $statementArray as $statement ) {
			$this->assertEquals( $i++, $statements->indexOf( $statement ) );
		}
	}

	public function testAppend() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements->append( $statement1 );
		$statements->append( $statement2 );

		$this->assertSame( 2, $statements->count() );
		$this->assertEquals( $statement1, $statements[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $statements[$statement2->getGuid()] );

		$statements->append( $statement1 );
		$statements->append( $statement2 );

		$this->assertSame( 2, $statements->count() );
	}

	public function testAppendOperator() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements[] = $statement1;
		$statements[] = $statement2;

		$this->assertSame( 2, $statements->count() );
		$this->assertEquals( $statement1, $statements[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $statements[$statement2->getGuid()] );

		$statements[] = $statement1;
		$statements[] = $statement2;

		$this->assertSame( 2, $statements->count() );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testAppendWithNonClaimFails() {
		$statements = new Claims();
		$statements->append( 'bad' );
	}

	public function testOffsetSet() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements->offsetSet( $statement1->getGuid(), $statement1 );
		$statements->offsetSet( $statement2->getGuid(), $statement2 );

		$this->assertSame( 2, $statements->count() );
		$this->assertEquals( $statement1, $statements[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $statements[$statement2->getGuid()] );

		$statements->offsetSet( $statement1->getGuid(), $statement1 );
		$statements->offsetSet( $statement2->getGuid(), $statement2 );

		$this->assertSame( 2, $statements->count() );

		$this->setExpectedException( 'InvalidArgumentException' );
		$statements->offsetSet( 'spam', $statement1 );
	}

	public function testOffsetSetOperator() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statements[$statement1->getGuid()] = $statement1;
		$statements[$statement2->getGuid()] = $statement2;

		$this->assertSame( 2, $statements->count() );
		$this->assertEquals( $statement1, $statements[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $statements[$statement2->getGuid()] );

		$statements[$statement1->getGuid()] = $statement1;
		$statements[$statement2->getGuid()] = $statement2;

		$this->assertSame( 2, $statements->count() );
	}

	public function testGuidNormalization() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$statement1LowerGuid = strtolower( $statement1->getGuid() );
		$statement2UpperGuid = strtoupper( $statement2->getGuid() );

		$statements->addClaim( $statement1 );
		$statements->addClaim( $statement2 );
		$this->assertSame( 2, $statements->count() );

		$this->assertEquals( $statement1, $statements->getClaimWithGuid( $statement1LowerGuid ) );
		$this->assertEquals( $statement2, $statements->getClaimWithGuid( $statement2UpperGuid ) );

		$this->assertEquals( $statement1, $statements->offsetGet( $statement1LowerGuid ) );
		$this->assertEquals( $statement2, $statements->offsetGet( $statement2UpperGuid ) );

		$this->assertEquals( $statement1, $statements[$statement1LowerGuid] );
		$this->assertEquals( $statement2, $statements[$statement2UpperGuid] );

		$statements = new Claims();
		$statements->offsetSet( strtoupper( $statement1LowerGuid ), $statement1 );
		$statements->offsetSet( strtolower( $statement2UpperGuid ), $statement2 );
		$this->assertSame( 2, $statements->count() );

		$this->assertEquals( $statement1, $statements->getClaimWithGuid( $statement1LowerGuid ) );
		$this->assertEquals( $statement2, $statements->getClaimWithGuid( $statement2UpperGuid ) );
	}

	public function testGetMainSnaks() {
		$statements = new Claims( array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P42' ) ) ),
			$this->makeClaim( new PropertySomeValueSnak( new PropertyId( 'P42' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P23' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P9000' ) ) ),
		) );

		$snaks = $statements->getMainSnaks();
		$this->assertInternalType( 'array', $snaks );
		$this->assertSameSize( $statements, $snaks );

		foreach ( $snaks as $guid => $snak ) {
			$this->assertInstanceOf( 'Wikibase\DataModel\Snak\Snak', $snak );
			$this->assertTrue( $statements->hasClaimWithGuid( $guid ) );
		}
	}

	public function testGetGuids() {
		$statements = new Claims( array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P42' ) ) ),
			$this->makeClaim( new PropertySomeValueSnak( new PropertyId( 'P42' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P23' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P9000' ) ) ),
		) );

		$guids = $statements->getGuids();
		$this->assertInternalType( 'array', $guids );
		$this->assertSameSize( $statements, $guids );

		foreach ( $guids as $guid ) {
			$this->assertInternalType( 'string', $guid );
			$this->assertTrue( $statements->hasClaimWithGuid( $guid ) );
		}
	}

	public function testGetHashes() {
		$statements = new Claims( array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P42' ) ) ),
			$this->makeClaim( new PropertySomeValueSnak( new PropertyId( 'P42' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P23' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P9000' ) ) ),
		) );

		$hashes = $statements->getHashes();
		$this->assertInternalType( 'array', $hashes );
		$this->assertContainsOnly( 'string', $hashes );
		$this->assertSameSize( $statements, $hashes );

		$hashSet = array_flip( $hashes );

		/**
		 * @var Claim $statement
		 */
		foreach ( $statements as $statement ) {
			$hash = $statement->getHash();
			$this->assertArrayHasKey( $hash, $hashSet );
		}
	}

	public function testGetClaimsForProperty() {
		$statements = new Claims( array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P42' ) ) ),
			$this->makeClaim( new PropertySomeValueSnak( new PropertyId( 'P42' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P23' ) ) ),
		) );

		$matches = $statements->getClaimsForProperty( new PropertyId( 'P42' ) );
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claims', $statements );
		$this->assertSame( 2, $matches->count() );

		$matches = $statements->getClaimsForProperty( new PropertyId( 'P23' ) );
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claims', $statements );
		$this->assertSame( 1, $matches->count() );

		$matches = $statements->getClaimsForProperty( new PropertyId( 'P9000' ) );
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claims', $statements );
		$this->assertSame( 0, $matches->count() );
	}

	/**
	 * Attempts to add Claims with no GUID set will fail.
	 */
	public function testNoGuidFailure() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$list = new Claims();

		$this->setExpectedException( 'InvalidArgumentException' );
		$list->addClaim( $statement );
	}

	public function testDuplicateClaims() {
		$firstClaim = $this->makeClaim( new PropertyNoValueSnak( 42 ) );
		$secondClaim = $this->makeClaim( new PropertyNoValueSnak( 42 ) );

		$list = new Claims();
		$list->addClaim( $firstClaim );
		$list->addClaim( $secondClaim );

		$this->assertEquals( 2, $list->count(), 'Adding two duplicates to an empty list should result in a count of two' );

		$this->assertEquals( $firstClaim, $list->getClaimWithGuid( $firstClaim->getGuid() ) );
		$this->assertEquals( $secondClaim, $list->getClaimWithGuid( $secondClaim->getGuid() ) );

		$list->removeClaimWithGuid( $secondClaim->getGuid() );

		$this->assertNotNull( $list->getClaimWithGuid( $firstClaim->getGuid() ) );
		$this->assertNull( $list->getClaimWithGuid( $secondClaim->getGuid() ) );
	}

	public function testGetHash() {
		$statementsA = new Claims();
		$statementsB = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$this->assertEquals( $statementsA->getHash(), $statementsB->getHash(), 'empty list' );

		$statementsA->addClaim( $statement1 );
		$statementsB->addClaim( $statement2 );
		$this->assertNotEquals( $statementsA->getHash(), $statementsB->getHash(), 'different content' );

		$statementsA->addClaim( $statement2 );
		$statementsB->addClaim( $statement1 );
		$this->assertNotEquals( $statementsA->getHash(), $statementsB->getHash(), 'different order' );

		$statementsA->removeClaim( $statement1 );
		$statementsB->removeClaim( $statement1 );
		$this->assertEquals( $statementsA->getHash(), $statementsB->getHash(), 'same content' );
	}

	public function testIterator() {
		$expected = array(
			'TESTCLAIM1' => $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P42' ) ), 'testclaim1' ),
			'TESTCLAIM2' => $this->makeClaim( new PropertySomeValueSnak( new PropertyId( 'P42' ) ), 'testclaim2' ),
			'TESTCLAIM3' => $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P23' ) ), 'testclaim3' ),
			'TESTCLAIM4' => $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P9000' ) ), 'testclaim4' ),
		);

		$statements = new Claims( $expected );
		$actual = iterator_to_array( $statements->getIterator() );

		$this->assertSame( $expected, $actual );
	}

	public function testIsEmpty() {
		$statements = new Claims();
		$statement1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );

		$this->assertTrue( $statements->isEmpty() );

		$statements->addClaim( $statement1 );
		$this->assertFalse( $statements->isEmpty() );

		$statements->removeClaim( $statement1 );
		$this->assertTrue( $statements->isEmpty() );
	}

	public function provideGetByRank() {
		$s1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) );
		$s1->setRank( Statement::RANK_DEPRECATED );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) );
		$s2->setRank( Statement::RANK_PREFERRED );

		$s3 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P3' ) ) );
		$s3->setRank( Statement::RANK_PREFERRED );

		return array(
			// Empty yields empty
			array(
				new Claims(),
				Statement::RANK_NORMAL,
				new Claims()
			),
			// One statement with RANK_PREFERRED, so return it
			array(
				new Claims( array( $s2 ) ),
				Statement::RANK_PREFERRED,
				new Claims( array( $s2 ) ),
			),
			// s2 and s3 have RANK_PREFERRED, so return them
			array(
				new Claims( array( $s2, $s1, $s3 ) ),
				Statement::RANK_PREFERRED,
				new Claims( array( $s2, $s3 ) ),
			),
		);
	}

	/**
	 * @dataProvider provideGetByRank
	 */
	public function testGetByRank( Claims $input, $rank, $expected ) {
		$this->assertEquals( $input->getByRank( $rank ), $expected );
	}

	public function provideGetByRanks() {
		$s1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) );
		$s1->setRank( Statement::RANK_NORMAL );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) );
		$s2->setRank( Statement::RANK_PREFERRED );

		$ret = array(
			// s1 has RANK_NORMAL, thus doesn't match
			array(
				new Claims( array( $s2, $s1 ) ),
				array( Statement::RANK_PREFERRED, Statement::RANK_DEPRECATED ),
				new Claims( array( $s2 ) ),
			),
			// s2 has RANK_PREFERRED and s1 has RANK_NORMAL, so return them
			array(
				new Claims( array( $s2, $s1 ) ),
				array( Statement::RANK_PREFERRED, Statement::RANK_NORMAL ),
				new Claims( array( $s2, $s1 ) ),
			)
		);

		// This function acts very similar to Claims::getByRank, so that we
		// can reuse the test cases
		$ret = array_merge( $this->provideGetByRank(), $ret );

		return $ret;
	}

	/**
	 * @dataProvider provideGetByRanks
	 */
	public function testGetByRanks( Claims $input, $ranks, $expected ) {
		if ( !is_array( $ranks ) ) {
			$ranks = array( $ranks );
		}

		$this->assertEquals( $input->getByRanks( $ranks ), $expected );
	}

	public function testGetBestClaimsEmpty() {
		$statements = new Claims();
		$this->assertEquals( $statements->getBestClaims(), new Claims() );
	}

	public function testGetBestClaimsOnlyOne() {
		$statement = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) );
		$statement->setRank( Statement::RANK_NORMAL );

		$statements = new Claims( array( $statement ) );
		$this->assertEquals( $statements->getBestClaims(), $statements );
	}

	public function testGetBestClaimsNoDeprecated() {
		$statement = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) );
		$statement->setRank( Statement::RANK_DEPRECATED );

		$statements = new Claims( array( $statement ) );
		$this->assertEquals( $statements->getBestClaims(), new Claims() );
	}

	public function testGetBestClaimsReturnOne() {
		$s1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) );
		$s1->setRank( Statement::RANK_DEPRECATED );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) );
		$s2->setRank( Statement::RANK_NORMAL );

		$statements = new Claims( array( $s1, $s2 ) );
		$expected = new Claims( array( $s2 ) );
		$this->assertEquals( $statements->getBestClaims(), $expected );
	}

	public function testGetBestClaimsReturnTwo() {
		$s1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) );
		$s1->setRank( Statement::RANK_NORMAL );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) );
		$s2->setRank( Statement::RANK_PREFERRED );

		$s3 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P3' ) ) );
		$s3->setRank( Statement::RANK_PREFERRED );

		$statements = new Claims( array( $s3, $s1, $s2 ) );
		$expected = new Claims( array( $s2, $s3 ) );
		$this->assertEquals( $statements->getBestClaims(), $expected );
	}

	public function testEmptyListEqualsEmptyList() {
		$list = new Claims( array() );
		$this->assertTrue( $list->equals( clone $list ) );
	}

	public function testFilledListEqualsItself() {
		$list = new Claims( array(
			$this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ),
			$this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) ),
		) );

		$this->assertTrue( $list->equals( $list ) );
		$this->assertTrue( $list->equals( clone $list ) );
	}

	public function testGivenNonClaimList_equalsReturnsFalse() {
		$list = new Claims( array() );

		$this->assertFalse( $list->equals( null ) );
		$this->assertFalse( $list->equals( new \stdClass() ) );
	}

	public function testGivenDifferentList_equalsReturnsFalse() {
		$list = new Claims( array(
			$this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ),
			$this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) ),
		) );

		$otherList = new Claims( array(
			$this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P3' ) ) ),
			$this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P4' ) ) ),
		) );

		$this->assertFalse( $list->equals( $otherList ) );
	}

	public function testGivenDifferentClaimWithSameGuid_equalsReturnsFalse() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'kittens' );

		$newClaim = new Statement( new PropertyNoValueSnak( 1337 ) );
		$newClaim->setGuid( 'kittens' );

		$list = new Claims( array( $statement ) );
		$newList = new Claims( array( $newClaim ) );

		$this->assertFalse( $list->equals( $newList ) );
	}

}
