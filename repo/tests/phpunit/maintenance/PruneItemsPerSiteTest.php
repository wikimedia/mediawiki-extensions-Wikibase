<?php

namespace Wikibase\Repo\Tests\Maintenance;

use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\Maintenance\PruneItemsPerSite;
use Wikibase\Repo\WikibaseRepo;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/pruneItemsPerSite.php';

/**
 * @covers \Wikibase\Repo\Maintenance\PruneItemsPerSite
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PruneItemsPerSiteTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return PruneItemsPerSite::class;
	}

	public static function batchSizeProvider() {
		return [
			[ 2 ],
			[ 5000 ],
		];
	}

	/**
	 * @dataProvider batchSizeProvider
	 */
	public function testExecute( $selectBatchSize ) {
		$this->insertInvalidRows();
		$entityId = $this->storeNewItem();
		$this->insertInvalidRows();

		$this->maintenance->loadWithArgv( [ '--select-batch-size', $selectBatchSize ] );
		$this->maintenance->execute();

		$existingItemRowCount = $this->getDb()->newSelectQueryBuilder()
			->from( 'wb_items_per_site' )
			->where( [ 'ips_item_id' => $entityId->getNumericId() ] )
			->caller( __METHOD__ )->fetchRowCount();
		$allRowCount = $this->getDb()->newSelectQueryBuilder()
			->from( 'wb_items_per_site' )
			->caller( __METHOD__ )->fetchRowCount();
		$this->assertSame( 2, $existingItemRowCount );
		$this->assertSame( $existingItemRowCount, $allRowCount );
	}

	/**
	 * @return ItemId
	 */
	private function storeNewItem() {
		$testUser = $this->getTestUser()->getUser();

		$store = WikibaseRepo::getEntityStore();

		$item = new Item();
		$item->addSiteLink( new SiteLink( 'dewiki', 'Katze' ) );
		$item->addSiteLink( new SiteLink( 'enwiki', 'Cat' ) );

		$store->saveEntity( $item, 'testing', $testUser, EDIT_NEW );

		return $item->getId();
	}

	private function insertInvalidRows() {
		static $c = 0;
		$c++;

		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wb_items_per_site' )
			->row( [
				'ips_item_id' => 3453577 + $c,
				'ips_site_id' => 'dewiki',
				'ips_site_page' => 'Blah_' . $c,
			] )
			->row( [
				'ips_item_id' => 8985043 + $c,
				'ips_site_id' => 'eswiki',
				'ips_site_page' => 'BlahBlah_' . $c,
			] )
			->row( [
				'ips_item_id' => 6834348 + $c,
				'ips_site_id' => 'eswiki',
				'ips_site_page' => 'BlahBlahBlah_' . $c,
			] )
			->caller( __METHOD__ )
			->execute();
		// Create a page with the entity id as title, but in a non-entity NS
		$this->editPage( 'User talk:Q' . ( 3453577 + $c ), __METHOD__, __METHOD__ );
	}

}
