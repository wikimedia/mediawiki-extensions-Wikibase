<?php

namespace Wikibase\Test;

use Title;
use Wikibase\Client\ClientSiteLinkLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;

/**
 * @covers Wikibase\Client\ClientSiteLinkLookup
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ClientSiteLinkLookupTest extends \PHPUnit_Framework_TestCase {

	private function newSiteLinkList() {
		$list = new SiteLinkList();

		$list->addNewSiteLink( 'dewiki', 'Foo de', array( new ItemId( 'Q3' ) ) );
		$list->addNewSiteLink( 'enwiki', 'Foo en', array( new ItemId( 'Q3' ), new ItemId( 'Q123' ) ) );
		$list->addNewSiteLink( 'srwiki', 'Foo sr' );
		$list->addNewSiteLink( 'nlwiki', 'ThisIsANamespace:Foo nl' );
		$list->addNewSiteLink( 'dewiktionary', 'Foo de word' );
		$list->addNewSiteLink( 'enwiktionary', 'Foo en word' );

		return $list;
	}

	private function newItem() {
		$item = Item::newEmpty();

		$item->setId( 1 );
		$item->setLabel( 'en', 'Foo' );
		$item->setSiteLinkList( $this->newSiteLinkList() );

		return $item;
	}

	/**
	 * @param string $localSiteId
	 *
	 * @return ClientSiteLinkLookup
	 */
	private function getClientSiteLinkLookup( $localSiteId ) {
		$item = $this->newItem();

		$mockRepo = new MockRepository();
		$mockRepo->putEntity( $item );

		return new ClientSiteLinkLookup(
			$localSiteId,
			$mockRepo,
			$mockRepo
		);
	}

	/**
	 * @dataProvider provideGetSiteLinks
	 */
	public function testGetSiteLinks( SiteLinkList $expectedLinks, $localSiteId, Title $title, $message ) {
		$clientSiteLinkLookup = $this->getClientSiteLinkLookup( $localSiteId );

		$this->assertEquals(
			$expectedLinks,
			new SiteLinkList( $clientSiteLinkLookup->getSiteLinks( $title ) ),
			$message
		);
	}

	public function provideGetSiteLinks() {
		$siteLinks = $this->newSiteLinkList();

		return array(
			array( $siteLinks, 'dewiki', Title::newFromText( 'Foo de' ), 'from dewiki title' ),
			array( $siteLinks, 'enwiktionary', Title::newFromText( 'Foo en word' ), 'from enwiktionary title' ),
			array( $siteLinks, 'nlwiki', Title::newFromText( 'ThisIsANamespace:Foo nl' ), 'from nlwiki non-main namespace title' ),
			array( new SiteLinkList(), 'enwiki', Title::newFromText( 'Bar en' ), 'from nonexisting title' ),
			array( new SiteLinkList(), 'barwiki', Title::newFromText( 'Foo bar' ), 'from nonexisting site' ),
		);
	}

	/**
	 * @dataProvider provideGetSiteLink
	 */
	public function testGetSiteLink( $expected, $localSiteId, Title $title, $site, $message ) {
		$clientSiteLinkLookup = $this->getClientSiteLinkLookup( $localSiteId );

		$this->assertEquals(
			$expected,
			$clientSiteLinkLookup->getSiteLink( $title, $site ),
			$message
		);
	}

	public function provideGetSiteLink() {
		$links = $this->newSiteLinkList();

		return array(
			array( $links->getBySiteId( 'enwiki' ), 'dewiki', Title::newFromText( 'Foo de' ), 'enwiki', 'enwiki from dewiki title' ),
			array( $links->getBySiteId( 'dewiktionary' ), 'enwiktionary', Title::newFromText( 'Foo en word' ), 'dewiktionary', 'dewiktionary from enwiktionary title' ),
			array( $links->getBySiteId( 'enwiki' ), 'nlwiki', Title::newFromText( 'ThisIsANamespace:Foo nl' ), 'enwiki', 'enwiki from nlwiki non-main namespace title' ),
			array( $links->getBySiteId( 'nlwiki' ), 'enwiki', Title::newFromText( 'Foo en' ), 'nlwiki', 'non-main namespace nlwiki from enwiki title' ),
			array( null, 'enwiki', Title::newFromText( 'Bar en' ), 'dewiki', 'from nonexisting title' ),
			array( null, 'barwiki', Title::newFromText( 'Foo bar' ), 'enwiki', 'from nonexisting site' ),
			array( null, 'dewiki', Title::newFromText( 'Foo de' ), 'frwiki', 'nonexisting site from dewiki title' ),
		);
	}

}
