<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\ChangeOpMainSnak;
use Wikibase\Entity;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\ClaimGuidGenerator;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOpMainSnak
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpMainSnakTest extends \PHPUnit_Framework_TestCase {

	public function invalidArgumentProvider() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$validIdFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$validGuidGenerator = new ClaimGuidGenerator( $item->getId() );
		$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
		$validClaimGuid = $guidGenerator->newGuid();
		$validSnak = new \Wikibase\PropertyValueSnak( 7201010, new \DataValues\StringValue( 'o_O' ) );

		$args = array();
		$args[] = array( 123, $validSnak, $validIdFormatter, $validGuidGenerator );
		$args[] = array( 123, null, $validIdFormatter, $validGuidGenerator );
		$args[] = array( $validClaimGuid, 'notASnak', $validIdFormatter, $validGuidGenerator );
		$args[] = array( '', 'notASnak', $validIdFormatter, $validGuidGenerator );
		$args[] = array( '', null, $validIdFormatter, $validGuidGenerator );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $snak, $idFormatter, $guidGenerator ) {
		$ChangeOpMainSnak = new ChangeOpMainSnak( $claimGuid, $snak, $idFormatter, $guidGenerator );
	}

	public function changeOpProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$newSnak = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newSnak' ) );
		$claimGuid = '';
		$changeOp = new ChangeOpMainSnak( $claimGuid, $newSnak, $idFormatter, new ClaimGuidGenerator( $item->getId() ) );
		$expected = $newSnak->getDataValue();
		$args[] = array ( $item, $changeOp, $expected );

		$item = $this->provideNewItemWithClaim( 'q234', $snak );
		$newSnak = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'changedSnak' ) );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$changeOp = new ChangeOpMainSnak( $claimGuid, $newSnak, $idFormatter, new ClaimGuidGenerator( $item->getId() ) );
		$expected = $newSnak->getDataValue();
		$args[] = array ( $item, $changeOp, $expected );

		$item = $this->provideNewItemWithClaim( 'q345', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$changeOp = new ChangeOpMainSnak( $claimGuid, null, $idFormatter, new ClaimGuidGenerator( $item->getId() ) );
		$expected = null;
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
		$snak = new \Wikibase\PropertyValueSnak( 67573284, new \DataValues\StringValue( 'test' ) );
		$newSnak = new \Wikibase\PropertyValueSnak( 12651236, new \DataValues\StringValue( 'newww' ) );
		$item = $this->provideNewItemWithClaim( 'q777', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$guidGenerator = new ClaimGuidGenerator( $item->getId() );

		$args[] = array ( new ChangeOpMainSnak( $claimGuid, $newSnak, $idFormatter, $guidGenerator ) );
		$args[] = array ( new ChangeOpMainSnak( $claimGuid, null, $idFormatter, $guidGenerator ) );

		return $args;
	}

	/**
	 * @dataProvider provideChangeOps
	 * @expectedException \Wikibase\ChangeOpException
	 */
	public function testInvalidApply( $changeOp ) {
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
