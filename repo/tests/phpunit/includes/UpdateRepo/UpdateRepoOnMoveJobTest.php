<?php

declare( strict_types = 1 );

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
use Wikibase\Lib\SettingsArray;
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

	private function getSiteLookup( string $normalizedPageName ): SiteLookup {
		$enwiki = $this->createMock( Site::class );
		$enwiki->method( 'getGlobalId' )
			->willReturn( 'enwiki' );
		$enwiki->method( 'normalizePageName' )
			->willReturn( $normalizedPageName );

		return new HashSiteStore( [ $enwiki ] );
	}

	private function getEntityTitleLookup( ItemId $itemId ): EntityTitleStoreLookup {
		$entityTitleLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleLookup->method( 'getTitleForId' )
			->with( $itemId )
			->willReturn( Title::newFromTextThrow( $itemId->getSerialization() ) );

		return $entityTitleLookup;
	}

	private function getEntityPermissionChecker(): EntityPermissionChecker {
		$entityPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$entityPermissionChecker->method( 'getPermissionStatusForEntity' )
			->willReturn( Status::newGood() );

		return $entityPermissionChecker;
	}

	private function getSummaryFormatter(): SummaryFormatter {
		$summaryFormatter = $this->createMock( SummaryFormatter::class );
		$summaryFormatter->method( 'formatSummary' )->willReturn( '' );

		return $summaryFormatter;
	}

	private function getMockEditFitlerHookRunner(): EditFilterHookRunner {
		$runner = $this->getMockBuilder( EditFilterHookRunner::class )
			->onlyMethods( [ 'run' ] )
			->disableOriginalConstructor()
			->getMock();
		$runner->method( 'run' )
			->willReturn( Status::newGood() );
		return $runner;
	}

	public function runProvider(): iterable {
		return [
			[ 'New page name', 'New page name', 'Old page name' ],
			// Client normalization gets applied
			[ 'Even newer page name', 'Even newer page name', 'Old page name' ],
			// The title in the repo item is not the one we expect, so don't change it
			[ 'Old page name', 'New page name', 'Something else' ],
		];
	}

	private function getJob(
		array $params,
		EntityLookup $entityLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		string $normalizedPageName,
		Item $titleItem,
		EntityRevisionLookup $editEntityLookup = null,
		EntityStore $editEntityStore = null,
		array $tags = []
	): UpdateRepoOnMoveJob {
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
				$this->getServiceContainer()->getUserOptionsLookup(),
				PHP_INT_MAX,
				[ 'item', 'property' ]
			),
			new SettingsArray( [
				'updateRepoTags' => $tags,
			] )
		);
		return $job;
	}

	/**
	 * @dataProvider runProvider
	 */
	public function testRun( string $expected, string $normalizedPageName, string $oldTitle ) {
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
			'user' => $user->getName(),
		];
		$tags = [ 'tag 1', 'tag 2' ];

		$job = $this->getJob(
			$params,
			$mockRepository,
			$mockRepository,
			$this->getSummaryFormatter(),
			$normalizedPageName,
			$item,
			null,
			null,
			$tags
		);
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

		if ( $expected !== 'Old page name' ) {
			$this->assertSame(
				$tags,
				$mockRepository->getLatestLogEntryFor( $item->getId() )['tags'],
				'Edit tagged'
			);
		}
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
			'user' => $user->getName(),
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
