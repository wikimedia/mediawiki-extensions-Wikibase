<?php

namespace Wikibase\Test;

use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\ChangeOpStatementRank;
use Wikibase\Entity;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\ClaimGuidGenerator;
use InvalidArgumentException;
use Wikibase\Statement;

/**
 * @covers Wikibase\ChangeOpStatementRank
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
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
		$validIdFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
		$validClaimGuid = $guidGenerator->newGuid();
		$validRank = 1;

		$args = array();
		$args[] = array( 123, $validRank, $validIdFormatter );
		$args[] = array( $validClaimGuid, ':-)', $validIdFormatter );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $rank, $idFormatter ) {
		$ChangeOpStatementRank = new ChangeOpStatementRank( $claimGuid, $rank, $idFormatter );
	}

	public function changeOpProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$rank = 1;

		$changeOp = new ChangeOpStatementRank( $claimGuid, $rank, $idFormatter );

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
		$claims = new Claims( $item->getClaims() );
		$rank = $claims[0]->getRank();
		$this->assertEquals( $rank, $expectedRank, "No reference with expected hash" );
	}

	protected function provideNewItemWithClaim( $itemId, $snak ) {
		$entity = ItemContent::newFromArray( array( 'entity' => $itemId ) )->getEntity();
		$claim = new Statement( $snak );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8499CDA-25E4-4334-AG03-A3290BCD9CQP' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}
}
