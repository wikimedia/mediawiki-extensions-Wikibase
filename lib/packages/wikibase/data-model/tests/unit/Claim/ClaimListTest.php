<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimList;
use Wikibase\DataModel\Claim\ClaimListDiffer;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Claim\ClaimListDiffer
 * @uses Wikibase\DataModel\Claim\Claims
 * @uses Wikibase\DataModel\Claim\Claim
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
		return array_map(
			function( $number ) {
				return PropertyId::newFromNumber( $number );
			},
			func_get_args()
		);
	}

	private function getStubClaim( $propertyId, $guid, $rank = Claim::RANK_NORMAL ) {
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
			->will( $this->returnValue( $rank ) );

		return $claim;
	}

	public function testCanIterate() {
		$claim = $this->getStubClaim( 1, 'kittens' );
		$list = new ClaimList( array( $claim ) );

		foreach ( $list as $claimFormList ) {
			$this->assertEquals( $claim, $claimFormList );
		}
	}

	public function testGetBestClaims() {
		$list = new ClaimList( array(
			$this->getStubClaim( 1, 'one', Claim::RANK_PREFERRED ),
			$this->getStubClaim( 1, 'two', Claim::RANK_NORMAL ),
			$this->getStubClaim( 1, 'three', Claim::RANK_PREFERRED ),

			$this->getStubClaim( 2, 'four', Claim::RANK_DEPRECATED ),

			$this->getStubClaim( 3, 'five', Claim::RANK_DEPRECATED ),
			$this->getStubClaim( 3, 'six', Claim::RANK_NORMAL ),

			$this->getStubClaim( 4, 'seven', Claim::RANK_PREFERRED ),
			$this->getStubClaim( 4, 'eight', Claim::RANK_TRUTH ),
		) );

		$this->assertEquals(
			new ClaimList( array(
				$this->getStubClaim( 1, 'one', Claim::RANK_PREFERRED ),
				$this->getStubClaim( 1, 'three', Claim::RANK_PREFERRED ),

				$this->getStubClaim( 3, 'six', Claim::RANK_NORMAL ),

				$this->getStubClaim( 4, 'eight', Claim::RANK_TRUTH ),
			) ),
			$list->getBestClaims()
		);
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
			new ClaimList( array(
				$this->getClaimWithSnak( 1, 'foo' ),
				$this->getClaimWithSnak( 2, 'foo' ),
				$this->getClaimWithSnak( 2, 'bar' ),
				$this->getClaimWithSnak( 1, 'bar' ),
			) ),
			$list->getWithUniqueMainSnaks()
		);
	}

	private function getClaimWithSnak( $propertyId, $stringValue ) {
		$snak = new PropertyValueSnak( $propertyId, new StringValue( $stringValue ) );
		$claim = new Claim( $snak );
		$claim->setGuid( sha1( $snak->getHash() ) );
		return $claim;
	}

}
