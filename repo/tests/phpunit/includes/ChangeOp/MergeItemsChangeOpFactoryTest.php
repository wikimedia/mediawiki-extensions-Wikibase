<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\ChangeOp\MergeItemsChangeOpsFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\ChangeOp\MergeItemsChangeOpFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MergeItemsChangeOpFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return MergeItemsChangeOpsFactory
	 */
	protected function newChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );

		$toItemId = new ItemId( 'Q3' );

		$changeOpFactoryProvider =  new ChangeOpFactoryProvider(
			$mockProvider->getMockLabelDescriptionDuplicateDetector(),
			$mockProvider->getMockSitelinkCache(),
			$mockProvider->getMockGuidGenerator(),
			$mockProvider->getMockGuidValidator(),
			$mockProvider->getMockGuidParser( $toItemId ),
			$mockProvider->getMockSnakValidator()
		);

		return new MergeItemsChangeOpsFactory(
			$mockProvider->getMockLabelDescriptionDuplicateDetector(),
			$mockProvider->getMockSitelinkCache(),
			$changeOpFactoryProvider
		);
	}

	public function testNewMergeOps() {
		$fromItem = Item::newEmpty();
		$toItem = Item::newEmpty();

		$op = $this->newChangeOpFactory()->newMergeOps( $fromItem, $toItem );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOpsMergeItems', $op );
	}

}
