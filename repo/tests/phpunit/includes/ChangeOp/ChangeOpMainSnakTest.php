<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpMainSnak;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\ItemContent;
use Wikibase\Lib\ClaimGuidGenerator;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOp\ChangeOpMainSnak
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 * @group ChangeOpMainSnak
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpMainSnakTest extends \PHPUnit_Framework_TestCase {

	public function invalidArgumentProvider() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$validGuidGenerator = new ClaimGuidGenerator( $item->getId() );
		$guidGenerator = new ClaimGuidGenerator( $item->getId() );
		$validClaimGuid = $guidGenerator->newGuid();
		$validSnak = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );

		$args = array();
		$args[] = array( 123, $validSnak, $validGuidGenerator );
		$args[] = array( 123, null, $validGuidGenerator );
		$args[] = array( $validClaimGuid, 'notASnak', $validGuidGenerator );
		$args[] = array( '', 'notASnak', $validGuidGenerator );
		$args[] = array( '', null, $validGuidGenerator );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $snak, $guidGenerator ) {
		new ChangeOpMainSnak( $claimGuid, $snak, $guidGenerator );
	}

	public function provideChangeOps() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		// add a new claim
		$item = $this->makeNewItemWithClaim( 'q123', $snak );
		$newSnak = new PropertyValueSnak( 78462378, new StringValue( 'newSnak' ) );
		$claimGuid = '';
		$changeOp = new ChangeOpMainSnak( $claimGuid, $newSnak, new ClaimGuidGenerator( $item->getId() ) );
		$expected = $newSnak->getDataValue();
		$args['add new claim'] = array ( $item, $changeOp, $expected );

		// update an existing claim with a new main snak value
		$item = $this->makeNewItemWithClaim( 'q234', $snak );
		$newSnak = new PropertyValueSnak( 2754236, new StringValue( 'changedSnak' ) );
		$claims = $item->getClaims();
		$claim = reset( $claims );

		$claimGuid = $claim->getGuid();
		$changeOp = new ChangeOpMainSnak( $claimGuid, $newSnak, new ClaimGuidGenerator( $item->getId() ) );
		$expected = $newSnak->getDataValue();
		$args['update claim by guid'] = array ( $item, $changeOp, $expected );

		return $args;
	}

	/**
	 * @dataProvider provideChangeOps
	 *
	 * @param Entity $item
	 * @param ChangeOpMainSnak $changeOp
	 * @param DataValue|null $expected
	 */
	public function testApply( Entity $item, $changeOp, $expected ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$this->assertNotEmpty( $changeOp->getClaimGuid() );
		$claims = new Claims( $item->getClaims() );
		if ( $expected === null ) {
			$this->assertEquals( $expected, $claims->getClaimWithGuid( $changeOp->getClaimGuid() ) );
		} else {
			$this->assertEquals( $expected, $claims->getClaimWithGuid( $changeOp->getClaimGuid() )->getMainSnak()->getDataValue() );
		}
	}

	public function provideInvalidApply() {
		$snak = new PropertyValueSnak( 67573284, new StringValue( 'test' ) );
		$newSnak = new PropertyValueSnak( 12651236, new StringValue( 'newww' ) );
		$item = $this->makeNewItemWithClaim( 'q777', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$guidGenerator = new ClaimGuidGenerator( $item->getId() );

		// apply change to the wrong item
		$wrongItem = Item::newEmpty();
		$wrongItem->setId( new ItemId( "Q888" ) );
		$args['wrong entity'] = array ( $wrongItem, new ChangeOpMainSnak( $claimGuid, $newSnak, $guidGenerator ) );

		// apply change to an unknown claim
		$wrongClaimId = $item->getId()->getPrefixedId() . '$DEADBEEF-DEAD-BEEF-DEAD-BEEFDEADBEEF';
		$args['unknown claim'] = array ( $item, new ChangeOpMainSnak( $wrongClaimId, $newSnak, $guidGenerator ) );

		// update an existing claim with wrong main snak property
		$newSnak = new PropertyValueSnak( 78462378, new StringValue( 'changedSnak' ) );
		$claims = $item->getClaims();
		$claim = reset( $claims );

		$claimGuid = $claim->getGuid();
		$changeOp = new ChangeOpMainSnak( $claimGuid, $newSnak, $guidGenerator );
		$args['wrong main snak property'] = array ( $item, $changeOp );

		return $args;
	}

	/**
	 * @dataProvider provideInvalidApply
	 */
	public function testInvalidApply( Entity $item, ChangeOp $changeOp ) {
		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );

		$changeOp->apply( $item );
	}

	protected function makeNewItemWithClaim( $itemId, $snak ) {
		$entity = Item::newFromArray( array( 'entity' => $itemId ) );
		$claim = $entity->newClaim( $snak );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}
}
