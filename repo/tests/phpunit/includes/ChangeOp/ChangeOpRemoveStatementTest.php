<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpRemoveStatement;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpRemoveStatement
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ChangeOpRemoveStatementTest extends \PHPUnit\Framework\TestCase {

	public function invalidConstructorProvider() {
		$args = [];
		$args[] = [ [ 'foo' ] ];
		$args[] = [ '' ];
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $guid ) {
		new ChangeOpRemoveStatement( $guid );
	}

	public function testGetClaimGuid() {
		$guid = 'foobar';
		$changeop = new ChangeOpRemoveStatement( $guid );
		$this->assertEquals( $guid, $changeop->getGuid() );
	}

	public function changeOpProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = [];

		$item = $this->newItemWithClaim( 'q345', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$changeOp = new ChangeOpRemoveStatement( $guid );
		$expected = null;
		$args[] = [ $item, $changeOp, $expected ];

		return $args;
	}

	/**
	 * @dataProvider changeOpProvider
	 */
	public function testApplyAddNewClaim( Item $item, ChangeOpRemoveStatement $changeOp, DataValue $expected = null ) {
		$changeOp->apply( $item );
		$this->assertNotEmpty( $changeOp->getGuid() );
		$statements = $item->getStatements();
		$this->assertEquals( $expected, $statements->getFirstStatementWithGuid( $changeOp->getGuid() ) );
	}


	public function testGetState_beforeApply_returnsNotApplied() {
		$changeOpRemoveStatement = new ChangeOpRemoveStatement( 'GUID' );

		$this->assertSame( ChangeOp::STATE_NOT_APPLIED, $changeOpRemoveStatement->getState() );
	}

	public function changeOpAndStatesProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$item = $this->newItemWithClaim('q234', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );

		$changeOpRemoveStatement = new ChangeOpRemoveStatement($statement->getGuid() );

		return [
			[ // #1 - removing existing statement
				$item,
				$changeOpRemoveStatement,
				ChangeOp::STATE_DOCUMENT_CHANGED
			]
		];
	}

	/**
	 * @dataProvider changeOpAndStatesProvider
	 */
	public function testGetState_afterApply( $entity, $changeOpRemoveStatement, $expectedState ) {
		$changeOpRemoveStatement->apply(
			$entity,
			$this->prophesize( Summary::class )->reveal()
		);

		$this->assertSame( $expectedState, $changeOpRemoveStatement->getState() );
	}

	private function newItemWithClaim( $itemIdString, $snak ) {
		$item = new Item( new ItemId( $itemIdString ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P'
		);

		return $item;
	}

	public function testGetActions() {
		$changeOp = new ChangeOpRemoveStatement( 'guid' );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
