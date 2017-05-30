<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\Sql\SiteLinkTable;
use Wikibase\Repo\Store\Sql\SqlSiteLinkConflictLookup;
use Wikibase\WikibaseSettings;

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

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient does not have '
				. 'a local site link table." );
		}

		$this->tablesUsed[] = 'wb_items_per_site';

		$siteLinkTable = new SiteLinkTable( 'wb_items_per_site', false );

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

		$this->assertEquals(
			$expected,
			$siteLinkConflictLookup->getConflictsForItem( $this->getItem( 'Kitten' ) )
		);
	}

	public function testGetConflictsForItem_noConflict() {
		$siteLinkConflictLookup = $this->newSqlSiteLinkConflictLookup();

		$this->assertSame(
			[],
			$siteLinkConflictLookup->getConflictsForItem( $this->getItem( 'Cat' ) )
		);
	}

	private function getItem( $pageName ) {
		$siteLinks = new SiteLinkList( [ new SiteLink( 'enwiki', $pageName ) ] );

		return new Item( new ItemId( 'Q10' ), null, $siteLinks );
	}

	private function newSqlSiteLinkConflictLookup() {
		$entityIdComposer = new EntityIdComposer( [
			'item' => function ( $repositoryName, $uniquePart ) {
				return ItemId::newFromNumber( $uniquePart );
			},
		] );

		return new SqlSiteLinkConflictLookup( $entityIdComposer );
	}

}
