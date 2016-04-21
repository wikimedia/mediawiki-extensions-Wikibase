<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\SiteLinkTable;
use Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup;

/**
 * @covers Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SiteLink
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlSiteLinkConflictLookupTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient does not have '
				. 'a local site link table." );
		}

		$this->tablesUsed[] = 'wb_items_per_site';

		$siteLinkTable = new SiteLinkTable( 'wb_items_per_site', false );

		$siteLinks = new SiteLinkList( array(
			new SiteLink( 'dewiki', 'Katze' ),
			new SiteLink( 'enwiki', 'Kitten' ),
			new SiteLink( 'eswiki', 'Gato' )
		) );

		$item = new Item( new ItemId( 'Q9' ), null, $siteLinks );

		$siteLinkTable->saveLinksOfItem( $item );
	}

	public function testGetConflictsForItem() {
		$siteLinkConflictLookup = new SqlSiteLinkConflictLookup();

		$expected = array(
			array(
				'siteId' => 'enwiki',
				'itemId' => 9,
				'sitePage' => 'Kitten'
			)
		);

		$this->assertSame(
			$expected,
			$siteLinkConflictLookup->getConflictsForItem( $this->getItem( 'Kitten' ) )
		);
	}

	public function testGetConflictsForItem_noConflict() {
		$siteLinkConflictLookup = new SqlSiteLinkConflictLookup();

		$this->assertSame(
			[],
			$siteLinkConflictLookup->getConflictsForItem( $this->getItem( 'Cat' ) )
		);
	}

	private function getItem( $pageName ) {
		$siteLinks = new SiteLinkList( array(
			new SiteLink( 'enwiki', $pageName )
		) );

		return new Item( new ItemId( 'Q10' ), null, $siteLinks );
	}

}
