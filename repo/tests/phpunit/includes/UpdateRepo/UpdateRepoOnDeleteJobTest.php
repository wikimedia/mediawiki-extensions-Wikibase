<?php

namespace Wikibase\Repo\Tests\UpdateRepo;

use Site;
use SiteStore;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob;
use Wikibase\SummaryFormatter;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob
 * @covers Wikibase\Repo\UpdateRepo\UpdateRepoJob
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseIntegration
 * @group Database
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteJobTest extends \MediaWikiTestCase {

	public function testGetSummary() {
		$job = new UpdateRepoOnDeleteJob(
			Title::newMainPage(),
			array(
				'siteId' => 'SiteID',
				'title' => 'Test'
			)
		);

		$summary = $job->getSummary();

		$this->assertEquals( 'clientsitelink-remove', $summary->getMessageKey() );
		$this->assertEquals(
			array( 'SiteID' ),
			$summary->getCommentArgs()
		);

		$this->assertEquals(
			array( 'Test' ),
			$summary->getAutoSummaryArgs()
		);
	}

	/**
	 * @param bool $titleExists
	 *
	 * @return SiteStore
	 */
	private function getSiteStore( $titleExists ) {
		$enwiki = $this->getMock( Site::class );
		$enwiki->expects( $this->any() )
			->method( 'normalizePageName' )
			->with( 'Delete me' )
			->will( $this->returnValue( $titleExists ) );

		$siteStore = $this->getMock( SiteStore::class );
		$siteStore->expects( $this->any() )
			->method( 'getSite' )
			->with( 'enwiki' )
			->will( $this->returnValue( $enwiki ) );

		return $siteStore;
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup( ItemId $itemId ) {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->with( $itemId )
			->will( $this->returnValue( Title::newFromText( $itemId->getSerialization() ) ) );

		return $entityTitleLookup;
	}

	/**
	 * @return EntityPermissionChecker
	 */
	private function getEntityPermissionChecker() {
		$entityPermissionChecker = $this->getMock( EntityPermissionChecker::class );
		$entityPermissionChecker->expects( $this->any() )
				->method( 'getPermissionStatusForEntity' )
				->will( $this->returnValue( Status::newGood() ) );

		return $entityPermissionChecker;
	}

	/**
	 * @return SummaryFormatter
	 */
	private function getSummaryFormatter() {
		$summaryFormatter = $this->getMockBuilder( SummaryFormatter::class )
				->disableOriginalConstructor()->getMock();

		return $summaryFormatter;
	}

	/**
	 * @return EditFilterHookRunner
	 */
	private function getMockEditFitlerHookRunner() {
		$runner = $this->getMockBuilder( EditFilterHookRunner::class )
			->setMethods( array( 'run' ) )
			->disableOriginalConstructor()
			->getMock();
		$runner->expects( $this->any() )
			->method( 'run' )
			->will( $this->returnValue( Status::newGood() ) );
		return $runner;
	}

	public function runProvider() {
		return array(
			array( true, false, 'Delete me' ),
			// The title on client still exists, so don't unlink
			array( false, true, 'Delete me' ),
			// The title in the repo item is not the one we want to unlink, don't unlink
			array( false, false, 'Something changed' )
		);
	}

	/**
	 * @dataProvider runProvider
	 *
	 * @param bool $expected
	 * @param bool $titleExists
	 * @param string $oldTitle
	 */
	public function testRun( $expected, $titleExists, $oldTitle ) {
		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Delete me', array( new ItemId( 'Q42' ) ) );

		$mockRepository = new MockRepository();

		$user = User::newFromName( 'UpdateRepo' );

		// Needed as UpdateRepoOnDeleteJob instantiates a User object
		$user->addToDatabase();

		$mockRepository->saveEntity( $item, 'UpdateRepoOnDeleteJobTest', $user, EDIT_NEW );

		$params = array(
			'siteId' => 'enwiki',
			'entityId' => $item->getId()->getSerialization(),
			'title' => $oldTitle,
			'user' => $user->getName()
		);

		$job = new UpdateRepoOnDeleteJob( Title::newMainPage(), $params );
		$job->initServices(
			$mockRepository,
			$mockRepository,
			$this->getSummaryFormatter(),
			$this->getSiteStore( $titleExists ),
			new EditEntityFactory(
				$this->getEntityTitleLookup( $item->getId() ),
				$mockRepository,
				$mockRepository,
				$this->getEntityPermissionChecker(),
				new EntityDiffer(),
				new EntityPatcher(),
				$this->getMockEditFitlerHookRunner()
			)
		);

		$job->run();

		$item = $mockRepository->getEntity( $item->getId() );

		$this->assertSame(
			!$expected,
			$item->getSiteLinkList()->hasLinkWithSiteId( 'enwiki' ),
			'Sitelink has been removed.'
		);
	}

}
