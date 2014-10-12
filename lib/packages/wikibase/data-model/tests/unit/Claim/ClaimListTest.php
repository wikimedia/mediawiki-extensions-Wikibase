<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimList;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Claim\ClaimList
 * @uses Wikibase\DataModel\Claim\Claim
 * @uses Wikibase\DataModel\Claim\ClaimList
 * @uses Wikibase\DataModel\Snak\SnakList
 * @uses Wikibase\DataModel\Snak\PropertyValueSnak
 * @uses Wikibase\DataModel\Entity\PropertyId
 * @uses DataValues\StringValue
 * @uses InvalidArgumentException
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimListTest extends \PHPUnit_Framework_TestCase {

	public function testGivenNoClaims_getPropertyIdsReturnsEmptyArray() {
		$list = new ClaimList();

		$this->assertEquals( array(), $list->getPropertyIds() );
	}

	public function testGivenClaims_getPropertyIdsReturnsArrayWithoutDuplicates() {
		$list = new ClaimList( array(
			$this->getStubClaim( 1, 'kittens' ),
			$this->getStubClaim( 3, 'foo' ),
			$this->getStubClaim( 2, 'bar' ),
			$this->getStubClaim( 2, 'baz' ),
			$this->getStubClaim( 1, 'bah' ),
		) );

		$this->assertEquals(
			$this->propertyIdArray( 1, 3, 2 ),
			$list->getPropertyIds()
		);
	}

	private function propertyIdArray() {
		$properties = array();

		foreach ( func_get_args() as $number ) {
			$p = PropertyId::newFromNumber( $number );
			$properties[$p->getSerialization()] = $p;
		}

		return $properties;
	}

	private function getStubClaim( $propertyId, $guid ) {
		$claim = $this->getMockBuilder( 'Wikibase\DataModel\Claim\Claim' )
			->disableOriginalConstructor()->getMock();

		$claim->expects( $this->any() )
			->method( 'getGuid' )
			->will( $this->returnValue( $guid ) );

		$claim->expects( $this->any() )
			->method( 'getPropertyId' )
			->will( $this->returnValue( PropertyId::newFromNumber( $propertyId ) ) );

		$claim->expects( $this->any() )
			->method( 'getRank' )
			->will( $this->returnValue( Claim::RANK_TRUTH ) );

		return $claim;
	}

	public function testCanIterate() {
		$claim = $this->getStubClaim( 1, 'kittens' );
		$list = new ClaimList( array( $claim ) );

		foreach ( $list as $claimFormList ) {
			$this->assertEquals( $claim, $claimFormList );
		}
	}

	public function testGetUniqueMainSnaksReturnsListWithoutDuplicates() {
		$list = new ClaimList( array(
			$this->getClaimWithSnak( 1, 'foo' ),
			$this->getClaimWithSnak( 2, 'foo' ),
			$this->getClaimWithSnak( 1, 'foo' ),
			$this->getClaimWithSnak( 2, 'bar' ),
			$this->getClaimWithSnak( 1, 'bar' ),
		) );

		$this->assertEquals(
			array(
				$this->getClaimWithSnak( 1, 'foo' ),
				$this->getClaimWithSnak( 2, 'foo' ),
				$this->getClaimWithSnak( 2, 'bar' ),
				$this->getClaimWithSnak( 1, 'bar' ),
			),
			array_values( $list->getWithUniqueMainSnaks()->toArray() )
		);
	}

	private function getClaimWithSnak( $propertyId, $stringValue ) {
		$snak = $this->newSnak( $propertyId, $stringValue );
		$claim = new Claim( $snak );
		$claim->setGuid( sha1( $snak->getHash() ) );
		return $claim;
	}

	private function newSnak( $propertyId, $stringValue ) {
		return new PropertyValueSnak( $propertyId, new StringValue( $stringValue ) );
	}

	public function testAddClaimWithOnlyMainSnak() {
		$list = new ClaimList();

		$list->addNewClaim( $this->newSnak( 42, 'foo' ) );

		$this->assertEquals(
			new ClaimList( array(
				new Claim( $this->newSnak( 42, 'foo' ) )
			) ),
			$list
		);
	}

	public function testAddClaimWithQualifiersAsSnakArray() {
		$list = new ClaimList();

		$list->addNewClaim(
			$this->newSnak( 42, 'foo' ),
			array(
				$this->newSnak( 1, 'bar' )
			)
		);

		$this->assertEquals(
			new ClaimList( array(
				new Claim(
					$this->newSnak( 42, 'foo' ),
					new SnakList( array(
						$this->newSnak( 1, 'bar' )
					) )
				)
			) ),
			$list
		);
	}

	public function testAddClaimWithQualifiersAsSnakList() {
		$list = new ClaimList();
		$snakList = new SnakList( array(
			$this->newSnak( 1, 'bar' )
		) );

		$list->addNewClaim(
			$this->newSnak( 42, 'foo' ),
			$snakList
		);

		$this->assertEquals(
			new ClaimList( array(
				new Claim(
					$this->newSnak( 42, 'foo' ),
					$snakList
				)
			) ),
			$list
		);
	}

	public function testAddClaimWithGuid() {
		$list = new ClaimList();

		$list->addNewClaim(
			$this->newSnak( 42, 'foo' ),
			null,
			'kittens'
		);

		$claim = new Claim(
			$this->newSnak( 42, 'foo' ),
			null
		);

		$claim->setGuid( 'kittens' );

		$this->assertEquals(
			new ClaimList( array( $claim ) ),
			$list
		);
	}

	public function testCanConstructWithClaimsObject() {
		$claimArray = array(
			$this->getClaimWithSnak( 1, 'foo' ),
			$this->getClaimWithSnak( 2, 'bar' ),
		);

		$claimsObject = new Claims( $claimArray );

		$list = new ClaimList( $claimsObject );

		$this->assertEquals(
			$claimArray,
			array_values( $list->toArray() )
		);
	}

	public function testGivenNonTraversable_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new ClaimList( null );
	}

}
