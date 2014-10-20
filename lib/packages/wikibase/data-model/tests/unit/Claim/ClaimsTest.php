<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use ReflectionClass;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimList;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

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
			$guid = 'TEST$claim-' . $this->guidCounter;
		}

		$claim = new Claim( $mainSnak );
		$claim->setGuid( $guid );

		return $claim;
	}

	protected function makeStatement( Snak $mainSnak, $guid = null ) {
		if ( $guid === null ) {
			$this->guidCounter++;
			$guid = 'TEST$statement-' . $this->guidCounter;
		}

		$claim = new Statement( new Claim( $mainSnak ) );
		$claim->setGuid( $guid );

		return $claim;
	}

	public function testArrayObjectNotConstructedFromObject() {
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( 1 ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( 2 ) );

		$claimList = new ClaimList();
		$claimList->addClaim( $claim1 );

		$claims = new Claims( $claimList );
		// According to the documentation append() "cannot be called when the ArrayObject was
		// constructed from an object." This test makes sure it was not constructed from an object.
		$claims->append( $claim2 );

		$this->assertCount( 2, $claims );
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
			array( array( "foo" ) ),
		);
	}

	public function testHasClaim() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$this->assertFalse( $claims->hasClaim( $claim1 ) );
		$this->assertFalse( $claims->hasClaim( $claim2 ) );

		$claims->addClaim( $claim1 );
		$this->assertTrue( $claims->hasClaim( $claim1 ) );
		$this->assertFalse( $claims->hasClaim( $claim2 ) );

		$claims->addClaim( $claim2 );
		$this->assertTrue( $claims->hasClaim( $claim1 ) );
		$this->assertTrue( $claims->hasClaim( $claim2 ) );

		// no guid
		$claim0 = new Claim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$this->assertFalse( $claims->hasClaim( $claim0 ) );
	}

	public function testHasClaimWithGuid() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$this->assertFalse( $claims->hasClaimWithGuid( $claim1->getGuid() ) );
		$this->assertFalse( $claims->hasClaimWithGuid( $claim2->getGuid() ) );

		$claims->addClaim( $claim1 );
		$this->assertTrue( $claims->hasClaimWithGuid( $claim1->getGuid() ) );
		$this->assertFalse( $claims->hasClaimWithGuid( $claim2->getGuid() ) );

		$claims->addClaim( $claim2 );
		$this->assertTrue( $claims->hasClaimWithGuid( $claim1->getGuid() ) );
		$this->assertTrue( $claims->hasClaimWithGuid( $claim2->getGuid() ) );
	}

	public function testRemoveClaim() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims->addClaim( $claim1 );
		$claims->addClaim( $claim2 );
		$this->assertCount( 2, $claims );

		$claims->removeClaim( $claim1 );
		$this->assertFalse( $claims->hasClaim( $claim1 ) );
		$this->assertNull( $claims->getClaimWithGuid( $claim1->getGuid() ) );
		$this->assertCount( 1, $claims );

		$claims->removeClaim( $claim2 );
		$this->assertFalse( $claims->hasClaim( $claim2 ) );
		$this->assertNull( $claims->getClaimWithGuid( $claim2->getGuid() ) );
		$this->assertCount( 0, $claims );

		// no guid
		$claim0 = new Claim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claims->removeClaim( $claim0 );
	}

	public function testRemoveClaimWithGuid() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims->addClaim( $claim1 );
		$claims->addClaim( $claim2 );
		$this->assertCount( 2, $claims );

		$claims->removeClaimWithGuid( $claim1->getGuid() );
		$this->assertFalse( $claims->hasClaim( $claim1 ) );
		$this->assertNull( $claims->getClaimWithGuid( $claim1->getGuid() ) );
		$this->assertCount( 1, $claims );

		$claims->removeClaimWithGuid( $claim2->getGuid() );
		$this->assertFalse( $claims->hasClaim( $claim2 ) );
		$this->assertNull( $claims->getClaimWithGuid( $claim2->getGuid() ) );
		$this->assertCount( 0, $claims );
	}

	public function testOffsetUnset() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims->addClaim( $claim1 );
		$claims->addClaim( $claim2 );
		$this->assertCount( 2, $claims );

		$claims->offsetUnset( $claim1->getGuid() );
		$this->assertFalse( $claims->hasClaim( $claim1 ) );
		$this->assertNull( $claims->getClaimWithGuid( $claim1->getGuid() ) );
		$this->assertCount( 1, $claims );

		$claims->offsetUnset( $claim2->getGuid() );
		$this->assertFalse( $claims->hasClaim( $claim2 ) );
		$this->assertNull( $claims->getClaimWithGuid( $claim2->getGuid() ) );
		$this->assertCount( 0, $claims );
	}

	public function testGetClaimWithGuid() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims->addClaim( $claim1 );
		$this->assertSame( $claim1, $claims->getClaimWithGuid( $claim1->getGuid() ) );
		$this->assertNull( $claims->getClaimWithGuid( $claim2->getGuid() ) );

		$claims->addClaim( $claim2 );
		$this->assertSame( $claim1, $claims->getClaimWithGuid( $claim1->getGuid() ) );
		$this->assertSame( $claim2, $claims->getClaimWithGuid( $claim2->getGuid() ) );
	}

	public function testOffsetGet() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims->addClaim( $claim1 );
		$this->assertSame( $claim1, $claims->offsetGet( $claim1->getGuid() ) );

		$claims->addClaim( $claim2 );
		$this->assertSame( $claim1, $claims->offsetGet( $claim1->getGuid() ) );
		$this->assertSame( $claim2, $claims->offsetGet( $claim2->getGuid() ) );
	}

	public function testAddClaim() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims->addClaim( $claim1 );
		$claims->addClaim( $claim2 );

		$this->assertCount( 2, $claims );
		$this->assertEquals( $claim1, $claims[$claim1->getGuid()] );
		$this->assertEquals( $claim2, $claims[$claim2->getGuid()] );

		$claims->addClaim( $claim1 );
		$claims->addClaim( $claim2 );

		$this->assertCount( 2, $claims );

		$this->assertNotNull( $claims->getClaimWithGuid( $claim1->getGuid() ) );
		$this->assertNotNull( $claims->getClaimWithGuid( $claim2->getGuid() ) );

		// Insert claim at the beginning:
		$claim3 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P17' ) ) );
		$claims->addClaim( $claim3, 0 );
		$this->assertEquals( 0, $claims->indexOf( $claim3 ), 'Inserting claim at the beginning failed' );

		// Insert claim at another index:
		$claim4 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P18' ) ) );
		$claims->addClaim( $claim4, 1 );
		$this->assertEquals( 1, $claims->indexOf( $claim4 ), 'Inserting claim at index 1 failed' );

		// Insert claim with an index out of bounds:
		$claim5 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P19' ) ) );
		$claims->addClaim( $claim5, 99999 );
		$this->assertEquals( 4, $claims->indexOf( $claim5 ), 'Appending claim by specifying an index out of bounds failed' );
	}

	public function testIndexOf() {
		$claims = new Claims();
		$claimArray = array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P3' ) ) ),
		);
		$excludedClaim = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( 'P99' ) ) );

		foreach( $claimArray as $claim ) {
			$claims->addClaim( $claim );
		}

		$this->assertFalse( $claims->indexOf( $excludedClaim ) );

		$i = 0;
		foreach( $claimArray as $claim ) {
			$this->assertEquals( $i++, $claims->indexOf( $claim ) );
		}
	}

	public function testAppend() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims->append( $claim1 );
		$claims->append( $claim2 );

		$this->assertCount( 2, $claims );
		$this->assertEquals( $claim1, $claims[$claim1->getGuid()] );
		$this->assertEquals( $claim2, $claims[$claim2->getGuid()] );

		$claims->append( $claim1 );
		$claims->append( $claim2 );

		$this->assertCount( 2, $claims );
	}

	public function testAppendOperator() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims[] = $claim1;
		$claims[] = $claim2;

		$this->assertCount( 2, $claims );
		$this->assertEquals( $claim1, $claims[$claim1->getGuid()] );
		$this->assertEquals( $claim2, $claims[$claim2->getGuid()] );

		$claims[] = $claim1;
		$claims[] = $claim2;

		$this->assertCount( 2, $claims );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testAppendWithNonClaimFails() {
		$claims = new Claims();
		$claims->append( 'bad' );
	}

	public function testOffsetSet() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims->offsetSet( $claim1->getGuid(), $claim1 );
		$claims->offsetSet( $claim2->getGuid(), $claim2 );

		$this->assertCount( 2, $claims );
		$this->assertEquals( $claim1, $claims[$claim1->getGuid()] );
		$this->assertEquals( $claim2, $claims[$claim2->getGuid()] );

		$claims->offsetSet( $claim1->getGuid(), $claim1 );
		$claims->offsetSet( $claim2->getGuid(), $claim2 );

		$this->assertCount( 2, $claims );

		$this->setExpectedException( 'InvalidArgumentException' );
		$claims->offsetSet( 'spam', $claim1 );
	}

	public function testOffsetSetOperator() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claims[$claim1->getGuid()] = $claim1;
		$claims[$claim2->getGuid()] = $claim2;

		$this->assertCount( 2, $claims );
		$this->assertEquals( $claim1, $claims[$claim1->getGuid()] );
		$this->assertEquals( $claim2, $claims[$claim2->getGuid()] );

		$claims[$claim1->getGuid()] = $claim1;
		$claims[$claim2->getGuid()] = $claim2;

		$this->assertCount( 2, $claims );
	}

	public function testGuidNormalization() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$claim1LowerGuid = strtolower( $claim1->getGuid() );
		$claim2UpperGuid = strtoupper( $claim2->getGuid() );

		$claims->addClaim( $claim1 );
		$claims->addClaim( $claim2 );
		$this->assertCount( 2, $claims );

		$this->assertEquals( $claim1, $claims->getClaimWithGuid( $claim1LowerGuid ) );
		$this->assertEquals( $claim2, $claims->getClaimWithGuid( $claim2UpperGuid ) );

		$this->assertEquals( $claim1, $claims->offsetGet( $claim1LowerGuid ) );
		$this->assertEquals( $claim2, $claims->offsetGet( $claim2UpperGuid ) );

		$this->assertEquals( $claim1, $claims[$claim1LowerGuid] );
		$this->assertEquals( $claim2, $claims[$claim2UpperGuid] );

		$claims = new Claims();
		$claims->offsetSet( strtoupper( $claim1LowerGuid ), $claim1 );
		$claims->offsetSet( strtolower( $claim2UpperGuid ), $claim2 );
		$this->assertCount( 2, $claims );

		$this->assertEquals( $claim1, $claims->getClaimWithGuid( $claim1LowerGuid ) );
		$this->assertEquals( $claim2, $claims->getClaimWithGuid( $claim2UpperGuid ) );
	}

	public function testGetMainSnaks() {
		$claims = new Claims( array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P42" ) ) ),
			$this->makeClaim( new PropertySomeValueSnak( new PropertyId( "P42" ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P23" ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P9000" ) ) ),
		) );

		$snaks = $claims->getMainSnaks();
		$this->assertInternalType( 'array', $snaks );
		$this->assertSameSize( $claims, $snaks );

		foreach ( $snaks as $guid => $snak ) {
			$this->assertInstanceOf( 'Wikibase\DataModel\Snak\Snak', $snak );
			$this->assertTrue( $claims->hasClaimWithGuid( $guid ) );
		}
	}

	public function testGetGuids() {
		$claims = new Claims( array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P42" ) ) ),
			$this->makeClaim( new PropertySomeValueSnak( new PropertyId( "P42" ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P23" ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P9000" ) ) ),
		) );

		$guids = $claims->getGuids();
		$this->assertInternalType( 'array', $guids );
		$this->assertSameSize( $claims, $guids );

		foreach ( $guids as $guid ) {
			$this->assertInternalType( 'string', $guid );
			$this->assertTrue( $claims->hasClaimWithGuid( $guid ) );
		}
	}

	public function testGetHashes() {
		$claims = new Claims( array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P42" ) ) ),
			$this->makeClaim( new PropertySomeValueSnak( new PropertyId( "P42" ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P23" ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P9000" ) ) ),
		) );

		$hashes = $claims->getHashes();
		$this->assertInternalType( 'array', $hashes );
		$this->assertContainsOnly( 'string', $hashes );
		$this->assertSameSize( $claims, $hashes );

		$hashSet = array_flip( $hashes );

		/**
		 * @var Claim $claim
		 */
		foreach ( $claims as $claim ) {
			$hash = $claim->getHash();
			$this->assertArrayHasKey( $hash, $hashSet );
		}
	}

	public function testGetClaimsForProperty() {
		$claims = new Claims( array(
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P42" ) ) ),
			$this->makeClaim( new PropertySomeValueSnak( new PropertyId( "P42" ) ) ),
			$this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P23" ) ) ),
		) );

		$matches = $claims->getClaimsForProperty( new PropertyId( 'P42' ) );
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claims', $claims );
		$this->assertCount( 2, $matches );

		$matches = $claims->getClaimsForProperty( new PropertyId( 'P23' ) );
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claims', $claims );
		$this->assertCount( 1, $matches );

		$matches = $claims->getClaimsForProperty( new PropertyId( 'P9000' ) );
		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claims', $claims );
		$this->assertCount( 0, $matches );
	}

	/**
	 * Attempts to add Claims with no GUID set will fail.
	 */
	public function testNoGuidFailure() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$list = new Claims();

		$this->setExpectedException( 'InvalidArgumentException' );
		$list->addClaim( $claim );
	}

	public function testDuplicateClaims() {
		$firstClaim = $this->makeClaim( new PropertyNoValueSnak( 42 ) );
		$secondClaim = $this->makeClaim( new PropertyNoValueSnak( 42 ) );

		$list = new Claims();
		$list->addClaim( $firstClaim );
		$list->addClaim( $secondClaim );

		$this->assertEquals( 2, count( $list ), 'Adding two duplicates to an empty list should result in a count of two' );

		$this->assertEquals( $firstClaim, $list->getClaimWithGuid( $firstClaim->getGuid() ) );
		$this->assertEquals( $secondClaim, $list->getClaimWithGuid( $secondClaim->getGuid() ) );

		$list->removeClaimWithGuid( $secondClaim->getGuid() );

		$this->assertNotNull( $list->getClaimWithGuid( $firstClaim->getGuid() ) );
		$this->assertNull( $list->getClaimWithGuid( $secondClaim->getGuid() ) );
	}

	public function testGetHash() {
		$claimsA = new Claims();
		$claimsB = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );
		$claim2 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P16" ) ) );

		$this->assertEquals( $claimsA->getHash(), $claimsB->getHash(), 'empty list' );

		$claimsA->addClaim( $claim1 );
		$claimsB->addClaim( $claim2 );
		$this->assertNotEquals( $claimsA->getHash(), $claimsB->getHash(), 'different content' );

		$claimsA->addClaim( $claim2 );
		$claimsB->addClaim( $claim1 );
		$this->assertNotEquals( $claimsA->getHash(), $claimsB->getHash(), 'different order' );

		$claimsA->removeClaim( $claim1 );
		$claimsB->removeClaim( $claim1 );
		$this->assertEquals( $claimsA->getHash(), $claimsB->getHash(), 'same content' );
	}

	public function testIterator() {
		$array = array(
			'TESTCLAIM1' => $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P42" ) ), 'testclaim1' ),
			'TESTCLAIM2' => $this->makeClaim( new PropertySomeValueSnak( new PropertyId( "P42" ) ), 'testclaim2' ),
			'TESTCLAIM3' => $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P23" ) ), 'testclaim3' ),
			'TESTCLAIM4' => $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P9000" ) ), 'testclaim4' ),
		);
		$claims = new Claims( $array );

		$iterator = iterator_to_array( $claims->getIterator() );
		$this->assertSame( $array, $iterator );
	}

	public function testIsEmpty() {
		$claims = new Claims();
		$claim1 = $this->makeClaim( new PropertyNoValueSnak( new PropertyId( "P15" ) ) );

		$this->assertTrue( $claims->isEmpty() );

		$claims->addClaim( $claim1 );
		$this->assertFalse( $claims->isEmpty() );

		$claims->removeClaim( $claim1 );
		$this->assertTrue( $claims->isEmpty() );
	}

	public function provideGetByRank() {
		$s1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P1" ) ) );
		$s1->setRank( Statement::RANK_DEPRECATED );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P2" ) ) );
		$s2->setRank( Statement::RANK_PREFERRED );

		$s3 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P3" ) ) );
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
			// s2 has RANK_PREFERRED, so doesn't match RANK_TRUTH
			array(
				new Claims( array( $s2 ) ),
				Claim::RANK_TRUTH,
				new Claims(),
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
		$s1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P1" ) ) );
		$s1->setRank( Statement::RANK_NORMAL );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P2" ) ) );
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
		$claims = new Claims();
		$this->assertEquals( $claims->getBestClaims(), new Claims() );
	}

	public function testGetBestClaimsOnlyOne() {
		$statement = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P1" ) ) );
		$statement->setRank( Statement::RANK_NORMAL );

		$claims = new Claims( array( $statement ) );
		$this->assertEquals( $claims->getBestClaims(), $claims );
	}

	public function testGetBestClaimsNoDeprecated() {
		$statement = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P1" ) ) );
		$statement->setRank( Statement::RANK_DEPRECATED );

		$claims = new Claims( array( $statement ) );
		$this->assertEquals( $claims->getBestClaims(), new Claims() );
	}

	public function testGetBestClaimsReturnOne() {
		$s1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P1" ) ) );
		$s1->setRank( Statement::RANK_DEPRECATED );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P2" ) ) );
		$s2->setRank( Statement::RANK_NORMAL );

		$claims = new Claims( array( $s1, $s2 ) );
		$expected = new Claims( array( $s2 ) );
		$this->assertEquals( $claims->getBestClaims(), $expected );
	}

	public function testGetBestClaimsReturnTwo() {
		$s1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P1" ) ) );
		$s1->setRank( Statement::RANK_NORMAL );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P2" ) ) );
		$s2->setRank( Statement::RANK_PREFERRED );

		$s3 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P3" ) ) );
		$s3->setRank( Statement::RANK_PREFERRED );

		$claims = new Claims( array( $s3, $s1, $s2 ) );
		$expected = new Claims( array( $s2, $s3 ) );
		$this->assertEquals( $claims->getBestClaims(), $expected );
	}

	public function testGetBestClaimsReturnsTruthRanks() {
		$s1 = new Claim( new PropertyNoValueSnak( new PropertyId( "P1" ) ) );
		$s1->setGuid( 'kittens' );

		$s2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( "P2" ) ) );
		$s2->setRank( Statement::RANK_NORMAL );

		$claims = new Claims( array( $s1, $s2 ) );
		$expected = new Claims( array( $s1 ) );
		$this->assertEquals( $claims->getBestClaims(), $expected );
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
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'kittens' );

		$newClaim = new Claim( new PropertyNoValueSnak( 1337 ) );
		$newClaim->setGuid( 'kittens' );

		$list = new Claims( array( $claim ) );
		$newList = new Claims( array( $newClaim ) );

		$this->assertFalse( $list->equals( $newList ) );
	}

}
