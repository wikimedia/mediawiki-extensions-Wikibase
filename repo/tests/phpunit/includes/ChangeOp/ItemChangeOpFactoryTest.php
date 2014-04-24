<?php

namespace Wikibase\Test;
use Wikibase\ChangeOp\ItemChangeOpFactory;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;

/**
 * @covers Wikibase\ChangeOp\ItemChangeOpFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ItemChangeOpFactoryTest extends ChangeOpFactoryTest {

	/**
	 * @return ItemChangeOpFactory
	 */
	protected function newChangeOpFactory() {
		$entityId = new ItemId( 'Q2' );

		return new ItemChangeOpFactory(
			$this->mockProvider->getMockLabelDescriptionDuplicateDetector(),
			$this->mockProvider->getMockSitelinkCache(),
			$this->mockProvider->getMockGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $entityId ),
			$this->mockProvider->getMockSnakValidator()
		);
	}

	public function testNewSetSiteLinkOp() {
		$op = $this->newChangeOpFactory()->newSetSiteLinkOp( 'enwiki', 'Foo' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewRemoveSiteLinkOp() {
		$op = $this->newChangeOpFactory()->newRemoveSiteLinkOp( 'enwiki', 'Foo' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewSetReferenceOp() {
		$reference = new Reference();

		$op = $this->newChangeOpFactory()->newSetReferenceOp( 'DEADBEEF', $reference, '1337BABE' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewRemoveReferenceOp() {
		$op = $this->newChangeOpFactory()->newRemoveReferenceOp( 'DEADBEEF', '1337BABE' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}


	public function testNewSetStatementRankOp() {
		$op = $this->newChangeOpFactory()->newSetStatementRankOp( 'DEADBEEF', Statement::RANK_NORMAL );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewMergeOps() {
		$fromItem = Item::newEmpty();
		$toItem = Item::newEmpty();

		$op = $this->newChangeOpFactory()->newMergeOps( $fromItem, $toItem );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOpsMerge', $op );
	}

}
