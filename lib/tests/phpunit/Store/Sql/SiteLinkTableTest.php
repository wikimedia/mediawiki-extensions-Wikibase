<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use InvalidArgumentException;
use TitleValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikibase\WikibaseSettings;

/**
 * @covers Wikibase\Lib\Store\Sql\SiteLinkTable
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkTableTest extends \MediaWikiTestCase {

	/**
	 * @var SiteLinkTable
	 */
	private $siteLinkTable;

	protected function setUp() {
		parent::setUp();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local site link table." );
		}

		$this->siteLinkTable = new SiteLinkTable( 'wb_items_per_site', false );
		$this->tablesUsed[] = 'wb_items_per_site';
	}

	public function itemProvider() {
		$items = [];

		$item = new Item( new ItemId( 'Q1' ) );
		$item->setLabel( 'en', 'Beer' );

		$siteLinks = [
			'cswiki' => 'Pivo',
			'enwiki' => 'Beer',
			'jawiki' => 'ビール'
		];

		foreach ( $siteLinks as $siteId => $pageName ) {
			$item->getSiteLinkList()->addNewSiteLink( $siteId, $pageName );
		}

		$items[] = $item;

		return [ $items ];
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testSaveLinksOfItem( Item $item ) {
		$res = $this->siteLinkTable->saveLinksOfItem( $item );
		$this->assertTrue( $res );
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testSaveLinksOfItem_duplicate( Item $otherItem ) {
		$this->siteLinkTable->saveLinksOfItem( $otherItem );
		$item = new Item( new ItemId( 'Q2' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Beer' );

		$res = $this->siteLinkTable->saveLinksOfItem( $item );
		$this->assertFalse( $res );
	}

	public function testUpdateLinksOfItem() {
		// save initial links
		$item = new Item( new ItemId( 'Q177' ) );
		$siteLinks = $item->getSiteLinkList();
		$siteLinks->addNewSiteLink( 'enwiki', 'Foo' );
		$siteLinks->addNewSiteLink( 'dewiki', 'Bar' );
		$siteLinks->addNewSiteLink( 'svwiki', 'Börk' );

		$this->siteLinkTable->saveLinksOfItem( $item );

		// modify links, and save again
		$siteLinks->removeLinkWithSiteId( 'enwiki' );
		$siteLinks->addNewSiteLink( 'enwiki', 'FooK' );
		$siteLinks->removeLinkWithSiteId( 'dewiki' );
		$siteLinks->addNewSiteLink( 'nlwiki', 'GrooK' );

		$this->siteLinkTable->saveLinksOfItem( $item );

		// check that the update worked correctly
		$actualLinks = $this->siteLinkTable->getSiteLinksForItem( $item->getId() );
		$this->assertArrayEquals( $siteLinks->toArray(), $actualLinks );
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGetSiteLinksOfItem( Item $item ) {
		$this->siteLinkTable->saveLinksOfItem( $item );
		$siteLinks = $this->siteLinkTable->getSiteLinksForItem( $item->getId() );

		$this->assertArrayEquals(
			$item->getSiteLinkList()->toArray(),
			$siteLinks
		);
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGetItemIdForSiteLink( Item $item ) {
		$this->siteLinkTable->saveLinksOfItem( $item );
		foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
			$this->assertEquals(
				$item->getId(),
				$this->siteLinkTable->getItemIdForSiteLink( $siteLink )
			);
		}
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGetItemIdForLink( Item $item ) {
		$this->siteLinkTable->saveLinksOfItem( $item );
		foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
			$this->assertEquals(
				$item->getId(),
				$this->siteLinkTable->getItemIdForLink( $siteLink->getSiteId(), $siteLink->getPageName() )
			);
		}
	}

	public function testGetEntityIdForLinkedTitle_doesNotAcceptObjects() {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->siteLinkTable->getEntityIdForLinkedTitle( 'enwiki', new TitleValue( 0, 'Beer' ) );
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGetEntityIdForLinkedTitle( Item $item ) {
		$this->siteLinkTable->saveLinksOfItem( $item );
		foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
			$this->assertEquals(
				$item->getId(),
				$this->siteLinkTable->getEntityIdForLinkedTitle( $siteLink->getSiteId(), $siteLink->getPageName() )
			);
		}
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testDeleteLinksOfItem( Item $item ) {
		$this->siteLinkTable->saveLinksOfItem( $item );
		$this->assertTrue(
			$this->siteLinkTable->deleteLinksOfItem( $item->getId() ) !== false
		);

		$this->assertEmpty(
			$this->siteLinkTable->getSiteLinksForItem( $item->getId() )
		);
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testClear( Item $item ) {
		$this->siteLinkTable->saveLinksOfItem( $item );
		$this->assertTrue(
			$this->siteLinkTable->clear() !== false
		);

		$this->assertEmpty(
			$this->siteLinkTable->getSiteLinksForItem( $item->getId() )
		);
	}

}
