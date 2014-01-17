<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpMainSnak;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\ItemContent;
use Wikibase\Lib\ClaimGuidGenerator;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOp\ChangeOpMainSnak
 *
 * @since 0.4
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

	public function changeOpProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$newSnak = new PropertyValueSnak( 78462378, new StringValue( 'newSnak' ) );
		$claimGuid = '';
		$changeOp = new ChangeOpMainSnak( $claimGuid, $newSnak, new ClaimGuidGenerator( $item->getId() ) );
		$expected = $newSnak->getDataValue();
		$args[] = array ( $item, $changeOp, $expected );

		$item = $this->provideNewItemWithClaim( 'q234', $snak );
		$newSnak = new PropertyValueSnak( 78462378, new StringValue( 'changedSnak' ) );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$changeOp = new ChangeOpMainSnak( $claimGuid, $newSnak, new ClaimGuidGenerator( $item->getId() ) );
		$expected = $newSnak->getDataValue();
		$args[] = array ( $item, $changeOp, $expected );

		return $args;
	}

	/**
	 * @dataProvider changeOpProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpMainSnak $changeOp
	 * @param DataValue|null $expected
	 */
	public function testApplyAddNewClaim( $item, $changeOp, $expected ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$this->assertNotEmpty( $changeOp->getClaimGuid() );
		$claims = new Claims( $item->getClaims() );
		if ( $expected === null ) {
			$this->assertEquals( $expected, $claims->getClaimWithGuid( $changeOp->getClaimGuid() ) );
		} else {
			$this->assertEquals( $expected, $claims->getClaimWithGuid( $changeOp->getClaimGuid() )->getMainSnak()->getDataValue() );
		}
	}

	public function provideChangeOps() {
		$snak = new PropertyValueSnak( 67573284, new StringValue( 'test' ) );
		$newSnak = new PropertyValueSnak( 12651236, new StringValue( 'newww' ) );
		$item = $this->provideNewItemWithClaim( 'q777', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$guidGenerator = new ClaimGuidGenerator( $item->getId() );

		$args[] = array ( new ChangeOpMainSnak( $claimGuid, $newSnak, $guidGenerator ) );

		return $args;
	}

	/**
	 * @dataProvider provideChangeOps
	 * @expectedException \Wikibase\ChangeOp\ChangeOpException
	 */
	public function testInvalidApply( ChangeOp $changeOp ) {
		$wrongItem = ItemContent::newEmpty();
		$changeOp->apply( $wrongItem->getEntity() );
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
