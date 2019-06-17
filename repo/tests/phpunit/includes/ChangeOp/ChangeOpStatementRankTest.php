<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpStatementRank;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpStatementRank
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpStatementRankTest extends \PHPUnit\Framework\TestCase {

	public function invalidArgumentProvider() {
		$item = new Item( new ItemId( 'Q42' ) );

		$guidGenerator = new GuidGenerator();
		$validGuid = $guidGenerator->newGuid( $item->getId() );
		$validRank = 1;

		$args = [];
		$args[] = [ 123, $validRank ];
		$args[] = [ $validGuid, ':-)' ];

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
		$args = [];

		$item = $this->newItemWithClaim( 'q123', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$rank = 1;

		$changeOp = new ChangeOpStatementRank( $guid, $rank );

		$args[] = [ $item, $changeOp, $rank ];

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

	public function testGetState_beforeApply_returnsNotApplied() {
		$changeOpDescription = new ChangeOpStatementRank( 'GUID', 123);

		$this->assertSame( ChangeOp::STATE_NOT_APPLIED, $changeOpDescription->getState() );
	}

	public function changeOpAndStatesProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$item = $this->newItemWithClaim( 'q123', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$rank = 1;
		$newRank = 2;
		$noChangeOpStatementRank = new ChangeOpStatementRank( $statement->getGuid(), $rank );
		$ChangeOpstatementRank = new ChangeOpStatementRank( $statement->getGuid(), $newRank );

		return [
			[ // #0 - setting already existing rank on statement
				$item,
				$noChangeOpStatementRank,
				ChangeOp::STATE_DOCUMENT_NOT_CHANGED
			],
			[ // #1 - adding a rank on a statement
				$item,
				$ChangeOpstatementRank,
				ChangeOp::STATE_DOCUMENT_CHANGED
			]
		];
	}

	/**
	 * @dataProvider changeOpAndStatesProvider
	 */
	public function testGetState_afterApply($entity, $changeOpStatementRank, $expectedState ) {
		$changeOpStatementRank->apply(
			$entity,
			$this->prophesize( Summary::class )->reveal()
		);

		$this->assertSame( $expectedState, $changeOpStatementRank->getState() );
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

	public function testGetActions() {
		$changeOp = new ChangeOpStatementRank( 'guid', 1 );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
