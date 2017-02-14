<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\SiteLinkTable;
use Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup
 *
 * @group Wikibase
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
		$entityIdComposer = WikibaseRepo::getDefaultInstance()->getEntityIdComposer();
		$siteLinkConflictLookup = new SqlSiteLinkConflictLookup( $entityIdComposer );

		$expected = array(
			array(
				'siteId' => 'enwiki',
				'itemId' => new ItemId( 'Q9' ),
				'sitePage' => 'Kitten'
			)
		);

		$this->assertEquals(
			$expected,
			$siteLinkConflictLookup->getConflictsForItem( $this->getItem( 'Kitten' ) )
		);
	}

	public function testGetConflictsForItem_noConflict() {
		$entityIdComposer = WikibaseRepo::getDefaultInstance()->getEntityIdComposer();
		$siteLinkConflictLookup = new SqlSiteLinkConflictLookup( $entityIdComposer );

		$this->assertSame(
			array(),
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
