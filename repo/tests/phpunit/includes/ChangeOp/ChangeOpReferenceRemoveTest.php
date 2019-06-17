<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpReferenceRemove;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpReferenceRemove
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ChangeOpReferenceRemoveTest extends \PHPUnit\Framework\TestCase {

	public function invalidConstructorProvider() {
		$args = [];
		$args[] = [ '', '' ];
		$args[] = [ '', 'foo' ];
		$args[] = [ 'foo', '' ];
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $guid, $referenceHash ) {
		new ChangeOpReferenceRemove( $guid, $referenceHash );
	}

	public function changeOpRemoveProvider() {
		$mainSnak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$referenceSnak = new PropertyValueSnak( 78462378, new StringValue( 'newReference' ) );
		$reference = new Reference( [ $referenceSnak ] );

		$item = $this->newItemWithClaim( 'q345', $mainSnak );
		$statement = $item->getStatements()->toArray();
		$statement->getReferences()->addReference( $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $reference->getHash() );

		return [ $item, $changeOp, $reference];
	}
	public function testApplyRemovesReference() {
		$mainSnak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$referenceSnak = new PropertyValueSnak( 78462378, new StringValue( 'newReference' ) );
		$reference = new Reference( [ $referenceSnak ] );

		$item = $this->newItemWithClaim( 'q345', $mainSnak );
		$statement = $item->getStatements()->toArray()[0];
		$statement->getReferences()->addReference( $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $reference->getHash() );

		$changeOp->apply( $item );

		$newStatement = $item->getStatements()->toArray()[0];
		$this->assertTrue( $newStatement->getReferences()->isEmpty() );
	}

	public function testApplyWithDuplicateReferencePreservesOne() {
		$mainSnak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$referenceSnak = new PropertyValueSnak( 78462378, new StringValue( 'newReference' ) );
		$reference = new Reference( [ $referenceSnak ] );
		$referenceHash = $reference->getHash();

		$item = $this->newItemWithClaim( 'q345', $mainSnak );
		$statement = $item->getStatements()->toArray()[0];
		$statement->getReferences()->addReference( clone $reference );
		$statement->getReferences()->addReference( clone $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $referenceHash );

		$changeOp->apply( $item );

		$newStatement = $item->getStatements()->toArray()[0];
		$this->assertTrue( $newStatement->getReferences()->hasReferenceHash( $referenceHash ) );
		$this->assertCount( 1, $newStatement->getReferences() );
	}

	private function newItemWithClaim( $itemIdString, Snak $snak ) {
		$item = new Item( new ItemId( $itemIdString ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$item->getId()->getSerialization() . '$D8494TYA-25E4-4334-AG03-A3290BCT9CQP'
		);

		return $item;
	}


	public function testGetState_beforeApply_returnsNotApplied() {
		$changeOpReferenceRemove = new ChangeOpReferenceRemove( 'GUID', 'referenceHash' );

		$this->assertSame( ChangeOp::STATE_NOT_APPLIED, $changeOpReferenceRemove->getState() );
	}

	public function changeOpAndStatesProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );

		$item = $this->newItemWithClaim( 'q345', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$referenceSnak = new PropertyValueSnak( 78462378, new StringValue( 'newReference' ) );
		$reference = new Reference( [ $referenceSnak ] );
		$statement->getReferences()->addReference($reference);

		$changeOpReferenceRemove = new ChangeOpReferenceRemove( $guid, $reference->getHash() );

		return [
			[ // #1 - removing existing reference
				$item,
				$changeOpReferenceRemove,
				ChangeOp::STATE_DOCUMENT_CHANGED
			]
		];
	}

	/**
	 * @dataProvider changeOpAndStatesProvider
	 */
	public function testGetState_afterApply( $entity, $changeOpReferenceRemove, $expectedState ) {
		$changeOpReferenceRemove->apply(
			$entity,
			$this->prophesize( Summary::class )->reveal()
		);

		$this->assertSame( $expectedState, $changeOpReferenceRemove->getState() );
	}

	public function testGetActions() {
		$changeOp = new ChangeOpReferenceRemove( 'guid', 'refhash' );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
