<?php

namespace Wikibase\Test;

use TestSites;
use Title;
use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Store\SQL\EntityPerPageTable;
use Wikibase\Repo\Store\WikiPageEntityStore;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\UpdateRepoOnMoveJob;

/**
 * @covers Wikibase\UpdateRepoOnMoveJob
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseIntegration
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveJobTest extends \MediaWikiTestCase {

	public function testGetSummary() {
		$job = new UpdateRepoOnMoveJob( Title::newMainPage() );

		$summary = $job->getSummary( 'SiteID', 'Test', 'MoarTest' );

		$this->assertEquals( 'clientsitelink-update', $summary->getMessageKey() );
		$this->assertEquals( 'SiteID', $summary->getLanguageCode() );
		$this->assertEquals(
			array( 'SiteID:Test', 'SiteID:MoarTest' ),
			$summary->getCommentArgs()
		);
	}

	/**
	 * Simple generic integration test testing the whole class.
	 */
	public function testJob() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$wikibaseRepo->getSiteStore()->clear();
		$wikibaseRepo->getSiteStore()->saveSites( TestSites::getSites() );

		$user = User::newFromName( 'UpdateRepoOnMoveJobTest' );
		$user->addToDatabase();

		$item = Item::newEmpty();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Old page name', array( new ItemId( 'Q42' ) ) );

		$store = new WikiPageEntityStore(
			$wikibaseRepo->getEntityContentFactory(),
			$wikibaseRepo->getStore()->newIdGenerator(),
			new EntityPerPageTable( new BasicEntityIdParser() )
		);

		$store->saveEntity( $item, 'UpdateRepoOnMoveJobTest', $user, EDIT_NEW );

		$params = array(
			'siteId' => 'enwiki',
			'entityId' => $item->getId()->getSerialization(),
			'oldTitle' => 'Old page name',
			'newTitle' => 'New page name',
			'user' => $user->getName()
		);

		$job = new UpdateRepoOnMoveJob( Title::newMainPage(), $params );

		$job->run();

		$item = $wikibaseRepo->getEntityLookup()->getEntity( $item->getId() );

		$this->assertSame(
			$item->getSiteLinkList()->getBySiteId( 'enwiki' )->getPageName(),
			'New page name'
		);

		$this->assertEquals(
			$item->getSiteLinkList()->getBySiteId( 'enwiki' )->getBadges(),
			array( new ItemId( 'Q42' ) )
		);
	}
}
