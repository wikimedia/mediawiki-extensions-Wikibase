<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use HashSiteStore;
use PHPUnit4And6Compat;
use TestSites;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpsMerge;
use Wikibase\Repo\ChangeOp\MergeChangeOpsFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\Merge\StatementsMerger;
use Wikibase\Repo\Validators\EntityConstraintProvider;

/**
 * @covers \Wikibase\Repo\ChangeOp\MergeChangeOpsFactory
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MergeChangeOpsFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @return MergeChangeOpsFactory
	 */
	protected function newChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );

		$toItemId = new ItemId( 'Q3' );

		$constraintProvider = $this->createMock( EntityConstraintProvider::class );

		$siteStore = $this->newSiteStore();

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

	public function testGetStatementsMergerYieldsStatementsMerger() {
		$factory = $this->newChangeOpFactory();
		$statementsMerger = $factory->getStatementsMerger();

		$this->assertInstanceOf( StatementsMerger::class, $statementsMerger );
	}

	public function testGetStatementsMergerPassesStatementChangeOpFactory() {
		$changeOpFactoryProvider = $this->createMock( ChangeOpFactoryProvider::class );
		$changeOpFactoryProvider->expects( $this->once() )
			->method( 'getStatementChangeOpFactory' )
			->willReturn( $this->createMock( StatementChangeOpFactory::class ) );

		$factory = new MergeChangeOpsFactory(
			$this->createMock( EntityConstraintProvider::class ),
			$changeOpFactoryProvider,
			$this->newSiteStore()
		);

		$factory->getStatementsMerger();
	}

	private function newSiteStore() {
		return new HashSiteStore( TestSites::getSites() );
	}

}
