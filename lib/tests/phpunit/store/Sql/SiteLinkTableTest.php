<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\BadgeStore;
use Wikibase\Lib\Store\SiteLinkTable;

/**
 * @covers Wikibase\Lib\Store\SiteLinkTable
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group SiteLink
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkTableTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local site link table." );
		}
	}

	/**
	 * @param SiteLink[] $updatedLinks
	 * @param SiteLink[] $removedLinks
	 * @param bool $clear
	 * @return BadgeStore
	 */
	private function newBadgeStore( array $updatedLinks, array $removedLinks, $clear ) {
		$badgeStore = $this->getMock( 'Wikibase\Lib\Store\BadgeStore' );

		$i = 0;
		foreach ( $removedLinks as $siteLink ) {
			$badgeStore->expects( $this->at( $i++ ) )
				->method( 'deleteBadgesOfSiteLink' )
				->with( $siteLink )
				->will( $this->returnValue( true ) );
		}

		foreach ( $updatedLinks as $siteLink ) {
			$badgeStore->expects( $this->at( $i++ ) )
				->method( 'saveBadgesOfSiteLink' )
				->with( $siteLink )
				->will( $this->returnValue( true ) );
		}

		if ( $clear ) {
			$badgeStore->expects( $this->once() )
				->method( 'clear' )
				->will( $this->returnValue( true ) );
		}

		return $badgeStore;
	}

	private function newSiteLinkTable( array $updatedLinks = array(), array $removedLinks = array(), $clear = false ) {
		return new SiteLinkTable( 'wb_items_per_site', false, $this->newBadgeStore( $updatedLinks, $removedLinks, $clear ) );
	}

	public function itemProvider() {
		$items = array();

		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( 'en', 'Beer' );

		$siteLinks = array(
			'cswiki' => 'Pivo',
			'enwiki' => 'Beer',
			'jawiki' => 'ビール'
		);

		foreach ( $siteLinks as $siteId => $pageName ) {
			$item->getSiteLinkList()->addNewSiteLink( $siteId, $pageName );
		}

		$items[] = $item;

		return array( $items );
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testSaveLinksOfItem( Item $item ) {
		$siteLinkTable = $this->newSiteLinkTable( $item->getSiteLinkList()->toArray() );
		$res = $siteLinkTable->saveLinksOfItem( $item );
		$this->assertTrue( $res );
	}

	/**
	 * @depends testSaveLinksOfItem
	 */
	public function testSaveLinksOfItem_duplicate() {
		$siteLinkTable = $this->newSiteLinkTable();
		$item = new Item( new ItemId( 'Q2' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Beer' );

		$res = $siteLinkTable->saveLinksOfItem( $item );
		$this->assertFalse( $res );
	}

	public function testUpdateLinksOfItem() {
		// save initial links
		$item = new Item( new ItemId( 'Q177' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bar' );
		$item->getSiteLinkList()->addNewSiteLink( 'svwiki', 'Börk' );

		$siteLinkTable = $this->newSiteLinkTable( $item->getSiteLinkList()->toArray() );
		$siteLinkTable->saveLinksOfItem( $item );

		// modify links, and save again
		$item->getSiteLinkList()->setNewSiteLink( 'enwiki', 'FooK' );
		$item->getSiteLinkList()->removeLinkWithSiteId( 'dewiki' );
		$item->getSiteLinkList()->addNewSiteLink( 'nlwiki', 'GrooK' );

		$updated = array(
			new SiteLink( 'enwiki', 'FooK' ),
			new SiteLink( 'nlwiki', 'GrooK' )
		);
		$removed = array(
			new SiteLink( 'enwiki', 'FooK' ),
			new SiteLink( 'dewiki', 'Bar' )
		);
		$siteLinkTable = $this->newSiteLinkTable( $updated, $removed );
		$siteLinkTable->saveLinksOfItem( $item );

		// check that the update worked correctly
		$actualLinks = $siteLinkTable->getSiteLinksForItem( $item->getId() );
		$expectedLinks = $item->getSiteLinkList()->toArray();

		$missingLinks = array_udiff( $expectedLinks, $actualLinks, array( $siteLinkTable, 'compareSiteLinks' ) );
		$extraLinks = array_udiff( $actualLinks, $expectedLinks, array( $siteLinkTable, 'compareSiteLinks' ) );

		$this->assertEmpty( $missingLinks, 'Missing links' );
		$this->assertEmpty( $extraLinks, 'Extra links' );
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	public function testGetSiteLinksOfItem( Item $item ) {
		$siteLinkTable = $this->newSiteLinkTable();
		$siteLinks = $siteLinkTable->getSiteLinksForItem( $item->getId() );

		$this->assertArrayEquals(
			$item->getSiteLinkList()->toArray(),
			$siteLinks
		);
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	public function testGetItemIdForSiteLink( Item $item ) {
		$siteLinkTable = $this->newSiteLinkTable();

		foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
			$this->assertEquals(
				$item->getId(),
				$siteLinkTable->getItemIdForSiteLink( $siteLink )
			);
		}
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	public function testGetItemIdForLink( Item $item ) {
		$siteLinkTable = $this->newSiteLinkTable();

		foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
			$this->assertEquals(
				$item->getId(),
				$siteLinkTable->getItemIdForLink( $siteLink->getSiteId(), $siteLink->getPageName() )
			);
		}
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	public function testDeleteLinksOfItem( Item $item ) {
		$siteLinkTable = $this->newSiteLinkTable( array(), $item->getSiteLinkList()->toArray() );

		$this->assertTrue(
			$siteLinkTable->deleteLinksOfItem( $item->getId() ) !== false
		);

		$this->assertEmpty(
			$siteLinkTable->getSiteLinksForItem( $item->getId() )
		);
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	public function testClear( Item $item ) {
		$siteLinkTable = $this->newSiteLinkTable( array(), array(), true );

		$this->assertTrue(
			$siteLinkTable->clear() !== false
		);

		$this->assertEmpty(
			$siteLinkTable->getSiteLinksForItem( $item->getId() )
		);
	}

}
