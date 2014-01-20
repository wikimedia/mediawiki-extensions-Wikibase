<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpClaimRemove;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\ItemContent;

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

		$item = $this->provideNewItemWithClaim( 'q345', $snak );
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

	protected function provideNewItemWithClaim( $itemId, $snak ) {
		$entity = ItemContent::newFromArray( array( 'entity' => $itemId ) )->getEntity();
		$claim =$entity->newClaim( $snak );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}

}