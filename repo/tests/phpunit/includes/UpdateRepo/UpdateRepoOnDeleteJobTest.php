<?php

namespace Wikibase\Repo\Tests\UpdateRepo;

use Title;
use User;
use Status;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseIntegration
 * @group Database
 *
 * @licence GNU GPL v2+
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

	private function getSiteStore( $titleExists ) {
		$enwiki = $this->getMock( 'Site' );
		$enwiki->expects( $this->any() )
			->method( 'normalizePageName' )
			->with( 'Delete me' )
			->will( $this->returnValue( $titleExists ) );

		$siteStore = $this->getMock( 'SiteStore' );
		$siteStore->expects( $this->any() )
			->method( 'getSite' )
			->with( 'enwiki' )
			->will( $this->returnValue( $enwiki ) );

		return $siteStore;
	}

	private function getEntityTitleLookup( ItemId $itemId ) {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->with( $itemId )
			->will( $this->returnValue( Title::newFromText( $itemId->getSerialization() ) ) );

		return $entityTitleLookup;
	}

	private function getEntityPermissionChecker() {
		$entityPermissionChecker = $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );
		$entityPermissionChecker->expects( $this->any() )
				->method( 'getPermissionStatusForEntity' )
				->will( $this->returnValue( Status::newGood() ));

		return $entityPermissionChecker;
	}

	private function getSummaryFormatter() {
		$summaryFormatter = $this->getMockBuilder( 'Wikibase\SummaryFormatter' )
				->disableOriginalConstructor()->getMock();

		return $summaryFormatter;
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
		$item = Item::newEmpty();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Delete me', array( new ItemId( 'Q42' ) ) );

		$store = new MockRepository();

		$user = User::newFromName( 'UpdateRepo' );

		// Needed as UpdateRepoOnDeleteJob instantiates a User object
		$user->addToDatabase();

		$store->saveEntity( $item, 'UpdateRepoOnDeleteJobTest', $user, EDIT_NEW );

		$params = array(
			'siteId' => 'enwiki',
			'entityId' => $item->getId()->getSerialization(),
			'title' => $oldTitle,
			'user' => $user->getName()
		);

		$job = new UpdateRepoOnDeleteJob( Title::newMainPage(), $params );
		$job->initServices(
			$this->getEntityTitleLookup( $item->getId() ),
			$store,
			$store,
			$this->getSummaryFormatter(),
			$this->getEntityPermissionChecker(),
			$this->getSiteStore( $titleExists )
		);

		$job->run();

		$item = $store->getEntity( $item->getId() );

		$this->assertSame(
			!$expected,
			$item->getSiteLinkList()->hasLinkWithSiteId( 'enwiki' ),
			'Sitelink has been removed.'
		);
	}
}
