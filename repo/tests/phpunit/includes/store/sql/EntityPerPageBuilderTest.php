<?php

namespace Wikibase\Test;
use Wikibase\StoreFactory;
use Wikibase\EntityPerPageBuilder;

/**
 * Tests for the Wikibase\EntityPerPageBuilder class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
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

	protected function getUser() {
		$user = \User::newFromName( 'zombie1' );

		if ( $user->getId() === 0 ) {
			$user = \User::createNew( $user->getName() );
		}

		return $user;
	}

	protected function clearTables() {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->delete( 'page', array( "1" ) );
		$this->clearEntityPerPageTable();

		assert( $this->countPages() === 0 );
		assert( $this->countEntityPerPageRows() === 0 );
	}

	protected function addItems() {
		$user = $this->getUser();

		$labels = array( 'Berlin', 'New York City', 'Tokyo', 'Jakarta', 'Nairobi',
			'Rome', 'Cairo', 'Santiago', 'Sydney', 'Toronto' );

		foreach( $labels as $label ) {
			$itemContent = \Wikibase\ItemContent::newEmpty();
			$itemContent->getEntity()->setLabel( 'en', $label );
			$itemContent->save( "added an item", $user, EDIT_NEW );
		}
	}

	protected function clearEntityPerPageTable( $partial = false ) {
		$dbw = wfGetDB( DB_MASTER );

		if ( $partial === false ) {
			$dbw->delete( 'wb_entity_per_page', array( "1" ) );
		} else {
			$numRows = $this->countEntityPerPageRows();
			$pageRow = $dbw->selectRow( 'page', 'page_id', array( 'page_title' => 'Nairobi' ), __METHOD__ );

			if ( $pageRow ) {
				$dbw->delete( 'wb_entity_per_page', array( 'epp_page_id > ' . $pageRow->page_id ) );
			}
		}
	}

	protected function countPages() {
		$dbw = wfGetDB( DB_MASTER );
		$pages = $dbw->select( 'page', array( 'page_id' ), array(), __METHOD__ );

		return $pages->numRows();
	}

	protected function countEntityPerPageRows() {
		$dbw = wfGetDB( DB_MASTER );
		$eppRows = $dbw->select( 'wb_entity_per_page', array( 'epp_entity_id' ), array(), __METHOD__ );

		return $eppRows->numRows();
	}

	protected function getEntityPerPageData() {
		$dbw = wfGetDB( DB_MASTER );
		$items = $dbw->select( 'wb_entity_per_page', array( 'epp_entity_id', 'epp_page_id' ), array(), __METHOD__ );

		$pages = array();

		foreach ( $items as $row ) {
			$pages[] = array( 'page_id' => $row->epp_page_id, 'entity_id' => $row->epp_entity_id );
		}

		return $pages;
	}

	public function testRebuild() {
		$this->clearTables();
		$this->addItems();

		assert( $this->countPages() === 10 );

		$pages = $this->getEntityPerPageData();

		$this->clearEntityPerPageTable();

		assert( $this->countEntityPerPageRows() === 0 );

		$entityPerPageTable = StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		$builder = new EntityPerPageBuilder( $entityPerPageTable );
		$builder->setRebuildAll( true );
		$builder->rebuild();

		$this->assertEquals( $this->countEntityPerPageRows(), 10 );

		$dbw = wfGetDB( DB_MASTER );

		foreach( $pages as $page ) {
			$res = $dbw->selectRow( 'wb_entity_per_page', array( 'epp_entity_id', 'epp_page_id' ),
				array( 'epp_page_id' => $page['page_id'] ), __METHOD__ );
			$this->assertEquals( $res->epp_entity_id, $page['entity_id'] );
		}
	}

	public function testRebuildPartial() {
		$this->clearTables();
		$this->addItems();

		assert( $this->countPages() === 10 );

		$pages = $this->getEntityPerPageData();

		$this->clearEntityPerPageTable( true );

		$entityPerPageTable = StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		$builder = new EntityPerPageBuilder( $entityPerPageTable );
		$builder->setRebuildAll( false );
		$builder->rebuild();

		$this->assertEquals( $this->countEntityPerPageRows(), 10 );

		$dbw = wfGetDB( DB_MASTER );

		foreach( $pages as $page ) {
			$res = $dbw->selectRow( 'wb_entity_per_page', array( 'epp_entity_id', 'epp_page_id' ),
				array( 'epp_page_id' => $page['page_id'] ), __METHOD__ );
			$this->assertEquals( $res->epp_entity_id, $page['entity_id'] );
		}
	}
}
