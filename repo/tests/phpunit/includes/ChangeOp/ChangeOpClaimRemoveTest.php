<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpClaimRemove;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\ChangeOp\ChangeOpClaimRemove
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpClaimRemoveTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$args = array();
		$args[] = array( array( 'foo' ) );
		$args[] = array( '' );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid ) {
		new ChangeOpClaimRemove( $claimGuid );
	}

	public function testGetClaimGuid() {
		$claimguid = 'foobar';
		$changeop = new ChangeOpClaimRemove( $claimguid );
		$this->assertEquals( $claimguid, $changeop->getClaimGuid() );
	}

	public function changeOpProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( 'q345', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$changeOp = new ChangeOpClaimRemove( $claimGuid );
		$expected = null;
		$args[] = array ( $item, $changeOp, $expected );

		return $args;
	}

	/**
	 * @dataProvider changeOpProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpClaimRemove $changeOp
	 * @param DataValue|null $expected
	 */
	public function testApplyAddNewClaim( $item, $changeOp, $expected ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$this->assertNotEmpty( $changeOp->getClaimGuid() );
		$claims = new Claims( $item->getClaims() );
		$this->assertEquals( $expected, $claims->getClaimWithGuid( $changeOp->getClaimGuid() ) );
	}

	private function newItemWithClaim( $itemIdString, $snak ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $itemIdString ) );

		$claim = $item->newClaim( $snak );
		$claim->setGuid( $item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$item->setClaims( $claims );

		return $item;
	}

}
