<?php

namespace Wikibase\Test;
use \Wikibase\SiteLinkTable;
use \Wikibase\SiteLink;
use \Wikibase\Item;

/**
 * Tests for the Wikibase\SiteLinkTable class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group SiteLink
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkTableTest extends \MediaWikiTestCase {

	protected $siteLinkTable;

	public function setUp() {
		parent::setUp();

		if ( defined( 'WBC_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local site link table." );
		}

		$this->siteLinkTable = new SiteLinkTable( 'wb_items_per_site', false );
	}

	public function itemProvider() {
		$items = array();

		$item = Item::newEmpty();
		$item->setId( new \Wikibase\EntityId( Item::ENTITY_TYPE, 1 ) );
		$item->setLabel( 'en', 'Beer' );

		$sitelinks = array(
			'cswiki' => 'Pivo',
			'enwiki' => 'Beer',
			'jawiki' => 'ビール'
		);

		foreach( $sitelinks as $site => $page ) {
			$item->addSiteLink( SiteLink::newFromText( $site, $page ) );
		}

		$items[] = $item;

		return array( $items );
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testSaveLinksOfItem( $item ) {
		$res = $this->siteLinkTable->saveLinksOfItem( $item );
		$this->assertTrue( $res );
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	 public function testGetSiteLinksOfItem( $item ) {
		$siteLinks = $this->siteLinkTable->getSiteLinksForItem( $item->getId() );

		$this->assertEquals(
			$item->getSiteLinks(),
			$siteLinks
		);
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	public function testGetEntityIdForSiteLink( Item $item ) {
		$siteLinks = $item->getSiteLinks();

		foreach( $siteLinks as $siteLink ) {
			$this->assertEquals( $item->getId(), $this->siteLinkTable->getEntityIdForSiteLink( $siteLink ) );
		}
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	public function testCountLinks( $item ) {
		$this->assertEquals(
			count( $item->getSiteLinks() ),
			$this->siteLinkTable->countLinks( array( $item->getId()->getNumericId() ) )
		);
	}

	/**
	 * @depends testCountLinks
	 * @dataProvider itemProvider
	 */
	 public function testDeleteLinksOfItem( $item ) {
		$this->assertTrue(
			$this->siteLinkTable->deleteLinksOfItem( $item->getId() ) !== false
		);

		$this->assertEquals(
			array(),
			$this->siteLinkTable->getSiteLinksForItem( $item->getId() )
		);
	}
}
