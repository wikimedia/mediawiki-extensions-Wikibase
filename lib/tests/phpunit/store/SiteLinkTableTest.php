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

	public function constructorProvider() {
		return array(
			array( 'its_a_table_name' ),
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $tableName ) {
		$instance = new SiteLinkTable( $tableName, false );

		// @todo: what kind of test is this?
		$this->assertTrue( true );

		// TODO: migrate tests from ItemDeletionUpdate and ItemStructuredSave
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
		if ( defined( 'WBC_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because you're running it on a WikibaseClient instance." );
		}

		$siteLinkTable = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newSiteLinkCache();

		$res = $siteLinkTable->saveLinksOfItem( $item );
		$this->assertTrue( $res );
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	 public function testGetSiteLinksOfItem( $item ) {
	 	$siteLinkTable = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newSiteLinkCache();

		$siteLinks = $siteLinkTable->getSiteLinksForItem( $item->getId() );

		$this->assertEquals(
			$item->getSiteLinks(),
			$siteLinks
		);
	}

	/**
	 * @depends testSaveLinksOfItem
	 * @dataProvider itemProvider
	 */
	public function testCountLinks( $item ) {
		$siteLinkTable = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newSiteLinkCache();

		$this->assertEquals(
			count( $item->getSiteLinks() ),
			$siteLinkTable->countLinks( array( $item->getId()->getNumericId() ) )
		);
	}

	/**
	 * @depends testCountLinks
	 * @dataProvider itemProvider
	 */
	 public function testDeleteLinksOfItem( $item ) {
	 	$siteLinkTable = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newSiteLinkCache();

		$this->assertTrue(
			$siteLinkTable->deleteLinksOfItem( $item->getId() )
		);

		$this->assertEquals(
			array(),
			$siteLinkTable->getSiteLinksForItem( $item->getId() )
		);
	}
}
