<?php

namespace Wikibase\Repo\Tests\Maintenance;

use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\Maintenance\RebuildItemsPerSite;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\IDatabase;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/rebuildItemsPerSite.php';

/**
 * @covers \Wikibase\Repo\Maintenance\RebuildItemsPerSite
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < mail@mariushoch.de >
 */
class RebuildItemsPerSiteTest extends MaintenanceBaseTestCase {

	/**
	 * @var ItemId[]
	 */
	private $itemIds = [];

	protected function getMaintenanceClass() {
		return RebuildItemsPerSite::class;
	}

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'wb_items_per_site';

		if ( !$this->itemIds ) {
			$this->itemIds = $this->storeItems();
		}
	}

	public function pageIdProvider() {
		return [
			'Rebuild all' => [
				[ 'Cat', 'Katze', 'Кошка' ],
			],
			'Rebuild all, with page id range' => [
				[ 'Cat', 'Katze', 'Кошка' ],
				'firstPageId' => 1,
				'lastPageId' => PHP_INT_MAX,
			],
			'Rebuild first item by page id' => [
				[ 'Cat' ],
				'firstPageId' => 1,
				'lastPageId' => 'item-0',
			],
			'Rebuild second item by page id' => [
				[ 'Katze' ],
				'firstPageId' => 'item-1',
				'lastPageId' => 'item-1',
			],
			'Rebuild second and third item by page id' => [
				[ 'Katze', 'Кошка' ],
				'firstPageId' => 'item-1',
				'lastPageId' => 'item-2',
			],
		];
	}

	/**
	 * @dataProvider pageIdProvider
	 */
	public function testExecute( $expectedSiteLinks, $firstPageId = null, $lastPageId = null ) {
		$this->clearItemsPerSite();

		$argv = [];
		if ( $firstPageId !== null ) {
			$argv[] = '--first-page-id';
			$argv[] = $this->resolvePageId( $firstPageId );
		}
		if ( $lastPageId !== null ) {
			$argv[] = '--last-page-id';
			$argv[] = $this->resolvePageId( $lastPageId );
		}

		$this->maintenance->loadWithArgv( $argv );
		$this->maintenance->execute();

		$existingSiteLinks = $this->db->selectFieldValues(
			'wb_items_per_site',
			'ips_site_page',
			IDatabase::ALL_ROWS,
			__METHOD__
		);
		$this->assertArrayEquals( $expectedSiteLinks, $existingSiteLinks );
	}

	public function testExecuteFile() {
		$this->clearItemsPerSite();

		// Create a file containing the first and the third item ids (the "Cat" and the "Кошка" ones).
		$file = tempnam( sys_get_temp_dir(), "Wikibase-RebuildItemsPerSiteTest" );
		file_put_contents( $file, $this->itemIds[0] . PHP_EOL . $this->itemIds[2] );

		$this->maintenance->loadWithArgv( [ '--file', $file ] );
		$this->maintenance->execute();
		unlink( $file );

		$existingSiteLinks = $this->db->selectFieldValues(
			'wb_items_per_site',
			'ips_site_page',
			IDatabase::ALL_ROWS,
			__METHOD__
		);
		$this->assertArrayEquals( [ 'Cat', 'Кошка' ], $existingSiteLinks );
	}

	/**
	 * @return ItemId[]
	 */
	private function storeItems(): array {
		$testUser = $this->getTestUser()->getUser();

		$store = WikibaseRepo::getEntityStore();

		$item1 = new Item();
		$item1->addSiteLink( new SiteLink( 'enwiki', 'Cat' ) );
		$store->saveEntity( $item1, 'testing', $testUser, EDIT_NEW );

		$item2 = new Item();
		$item2->addSiteLink( new SiteLink( 'dewiki', 'Katze' ) );
		$store->saveEntity( $item2, 'testing', $testUser, EDIT_NEW );

		$item3 = new Item();
		$item3->addSiteLink( new SiteLink( 'ruwiki', 'Кошка' ) );
		$store->saveEntity( $item3, 'testing', $testUser, EDIT_NEW );

		return [ $item1->getId(), $item2->getId(), $item3->getId() ];
	}

	/**
	 * Resolve a reference like "item-N" to the page id of $this->itemIds[N].
	 * Ints are passed through as is, for convenience.
	 *
	 * @param int|string $idOrReference
	 *
	 * @return int The (item's) page id
	 */
	private function resolvePageId( $idOrReference ): int {
		if ( is_int( $idOrReference ) ) {
			return $idOrReference;
		}

		$itemId = $this->itemIds[ intval( substr( $idOrReference, 5 ) ) ];
		$entityTitleLookup = WikibaseRepo::getEntityTitleLookup();

		return $entityTitleLookup->getTitleForId( $itemId )->getArticleID();
	}

	private function clearItemsPerSite(): void {
		$this->db->delete( 'wb_items_per_site', IDatabase::ALL_ROWS, __METHOD__ );
	}

}
