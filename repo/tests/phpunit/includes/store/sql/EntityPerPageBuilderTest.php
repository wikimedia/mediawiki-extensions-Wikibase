<?php

namespace Wikibase\Test;

use RuntimeException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityPerPageBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\EntityPerPageBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group WikibaseEntityPerPage
 * @group Database
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityPerPageBuilderTest extends \MediaWikiTestCase {

	protected $entityPerPageTable;

	protected $entityPerPageRows;

	/**
	 * @var WikibaseRepo
	 */
	protected $wikibaseRepo;

	public function setUp() {
		parent::setUp();

		$this->wikibaseRepo = new WikibaseRepo( $this->getTestSettings() );

		$this->entityPerPageTable = $this->wikibaseRepo->getStore()->newEntityPerPage();

		$this->clearTables();
		$this->addItems();

		assert( $this->countPages() === 10 );

		$this->entityPerPageRows = $this->getEntityPerPageData();
	}

	/**
	 * @return \User
	 */
	protected function getUser() {
		$user = \User::newFromName( 'zombie1' );

		if ( $user->getId() === 0 ) {
			$user = \User::createNew( $user->getName() );
		}

		return $user;
	}

	protected function getTestSettings() {
		$globalSettings = WikibaseRepo::getDefaultInstance()->getSettings()->getArrayCopy();

		$settings = array_merge(
			$globalSettings,
			array(
				'entityNamespaces' => array(
					'wikibase-item' => 0,
					'wikibase-property' => 102
				)
			)
		);

		return new SettingsArray( $settings );
	}

	protected function clearTables() {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->delete( 'page', array( "1" ) );
		$this->entityPerPageTable->clear();

		assert( $this->countPages() === 0 );
		assert( $this->countEntityPerPageRows() === 0 );
	}

	protected function addItems() {
		$user = $this->getUser();

		$labels = array( 'Berlin', 'New York City', 'Tokyo', 'Jakarta', 'Nairobi',
			'Rome', 'Cairo', 'Santiago', 'Sydney', 'Toronto' );

		$prefix = get_class( $this ) . '/';

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		foreach( $labels as $label ) {
			$item = Item::newEmpty();
			$item->setLabel( 'en', $prefix . $label );
			$store->saveEntity( $item, "added an item", $user, EDIT_NEW );
		}
	}

	protected function partialClearEntityPerPageTable( $pageId ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'wb_entity_per_page', array( 'epp_page_id > ' . $pageId ) );
	}

	/**
	 * @return int
	 * @throws RuntimeException
	 */
	protected function getPageIdForPartialClear() {
		$dbw = wfGetDB( DB_MASTER );
		$pageRow = $dbw->select(
			'page',
			'page_id',
			array(),
			__METHOD__,
			array(
				'LIMIT' =>  1,
				'OFFSET' => 5,
				'ORDER BY' => ' page_id ASC'
			)
		);

		foreach( $pageRow as $row ) {
			return (int)$row->page_id;
		}

		throw new RuntimeException( 'Expected at least one result' );
	}

	/**
	 * @return int
	 */
	protected function countPages() {
		$dbw = wfGetDB( DB_MASTER );
		$pages = $dbw->select( 'page', array( 'page_id' ), array(), __METHOD__ );

		return $pages->numRows();
	}

	/**
	 * @return int
	 */
	protected function countEntityPerPageRows() {
		$dbw = wfGetDB( DB_MASTER );
		$eppRows = $dbw->select( 'wb_entity_per_page', array( 'epp_entity_id' ), array(), __METHOD__ );

		return $eppRows->numRows();
	}

	/**
	 * @return array
	 */
	protected function getEntityPerPageData() {
		$dbw = wfGetDB( DB_MASTER );
		$rows = $dbw->select( 'wb_entity_per_page', array( 'epp_entity_id', 'epp_page_id' ), array(), __METHOD__ );

		$pages = array();

		foreach ( $rows as $row ) {
			$pages[] = array( 'page_id' => $row->epp_page_id, 'entity_id' => $row->epp_entity_id );
		}

		return $pages;
	}

	public function testRebuildAll() {
		$this->entityPerPageTable->clear();

		assert( $this->countEntityPerPageRows() === 0 );

		$builder = new EntityPerPageBuilder(
			$this->entityPerPageTable,
			$this->wikibaseRepo->getEntityIdParser(),
			$this->wikibaseRepo->getContentModelMappings()
		);

		$builder->setRebuildAll( true );
		$builder->rebuild();

		$this->assertEquals( $this->countEntityPerPageRows(), 10 );

		$dbw = wfGetDB( DB_MASTER );

		foreach( $this->entityPerPageRows as $row ) {
			$res = $dbw->selectRow( 'wb_entity_per_page', array( 'epp_entity_id', 'epp_page_id' ),
				array( 'epp_page_id' => $row['page_id'] ), __METHOD__ );
			$this->assertEquals( $res->epp_entity_id, $row['entity_id'] );
		}
	}

	public function testRebuildPartial() {
		$pageId = $this->getPageIdForPartialClear();
		$this->partialClearEntityPerPageTable( $pageId );

		assert( $this->countEntityPerPageRows() === 6 );

		$builder = new EntityPerPageBuilder(
			$this->entityPerPageTable,
			$this->wikibaseRepo->getEntityIdParser(),
			$this->wikibaseRepo->getContentModelMappings()
		);

		$builder->rebuild();

		$this->assertEquals( 10, $this->countEntityPerPageRows() );

		$dbw = wfGetDB( DB_MASTER );

		foreach( $this->entityPerPageRows as $row ) {
			$res = $dbw->selectRow( 'wb_entity_per_page', array( 'epp_entity_id', 'epp_page_id' ),
				array( 'epp_page_id' => $row['page_id'] ), __METHOD__ );
			$this->assertEquals( $res->epp_entity_id, $row['entity_id'] );
		}
	}
}
