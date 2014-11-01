<?php

namespace Wikibase\Repo\Tests\UpdateRepo;

use Title;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Store\SQL\EntityPerPageTable;
use Wikibase\Repo\Store\WikiPageEntityStore;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob;

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
		$job = new UpdateRepoOnDeleteJob( Title::newMainPage() );

		$summary = $job->getSummary( 'SiteID', 'Test' );

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
	 * Simple generic integration test testing the whole class.
	 */
	public function testJob() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$enwiki = $this->getMock( 'Site' );
		$enwiki->expects( $this->once() )
			->method( 'normalizePageName' )
			->with( 'Delete me' )
			->will( $this->returnValue( false ) );

		$siteStore = $this->getMock( 'SiteStore' );
		$siteStore->expects( $this->once() )
			->method( 'getSite' )
			->with( 'enwiki' )
			->will( $this->returnValue( $enwiki ) );

		$user = User::newFromName( 'UpdateRepoOnDeleteJobTest' );
		$user->addToDatabase();

		$item = Item::newEmpty();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Delete me', array( new ItemId( 'Q42' ) ) );

		$store = new WikiPageEntityStore(
			$wikibaseRepo->getEntityContentFactory(),
			$wikibaseRepo->getStore()->newIdGenerator(),
			new EntityPerPageTable( new BasicEntityIdParser() )
		);

		$store->saveEntity( $item, 'UpdateRepoOnDeleteJobTest', $user, EDIT_NEW );

		$params = array(
			'siteId' => 'enwiki',
			'entityId' => $item->getId()->getSerialization(),
			'title' => 'Delete me',
			'user' => $user->getName()
		);

		$job = new UpdateRepoOnDeleteJob( Title::newMainPage(), $params );
		$job->initServices(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityStore(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityPermissionChecker(),
			$siteStore
		);

		$job->run();

		$item = $wikibaseRepo->getEntityLookup()->getEntity( $item->getId() );

		$this->assertFalse(
			$item->getSiteLinkList()->hasLinkWithSiteId( 'enwiki' ),
			'Sitelink has been removed.'
		);
	}
}
