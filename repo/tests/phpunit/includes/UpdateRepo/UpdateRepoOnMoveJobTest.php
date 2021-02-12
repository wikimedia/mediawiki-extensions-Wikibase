<?php

namespace Wikibase\Repo\Tests\UpdateRepo;

use HashSiteStore;
use MediaWikiIntegrationTestCase;
use NullStatsdDataFactory;
use Psr\Log\NullLogger;
use Site;
use SiteLookup;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\UpdateRepo\UpdateRepoOnMoveJob;

/**
 * @covers \Wikibase\Repo\UpdateRepo\UpdateRepoOnMoveJob
 * @covers \Wikibase\Repo\UpdateRepo\UpdateRepoJob
 *
 * @group Wikibase
 * @group WikibaseIntegration
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveJobTest extends MediaWikiIntegrationTestCase {

	public function testGetSummary() {
		$job = new UpdateRepoOnMoveJob(
			Title::newMainPage(),
			[
				'siteId' => 'SiteID',
				'oldTitle' => 'Test',
				'newTitle' => 'MoarTest',
			]
		);

		$summary = $job->getSummary();

		$this->assertEquals( 'clientsitelink-update', $summary->getMessageKey() );
		$this->assertEquals( 'SiteID', $summary->getLanguageCode() );
		$this->assertEquals(
			[ 'SiteID:Test', 'SiteID:MoarTest' ],
			$summary->getCommentArgs()
		);
	}

	/**
	 * @param string $normalizedPageName
	 *
	 * @return SiteLookup
	 */
	private function getSiteLookup( $normalizedPageName ) {
		$enwiki = $this->createMock( Site::class );
		$enwiki->method( 'getGlobalId' )
			->willReturn( 'enwiki' );
		$enwiki->method( 'normalizePageName' )
			->willReturn( $normalizedPageName );

		return new HashSiteStore( [ $enwiki ] );
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return EntityTitleStoreLookup
	 */
	private function getEntityTitleLookup( ItemId $itemId ) {
		$entityTitleLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleLookup->method( 'getTitleForId' )
			->with( $itemId )
			->willReturn( Title::newFromText( $itemId->getSerialization() ) );

		return $entityTitleLookup;
	}

	/**
	 * @return EntityPermissionChecker
	 */
	private function getEntityPermissionChecker() {
		$entityPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$entityPermissionChecker->method( 'getPermissionStatusForEntity' )
			->willReturn( Status::newGood() );

		return $entityPermissionChecker;
	}

	/**
	 * @return SummaryFormatter
	 */
	private function getSummaryFormatter() {
		return $this->createMock( SummaryFormatter::class );
	}

	/**
	 * @return EditFilterHookRunner
	 */
	private function getMockEditFitlerHookRunner() {
		$runner = $this->getMockBuilder( EditFilterHookRunner::class )
			->setMethods( [ 'run' ] )
			->disableOriginalConstructor()
			->getMock();
		$runner->method( 'run' )
			->willReturn( Status::newGood() );
		return $runner;
	}

	public function runProvider() {
		return [
			[ 'New page name', 'New page name', 'Old page name' ],
			// Client normalization gets applied
			[ 'Even newer page name', 'Even newer page name', 'Old page name' ],
			// The title in the repo item is not the one we expect, so don't change it
			[ 'Old page name', 'New page name', 'Something else' ],
		];
	}

	/**
	 * @param $params
	 * @param EntityLookup $entityLookup
	 * @param EntityStore $entityStore
	 * @param SummaryFormatter $summaryFormatter
	 * @param string $normalizedPageName
	 * @param Item $titleItem
	 * @param EntityRevisionLookup|null $editEntityLookup
	 * @param EntityStore|null $editEntityStore
	 *
	 * @return UpdateRepoOnMoveJob
	 */
	private function getJob( $params, $entityLookup, $entityStore, $summaryFormatter,
		$normalizedPageName, $titleItem, $editEntityLookup = null, $editEntityStore = null ) {

		if ( !isset( $editEntityLookup ) ) {
			$editEntityLookup = $entityLookup;
		}

		if ( !isset( $editEntityStore ) ) {
			$editEntityStore = $entityStore;
		}

		$job = new UpdateRepoOnMoveJob( Title::newMainPage(), $params );
		$job->initServices(
			$entityLookup,
			$entityStore,
			$summaryFormatter,
			new NullLogger(),
			$this->getSiteLookup( $normalizedPageName ),
			new MediawikiEditEntityFactory(
				$this->getEntityTitleLookup( $titleItem->getId() ),
				$editEntityLookup,
				$editEntityStore,
				$this->getEntityPermissionChecker(),
				new EntityDiffer(),
				new EntityPatcher(),
				$this->getMockEditFitlerHookRunner(),
				new NullStatsdDataFactory(),
				PHP_INT_MAX
			)
		);
		return $job;
	}

	/**
	 * @dataProvider runProvider
	 * @param string $expected
	 * @param string $normalizedPageName
	 * @param string $oldTitle
	 */
	public function testRun( $expected, $normalizedPageName, $oldTitle ) {
		$user = User::newFromName( 'UpdateRepo' );

		// Needed as UpdateRepoOnMoveJob instantiates a User object
		$user->addToDatabase();

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Old page name', [ new ItemId( 'Q42' ) ] );

		$mockRepository = new MockRepository();

		$mockRepository->saveEntity( $item, 'UpdateRepoOnDeleteJobTest', $user, EDIT_NEW );

		$params = [
			'siteId' => 'enwiki',
			'entityId' => $item->getId()->getSerialization(),
			'oldTitle' => $oldTitle,
			'newTitle' => 'New page name',
			'user' => $user->getName()
		];

		$job = $this->getJob( $params, $mockRepository, $mockRepository, $this->getSummaryFormatter(), $normalizedPageName, $item );
		$job->run();

		/** @var Item $item */
		$item = $mockRepository->getEntity( $item->getId() );

		$this->assertSame(
			$expected,
			$item->getSiteLinkList()->getBySiteId( 'enwiki' )->getPageName(),
			'Title linked on enwiki after the job ran'
		);

		$this->assertEquals(
			$item->getSiteLinkList()->getBySiteId( 'enwiki' )->getBadges(),
			[ new ItemId( 'Q42' ) ]
		);
	}

	/** @var MockObject|EntityLookup */
	private $entityLookup;

	/** @var MockObject|EntityLookup */
	private $redirectLookup;

	/**
	 * Creates two items and mocks the UnresolvedEntityRedirectException being thrown resulting in the
	 * second item being updated instead.
	 *
	 * @see https://phabricator.wikimedia.org/T251878
	 */
	public function testShouldSupportFirstLevelRedirects() {
		$expected = "New page name";
		$normalizedPageName = "New page name";
		$oldTitle = "Old page name";

		$user = User::newFromName( 'UpdateRepo' );

		// Needed as UpdateRepoOnMoveJob instantiates a User object
		$user->addToDatabase();

		$item = new Item();

		$redirectedItem = new Item();
		$redirectedItem->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Old page name', [ new ItemId( 'Q42' ) ] );

		$mockRepository = new MockRepository();
		$this->entityLookup = $this->createMock( EntityLookup::class );
		$this->redirectLookup = new RedirectResolvingEntityLookup( $this->entityLookup );

		$unresolvedRedirectionException = new UnresolvedEntityRedirectException( new ItemId( 'Q123' ), new ItemId( 'Q42' ) );
		$this->entityLookup->method( 'getEntity' )
			->will( $this->onConsecutiveCalls(
				$this->throwException( $unresolvedRedirectionException ),
				$redirectedItem
			) );

		$mockRepository->saveEntity( $item, 'UpdateRepoOnDeleteJobTest', $user, EDIT_NEW );
		$mockRepository->saveEntity( $redirectedItem, 'UpdateRepoOnDeleteJobTest', $user, EDIT_NEW );

		$params = [
			'siteId' => 'enwiki',
			'entityId' => $item->getId()->getSerialization(),
			'oldTitle' => $oldTitle,
			'newTitle' => 'New page name',
			'user' => $user->getName()
		];

		$job = $this->getJob(
							$params,
							$this->redirectLookup,
							$mockRepository,
							$this->getSummaryFormatter(),
							$normalizedPageName,
							$redirectedItem,
							$mockRepository,
							$mockRepository );
		$job->run();

		/** @var Item $item */
		$dbItem = $mockRepository->getEntity( $redirectedItem->getId() );

		$this->assertSame(
			$expected,
			$dbItem->getSiteLinkList()->getBySiteId( 'enwiki' )->getPageName(),
			'Title linked on enwiki after the job ran'
		);

		$this->assertEquals(
			$dbItem->getSiteLinkList()->getBySiteId( 'enwiki' )->getBadges(),
			[ new ItemId( 'Q42' ) ]
		);

		$this->assertNotEquals(
			$dbItem->getId(),
			$item->getId()
		);
	}

}
