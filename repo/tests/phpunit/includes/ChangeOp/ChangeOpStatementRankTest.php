<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpStatementRank;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\ClaimGuidGenerator;

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
		$item = Item::newEmpty();
		$item->setId( 42 );

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

		$item = $this->newItemWithClaim( 'q123', $snak );
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

	private function newItemWithClaim( $itemIdString, $mainSnak ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $itemIdString ) );

		$item->getStatements()->addNewStatement(
			$mainSnak,
			null,
			null,
			$itemIdString . '$D8499CDA-25E4-4334-AG03-A3290BCD9CQP'
		);

		return $item;
	}

}
