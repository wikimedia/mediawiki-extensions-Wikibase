<?php

namespace Wikibase;
use ORMRow;

/**
 * Represents a lookup database table that make the link between entities and pages.
 * Corresponds to the wb_entities_per_page table.
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
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class EntityPerPageTable implements EntityPerPage {

	/**
	 * @see EntityPerPage::addEntityContent
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entityContent
	 *
	 * @return boolean Success indicator
	 */
	public function addEntityContent( EntityContent $entityContent ) {
		$dbr = wfGetDB( DB_SLAVE );
		$select = $dbr->selectField(
			'wb_entities_per_page',
			'epp_page_id',
			array(
				'epp_entity_id' => $entityContent->getEntity()->getId(),
				'epp_entity_type' => $entityContent->getEntity()->getType()
			),
			__METHOD__
		);
		if( $select !== false ) {
			return false;
		}

		$dbw = wfGetDB( DB_MASTER );
		return $dbw->insert(
			'wb_entities_per_page',
			array(
				'epp_entity_id' => $entityContent->getEntity()->getId(),
				'epp_entity_type' => $entityContent->getEntity()->getType(),
				'epp_page_id' => $entityContent->getTitle()->getArticleID()
			),
			__METHOD__
		);
	}

	/**
	 * @see EntityPerPage::deleteEntityContent
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $entityContent
	 *
	 * @return boolean Success indicator
	 */
	public function deleteEntityContent( EntityContent $entityContent ) {
		$dbw = wfGetDB( DB_MASTER );
		return $dbw->delete(
			'wb_entities_per_page',
			array(
				'epp_entity_id' => $entityContent->getEntity()->getId(),
				'epp_entity_type' => $entityContent->getEntity()->getType()
			),
			__METHOD__
		);
	}

	/**
	 * @see EntityPerPage::clear
	 *
	 * @since 0.1
	 *
	 * @return boolean Success indicator
	 */
	public function clear() {
		return wfGetDB( DB_MASTER )->delete( 'wb_entities_per_page', '*', __METHOD__ );
	}

	/**
	 * @see EntityPerPage::rebuild
	 *
	 * @since 0.1
	 *
	 * @return boolean success indicator
	 */
	public function rebuild() {
		$dbw = wfGetDB( DB_MASTER );
		$continue = true;
		$entityContentFactory = EntityContentFactory::singleton();
		while( $continue ) {
			$pages = $dbw->select(
				array( 'page' ),
				array( 'page_title' ),
				array( 'page_namespace' => array( WB_NS_ITEM, WB_NS_PROPERTY, WB_NS_QUERY ) ),
				__METHOD__,
				array( 'LIMIT' => 1000 )
			);
			foreach ( $pages as $pageRow ) {
				$entityContent = $entityContentFactory->getFromPrefixedId( $pageRow->page_title, \Revision::RAW );
				$this->addEntityContent( $entityContent );
			}
			if ( $pages->numRows() !== 1000 ) {
				$continue = false;
			}
		}
	}
}
