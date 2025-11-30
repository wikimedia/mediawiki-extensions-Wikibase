<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlSiteLinkConflictLookupTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	protected function setUp(): void {
		parent::setUp();

		$siteLinkTable = new SiteLinkTable(
			'wb_items_per_site',
			false,
			$this->getRepoDomainDb()
		);

		$siteLinks = new SiteLinkList( [
			new SiteLink( 'dewiki', 'Katze' ),
			new SiteLink( 'enwiki', 'Kitten' ),
			new SiteLink( 'eswiki', 'Gato' ),
		] );

		$item = new Item( new ItemId( 'Q9' ), null, $siteLinks );

		$siteLinkTable->saveLinksOfItem( $item );
	}

	public function testGetConflictsForItem() {
		$siteLinkConflictLookup = $this->newSqlSiteLinkConflictLookup();

		$expected = [ [
			'siteId' => 'enwiki',
			'itemId' => new ItemId( 'Q9' ),
			'sitePage' => 'Kitten',
		] ];

		$item = $this->getItem( 'Kitten' );

		$this->assertEquals(
			$expected,
			$siteLinkConflictLookup->getConflictsForItem( $item->getId(), $item->getSiteLinkList() )
		);
	}

	public function testGetConflictsForItem_noConflict() {
		$siteLinkConflictLookup = $this->newSqlSiteLinkConflictLookup();
		$item = $this->getItem( 'Cat' );

		$this->assertSame(
			[],
			$siteLinkConflictLookup->getConflictsForItem( $item->getId(), $item->getSiteLinkList() )
		);
	}

	private function getItem( $pageName ) {
		$siteLinks = new SiteLinkList( [ new SiteLink( 'enwiki', $pageName ) ] );

		return new Item( new ItemId( 'Q10' ), null, $siteLinks );
	}

	private function newSqlSiteLinkConflictLookup() {
		return new SqlSiteLinkConflictLookup(
			$this->getRepoDomainDb(),
			new EntityIdComposer( [ 'item' => ItemId::newFromNumber( ... ) ] )
		);
	}

}
