<?php

namespace Wikibase\Repo\Tests\Merge;

use HashSiteStore;
use TestSites;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpsMerge;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\Merge\MergeFactory;
use Wikibase\Repo\Merge\StatementsMerger;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Repo\Validators\EntityConstraintProvider;

/**
 * @covers \Wikibase\Repo\Merge\MergeFactory
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MergeFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return MergeFactory
	 */
	protected function newMergeFactory() {
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
			$mockProvider->getMockSnakNormalizer(),
			$mockProvider->getMockReferenceNormalizer(),
			$mockProvider->getMockStatementNormalizer(),
			[],
			true
		);

		return new MergeFactory(
			$constraintProvider,
			$changeOpFactoryProvider,
			$siteStore
		);
	}

	public function testNewMergeOps() {
		$fromItem = new Item();
		$toItem = new Item();

		$op = $this->newMergeFactory()->newMergeOps( $fromItem, $toItem );
		$this->assertInstanceOf( ChangeOpsMerge::class, $op );
	}

	public function testGetStatementsMergerYieldsStatementsMerger() {
		$factory = $this->newMergeFactory();
		$statementsMerger = $factory->getStatementsMerger();

		$this->assertInstanceOf( StatementsMerger::class, $statementsMerger );
	}

	public function testGetStatementsMergerPassesStatementChangeOpFactory() {
		$changeOpFactoryProvider = $this->createMock( ChangeOpFactoryProvider::class );
		$changeOpFactoryProvider->expects( $this->once() )
			->method( 'getStatementChangeOpFactory' )
			->willReturn( $this->createMock( StatementChangeOpFactory::class ) );

		$factory = new MergeFactory(
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
