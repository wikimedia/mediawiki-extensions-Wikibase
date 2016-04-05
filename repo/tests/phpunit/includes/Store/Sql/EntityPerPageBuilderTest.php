<?php

namespace Wikibase\Test;

use RuntimeException;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Store\SQL\EntityPerPageBuilder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Store\SQL\EntityPerPageBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group WikibaseEntityPerPage
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityPerPageBuilderTest extends \MediaWikiTestCase {

	/**
	 * @var EntityPerPage
	 */
	private $entityPerPageTable;

	/**
	 * @var array[]
	 */
	private $entityPerPageRows;

	/**
	 * @var WikibaseRepo
	 */
	private $wikibaseRepo;

	protected function setUp() {
		parent::setUp();

		$this->wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->entityPerPageTable = $this->wikibaseRepo->getStore()->newEntityPerPage();

		$this->clearTables();
		$items = $this->addItems();

		if ( $this->countPages() !== count( $items ) ) {
			throw new RuntimeException( 'Page count must be equal to item count.' );
		}

		$this->entityPerPageRows = $this->getEntityPerPageData();
	}

	/**
	 * @return User
	 */
	private function getUser() {
		$user = User::newFromName( 'zombie1' );

		if ( $user->getId() === 0 ) {
			$user = User::createNew( $user->getName() );
		}

		return $user;
	}

	private function clearTables() {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->delete( 'page', array( "1" ) );
		$this->entityPerPageTable->clear();

		if ( $this->countPages() !== 0 || $this->countEntityPerPageRows() !== 0 ) {
			throw new RuntimeException( 'Clear failed.' );
		}
	}

	/**
	 * @return Item[]
	 */
	private function addItems() {
		$user = $this->getUser();

		$labels = array( 'New York City', 'Tokyo', 'Jakarta', 'Nairobi',
			'Rome', 'Cairo', 'Santiago', 'Sydney', 'Toronto', 'Berlin' );

		/** @var Item[] $items */
		$items = [];

		$prefix = get_class( $this ) . '/';

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		foreach ( $labels as $label ) {
			$item = new Item();
			$item->setLabel( 'en', $prefix . $label );
			$rev = $store->saveEntity( $item, "added an item", $user, EDIT_NEW );
			$items[] = $rev->getEntity();
		}

		// add another berlin (so we have a valid id), then turn it into a redirect
		$item = new Item();
		$item->setLabel( 'en', $prefix . 'Berlin2' );
		$rev = $store->saveEntity( $item, "added an item", $user, EDIT_NEW );
		$items[] = $rev->getEntity();

		$items = array_reverse( $items );
		$berlin2 = $items[0]->getId();
		$berlin1 = $items[1]->getId();
		$redirect = new EntityRedirect( $berlin2, $berlin1 );

		$store->saveRedirect( $redirect, "created redirect", $user, EDIT_UPDATE );

		return $items;
	}

	/**
	 * @param int $pageId
	 */
	private function partialClearEntityPerPageTable( $pageId ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'wb_entity_per_page', array( 'epp_page_id > ' . $pageId ) );
	}

	/**
	 * @throws RuntimeException
	 * @return int
	 */
	private function getPageIdForPartialClear() {
		$dbw = wfGetDB( DB_MASTER );
		$pageRow = $dbw->select(
			'page',
			'page_id',
			[],
			__METHOD__,
			array(
				'LIMIT' => 1,
				'OFFSET' => 5,
				'ORDER BY' => ' page_id ASC'
			)
		);

		foreach ( $pageRow as $row ) {
			return (int)$row->page_id;
		}

		throw new RuntimeException( 'Expected at least one result' );
	}

	/**
	 * @return int
	 */
	private function countPages() {
		$dbw = wfGetDB( DB_MASTER );
		$pages = $dbw->select( 'page', array( 'page_id' ), [], __METHOD__ );

		return $pages->numRows();
	}

	/**
	 * @return int
	 */
	private function countEntityPerPageRows() {
		$dbw = wfGetDB( DB_MASTER );
		$eppRows = $dbw->selectField( 'wb_entity_per_page', 'count(*)', [], __METHOD__ );

		return (int)$eppRows;
	}

	/**
	 * @return array[]
	 */
	private function getEntityPerPageData() {
		$dbw = wfGetDB( DB_MASTER );
		$rows = $dbw->select(
			'wb_entity_per_page',
			array( 'epp_page_id', 'epp_entity_id', 'epp_redirect_target' ),
			[],
			__METHOD__ );

		$pages = [];

		foreach ( $rows as $row ) {
			$pages[] = get_object_vars( $row );
		}

		return $pages;
	}

	public function testRebuildAll() {
		$this->entityPerPageTable->clear();

		$this->assertEquals( 0, $this->countEntityPerPageRows() );

		$builder = new EntityPerPageBuilder(
			$this->entityPerPageTable,
			$this->wikibaseRepo->getEntityIdParser(),
			$this->wikibaseRepo->getEntityNamespaceLookup(),
			$this->wikibaseRepo->getContentModelMappings()
		);

		$builder->setRebuildAll( true );
		$builder->rebuild();

		$this->assertEquals( count( $this->entityPerPageRows ), $this->countEntityPerPageRows() );

		$this->assertRows( $this->entityPerPageRows );
	}

	public function testRebuildPartial() {
		$pageId = $this->getPageIdForPartialClear();
		$this->partialClearEntityPerPageTable( $pageId );

		$this->assertEquals( 6, $this->countEntityPerPageRows() );

		$builder = new EntityPerPageBuilder(
			$this->entityPerPageTable,
			$this->wikibaseRepo->getEntityIdParser(),
			$this->wikibaseRepo->getEntityNamespaceLookup(),
			$this->wikibaseRepo->getContentModelMappings()
		);

		$builder->rebuild();

		$this->assertEquals( count( $this->entityPerPageRows ), $this->countEntityPerPageRows() );

		$this->assertRows( $this->entityPerPageRows );
	}

	/**
	 * @param array[] $expectedRows
	 */
	private function assertRows( array $expectedRows ) {
		$dbw = wfGetDB( DB_MASTER );

		foreach ( $expectedRows as $expectedRow ) {
			$pageId = (int)$expectedRow['epp_page_id'];

			$resRowObject = $dbw->selectRow(
				'wb_entity_per_page',
				array_keys( $expectedRow ),
				array( 'epp_page_id' => $pageId ), __METHOD__ );

			$resRow = get_object_vars( $resRowObject );

			$this->assertArrayEquals( $expectedRow, $resRow, false, true );
		}
	}

}
