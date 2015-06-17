<?php

namespace Wikibase\DataModel\Tests\Claim;

use InvalidArgumentException;
use ReflectionClass;
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

	private $guidCounter = 0;

	private function makeStatement( Snak $mainSnak, $guid = null ) {
		if ( $guid === null ) {
			$this->guidCounter++;
			$guid = 'TEST$statement-' . $this->guidCounter;
		}

		$claim = new Statement( $mainSnak );
		$claim->setGuid( $guid );

		return $claim;
	}

	public function testArrayObjectNotConstructedFromObject() {
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( 1 ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( 2 ) );

		$statementList = new StatementList();
		$statementList->addStatement( $statement1 );

		$claims = new Claims( $statementList );
		// According to the documentation append() "cannot be called when the ArrayObject was
		// constructed from an object." This test makes sure it was not constructed from an object.
		$claims->append( $statement2 );

		$this->assertSame( 2, $claims->count() );
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

	public function testHasClaimWithGuid() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$this->assertFalse( $claims->hasClaimWithGuid( $statement1->getGuid() ) );
		$this->assertFalse( $claims->hasClaimWithGuid( $statement2->getGuid() ) );

		$claims->addClaim( $statement1 );
		$this->assertTrue( $claims->hasClaimWithGuid( $statement1->getGuid() ) );
		$this->assertFalse( $claims->hasClaimWithGuid( $statement2->getGuid() ) );

		$claims->addClaim( $statement2 );
		$this->assertTrue( $claims->hasClaimWithGuid( $statement1->getGuid() ) );
		$this->assertTrue( $claims->hasClaimWithGuid( $statement2->getGuid() ) );
	}

	public function testRemoveClaimWithGuid() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims->addClaim( $statement1 );
		$claims->addClaim( $statement2 );
		$this->assertSame( 2, $claims->count() );

		$claims->removeClaimWithGuid( $statement1->getGuid() );
		$this->assertSame( 1, $claims->count() );

		$claims->removeClaimWithGuid( $statement2->getGuid() );
		$this->assertSame( 0, $claims->count() );
	}

	public function testOffsetUnset() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims->addClaim( $statement1 );
		$claims->addClaim( $statement2 );
		$this->assertSame( 2, $claims->count() );

		$claims->offsetUnset( $statement1->getGuid() );
		$this->assertSame( 1, $claims->count() );

		$claims->offsetUnset( $statement2->getGuid() );
		$this->assertSame( 0, $claims->count() );
	}

	public function testGetClaimWithGuid() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims->addClaim( $statement1 );
		$this->assertSame( $statement1, $claims->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertNull( $claims->getClaimWithGuid( $statement2->getGuid() ) );

		$claims->addClaim( $statement2 );
		$this->assertSame( $statement1, $claims->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertSame( $statement2, $claims->getClaimWithGuid( $statement2->getGuid() ) );
	}

	public function testOffsetGet() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims->addClaim( $statement1 );
		$this->assertSame( $statement1, $claims->offsetGet( $statement1->getGuid() ) );

		$claims->addClaim( $statement2 );
		$this->assertSame( $statement1, $claims->offsetGet( $statement1->getGuid() ) );
		$this->assertSame( $statement2, $claims->offsetGet( $statement2->getGuid() ) );
	}

	public function testAddClaim() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims->addClaim( $statement1 );
		$claims->addClaim( $statement2 );

		$this->assertSame( 2, $claims->count() );
		$this->assertEquals( $statement1, $claims[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $claims[$statement2->getGuid()] );

		$claims->addClaim( $statement1 );
		$claims->addClaim( $statement2 );

		$this->assertSame( 2, $claims->count() );

		$this->assertNotNull( $claims->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertNotNull( $claims->getClaimWithGuid( $statement2->getGuid() ) );

		$this->assertSame( array(
			'TEST$STATEMENT-1' => $statement1,
			'TEST$STATEMENT-2' => $statement2,
		), $claims->getArrayCopy() );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testAddClaimWithIndexFails() {
		$claims = new Claims();
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$claims->addClaim( $statement, 0 );
	}

	public function testAppend() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims->append( $statement1 );
		$claims->append( $statement2 );

		$this->assertSame( 2, $claims->count() );
		$this->assertEquals( $statement1, $claims[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $claims[$statement2->getGuid()] );

		$claims->append( $statement1 );
		$claims->append( $statement2 );

		$this->assertSame( 2, $claims->count() );
	}

	public function testAppendOperator() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims[] = $statement1;
		$claims[] = $statement2;

		$this->assertSame( 2, $claims->count() );
		$this->assertEquals( $statement1, $claims[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $claims[$statement2->getGuid()] );

		$claims[] = $statement1;
		$claims[] = $statement2;

		$this->assertSame( 2, $claims->count() );
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
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims->offsetSet( $statement1->getGuid(), $statement1 );
		$claims->offsetSet( $statement2->getGuid(), $statement2 );

		$this->assertSame( 2, $claims->count() );
		$this->assertEquals( $statement1, $claims[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $claims[$statement2->getGuid()] );

		$claims->offsetSet( $statement1->getGuid(), $statement1 );
		$claims->offsetSet( $statement2->getGuid(), $statement2 );

		$this->assertSame( 2, $claims->count() );

		$this->setExpectedException( 'InvalidArgumentException' );
		$claims->offsetSet( 'spam', $statement1 );
	}

	public function testOffsetSetOperator() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claims[$statement1->getGuid()] = $statement1;
		$claims[$statement2->getGuid()] = $statement2;

		$this->assertSame( 2, $claims->count() );
		$this->assertEquals( $statement1, $claims[$statement1->getGuid()] );
		$this->assertEquals( $statement2, $claims[$statement2->getGuid()] );

		$claims[$statement1->getGuid()] = $statement1;
		$claims[$statement2->getGuid()] = $statement2;

		$this->assertSame( 2, $claims->count() );
	}

	public function testGuidNormalization() {
		$claims = new Claims();
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P15' ) ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P16' ) ) );

		$claim1LowerGuid = strtolower( $statement1->getGuid() );
		$claim2UpperGuid = strtoupper( $statement2->getGuid() );

		$claims->addClaim( $statement1 );
		$claims->addClaim( $statement2 );
		$this->assertSame( 2, $claims->count() );

		$this->assertEquals( $statement1, $claims->getClaimWithGuid( $claim1LowerGuid ) );
		$this->assertEquals( $statement2, $claims->getClaimWithGuid( $claim2UpperGuid ) );

		$this->assertEquals( $statement1, $claims->offsetGet( $claim1LowerGuid ) );
		$this->assertEquals( $statement2, $claims->offsetGet( $claim2UpperGuid ) );

		$this->assertEquals( $statement1, $claims[$claim1LowerGuid] );
		$this->assertEquals( $statement2, $claims[$claim2UpperGuid] );

		$claims = new Claims();
		$claims->offsetSet( strtoupper( $claim1LowerGuid ), $statement1 );
		$claims->offsetSet( strtolower( $claim2UpperGuid ), $statement2 );
		$this->assertSame( 2, $claims->count() );

		$this->assertEquals( $statement1, $claims->getClaimWithGuid( $claim1LowerGuid ) );
		$this->assertEquals( $statement2, $claims->getClaimWithGuid( $claim2UpperGuid ) );
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
		$statement1 = $this->makeStatement( new PropertyNoValueSnak( 42 ) );
		$statement2 = $this->makeStatement( new PropertyNoValueSnak( 42 ) );

		$list = new Claims();
		$list->addClaim( $statement1 );
		$list->addClaim( $statement2 );

		$this->assertEquals( 2, $list->count(), 'Adding two duplicates to an empty list should result in a count of two' );

		$this->assertEquals( $statement1, $list->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertEquals( $statement2, $list->getClaimWithGuid( $statement2->getGuid() ) );

		$list->removeClaimWithGuid( $statement2->getGuid() );

		$this->assertNotNull( $list->getClaimWithGuid( $statement1->getGuid() ) );
		$this->assertNull( $list->getClaimWithGuid( $statement2->getGuid() ) );
	}

	public function testIterator() {
		$expected = array(
			'GUID1' => $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P42' ) ), 'guid1' ),
			'GUID2' => $this->makeStatement( new PropertySomeValueSnak( new PropertyId( 'P42' ) ), 'guid2' ),
			'GUID3' => $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P23' ) ), 'guid3' ),
			'GUID4' => $this->makeStatement( new PropertyNoValueSnak( new PropertyId( 'P9000' ) ), 'guid4' ),
		);

		$claims = new Claims( $expected );
		$actual = iterator_to_array( $claims->getIterator() );

		$this->assertSame( $expected, $actual );
	}

}
