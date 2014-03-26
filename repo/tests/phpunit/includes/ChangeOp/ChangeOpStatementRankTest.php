<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\ChangeOp\ChangeOpStatementRank;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\ItemContent;
use Wikibase\Lib\ClaimGuidGenerator;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOp\ChangeOpStatementRank
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpStatementRankTest extends \PHPUnit_Framework_TestCase {

	public function invalidArgumentProvider() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$guidGenerator = new ClaimGuidGenerator();
		$validClaimGuid = $guidGenerator->newGuid( $item->getId() );
		$validRank = 1;

		$args = array();
		$args[] = array( 123, $validRank );
		$args[] = array( $validClaimGuid, ':-)' );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $rank ) {
		new ChangeOpStatementRank( $claimGuid, $rank );
	}

	public function changeOpProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$claimGuid = $claim->getGuid();
		$rank = 1;

		$changeOp = new ChangeOpStatementRank( $claimGuid, $rank );

		$args[] = array ( $item, $changeOp, $rank );

		return $args;
	}

	/**
	 * @dataProvider changeOpProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpStatementRank $changeOp
	 * @param $expectedRank
	 */
	public function testApplyStatementRank( $item, $changeOp, $expectedRank ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = $item->getClaims();
		$claim = reset( $claims );
		$rank = $claim->getRank();
		$this->assertEquals( $rank, $expectedRank, "No reference with expected hash" );
	}

	protected function provideNewItemWithClaim( $itemId, $snak ) {
		$entity = ItemContent::newFromArray( array( 'entity' => $itemId ) )->getEntity();
		$claim = $entity->newClaim( $snak );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8499CDA-25E4-4334-AG03-A3290BCD9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}
}
