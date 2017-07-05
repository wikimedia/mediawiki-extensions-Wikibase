<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use HashSiteStore;
use PHPUnit_Framework_TestCase;
use TestSites;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpsMerge;
use Wikibase\Repo\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\Validators\EntityConstraintProvider;

/**
 * @covers Wikibase\Repo\ChangeOp\MergeChangeOpsFactory
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MergeChangeOpsFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return MergeChangeOpsFactory
	 */
	protected function newChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );

		$toItemId = new ItemId( 'Q3' );

		$constraintProvider = $this->getMockBuilder( EntityConstraintProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$siteStore = new HashSiteStore( TestSites::getSites() );

		$changeOpFactoryProvider = new ChangeOpFactoryProvider(
			$constraintProvider,
			new GuidGenerator(),
			$mockProvider->getMockGuidValidator(),
			$mockProvider->getMockGuidParser( $toItemId ),
			$mockProvider->getMockSnakValidator(),
			$mockProvider->getMockTermValidatorFactory(),
			$siteStore,
			[]
		);

		return new MergeChangeOpsFactory(
			$constraintProvider,
			$changeOpFactoryProvider,
			$siteStore
		);
	}

	public function testNewMergeOps() {
		$fromItem = new Item();
		$toItem = new Item();

		$op = $this->newChangeOpFactory()->newMergeOps( $fromItem, $toItem );
		$this->assertInstanceOf( ChangeOpsMerge::class, $op );
	}

}
