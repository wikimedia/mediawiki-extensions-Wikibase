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
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob;

/**
 * @covers \Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob
 * @covers \Wikibase\Repo\UpdateRepo\UpdateRepoJob
 *
 * @group Wikibase
 * @group WikibaseIntegration
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteJobTest extends MediaWikiIntegrationTestCase {

	public function testGetSummary() {
		$job = new UpdateRepoOnDeleteJob(
			Title::newMainPage(),
			[
				'siteId' => 'SiteID',
				'title' => 'Test',
			]
		);

		$summary = $job->getSummary();

		$this->assertEquals( 'clientsitelink-remove', $summary->getMessageKey() );
		$this->assertEquals(
			[ 'SiteID' ],
			$summary->getCommentArgs()
		);

		$this->assertEquals(
			[ 'Test' ],
			$summary->getAutoSummaryArgs()
		);
	}

	private function getSiteLookup( bool $titleExists ): SiteLookup {
		$enwiki = $this->createMock( Site::class );
		$enwiki->method( 'getGlobalId' )
			->willReturn( 'enwiki' );
		$enwiki->method( 'normalizePageName' )
			->with( 'Delete me' )
			->willReturn( $titleExists );

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
			[ true, false, 'Delete me' ],
			// The title on client still exists, so don't unlink
			[ false, true, 'Delete me' ],
			// The title in the repo item is not the one we want to unlink, don't unlink
			[ false, false, 'Something changed' ],
		];
	}

	/**
	 * @dataProvider runProvider
	 */
	public function testRun( bool $expected, bool $titleExists, string $oldTitle ) {
		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Delete me', [ new ItemId( 'Q42' ) ] );

		$mockRepository = new MockRepository();

		$user = User::newFromName( 'UpdateRepo' );

		// Needed as UpdateRepoOnDeleteJob instantiates a User object
		$user->addToDatabase();

		$mockRepository->saveEntity( $item, 'UpdateRepoOnDeleteJobTest', $user, EDIT_NEW );

		$params = [
			'siteId' => 'enwiki',
			'entityId' => $item->getId()->getSerialization(),
			'title' => $oldTitle,
			'user' => $user->getName(),
		];
		$tags = [ 'tag 1', 'tag 2' ];

		$job = new UpdateRepoOnDeleteJob( Title::newMainPage(), $params );
		$job->initServices(
			$mockRepository,
			$mockRepository,
			$this->getSummaryFormatter(),
			new NullLogger(),
			$this->getSiteLookup( $titleExists ),
			new MediawikiEditEntityFactory(
				$this->getEntityTitleLookup( $item->getId() ),
				$mockRepository,
				$mockRepository,
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

		$job->run();

		$item = $mockRepository->getEntity( $item->getId() );

		$this->assertSame(
			!$expected,
			$item->getSiteLinkList()->hasLinkWithSiteId( 'enwiki' ),
			'Sitelink has been removed.'
		);

		if ( $expected ) {
			$this->assertSame(
				$tags,
				$mockRepository->getLatestLogEntryFor( $item->getId() )['tags'],
				'Edit has been tagged.'
			);
		}
	}

}
