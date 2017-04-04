<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpStatementRank;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\ChangeOp\ChangeOpStatementRank
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpStatementRankTest extends \PHPUnit_Framework_TestCase {

	public function invalidArgumentProvider() {
		$item = new Item( new ItemId( 'Q42' ) );

		$guidGenerator = new GuidGenerator();
		$validGuid = $guidGenerator->newGuid( $item->getId() );
		$validRank = 1;

		$args = array();
		$args[] = array( 123, $validRank );
		$args[] = array( $validGuid, ':-)' );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $guid, $rank ) {
		new ChangeOpStatementRank( $guid, $rank );
	}

	public function changeOpProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( 'q123', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$rank = 1;

		$changeOp = new ChangeOpStatementRank( $guid, $rank );

		$args[] = array( $item, $changeOp, $rank );

		return $args;
	}

	/**
	 * @dataProvider changeOpProvider
	 */
	public function testApplyStatementRank( Item $item, ChangeOpStatementRank $changeOp, $expectedRank ) {
		$changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$rank = $statement->getRank();
		$this->assertEquals( $expectedRank, $rank, "No reference with expected hash" );
	}

	private function newItemWithClaim( $itemIdString, $mainSnak ) {
		$item = new Item( new ItemId( $itemIdString ) );

		$item->getStatements()->addNewStatement(
			$mainSnak,
			null,
			null,
			$itemIdString . '$D8499CDA-25E4-4334-AG03-A3290BCD9CQP'
		);

		return $item;
	}

}
