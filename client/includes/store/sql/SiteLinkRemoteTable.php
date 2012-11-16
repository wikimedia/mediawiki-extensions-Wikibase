<?php

namespace Wikibase;
use MWException;

/**
 * Represents a lookup database table for sitelinks.
 * It should have these fields: ips_item_id, ips_site_id, ips_site_page.
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
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkRemoteTable extends RepoTable implements SiteLinkLookup {

	/**
	 * @see SiteLinkLookup::getItemIdForLink
	 *
	 * @since 0.3
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return integer|boolean
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle ) {
		$dbr = $this->lb->getConnection( DB_SLAVE, array(), $this->repoDb );
		$result = $dbr->selectRow(
			$this->table,
			array( 'ips_item_id' ),
			array(
				'ips_site_id' => $globalSiteId,
				'ips_site_page' => $pageTitle,
			)
		);

		$this->lb->reuseConnection( $dbr );

		return $result === false ? false : (int)$result->ips_item_id;
	}

	/**
	 * @see SiteLinkLookup::getConflictsForItem
	 *
	 * @since 0.3
	 *
	 * @param Item $item
	 *
	 * @return array of array
	 */
	public function getConflictsForItem( Item $item ) {
		$links = $item->getSiteLinks();

		if ( $links === array() ) {
			return array();
		}

		$dbr = $this->lb->getConnection( DB_SLAVE );

		$anyOfTheLinks = '';

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $links as $siteLink ) {
			if ( $anyOfTheLinks !== '' ) {
				$anyOfTheLinks .= "\nOR ";
			}

			$anyOfTheLinks .= '(';
			$anyOfTheLinks .= 'ips_site_id=' . $dbr->addQuotes( $siteLink->getSite()->getGlobalId() );
			$anyOfTheLinks .= ' AND ';
			$anyOfTheLinks .= 'ips_site_page=' . $dbr->addQuotes( $siteLink->getPage() );
			$anyOfTheLinks .= ')';
		}

		//TODO: $anyOfTheLinks might get very large and hit some size limit imposed by the database.
		//      We could chop it up of we know that size limit. For MySQL, it's select @@max_allowed_packet.

		$conflictingLinks = $dbr->select(
			$this->table,
			array(
				'ips_site_id',
				'ips_site_page',
				'ips_item_id',
			),
			"($anyOfTheLinks) AND ips_item_id != " . $item->getId()->getNumericId(),
			__METHOD__
		);

		$conflicts = array();

		foreach ( $conflictingLinks as $link ) {
			$conflicts[] = array(
				'siteId' => $link->ips_site_id,
				'itemId' => (int)$link->ips_item_id,
				'sitePage' => $link->ips_site_page,
			);
		}

		$this->lb->reuseConnection( $dbr );

		return $conflicts;
	}

	/**
	 * @see SiteLinkLookup::countLinks
	 *
	 * @since 0.3
	 *
	 * @param array $itemIds
	 * @param array $siteIds
	 * @param array $pageNames
	 *
	 * @return integer
	 */
	public function countLinks( array $itemIds, array $siteIds = array(), array $pageNames = array() ) {
		$dbr = $this->lb->getConnection( DB_SLAVE );

		$conditions = array();

		if ( $itemIds !== array() ) {
			$conditions['ips_item_id'] = $itemIds;
		}

		if ( $siteIds !== array() ) {
			$conditions['ips_site_id'] = $siteIds;
		}

		if ( $pageNames !== array() ) {
			$conditions['ips_site_page'] = $pageNames;
		}

		$count = $dbr->selectRow(
			$this->table,
			array( 'COUNT(*) AS rowcount' ),
			$conditions,
			__METHOD__
		)->rowcount;

		$this->lb->reuseConnection( $dbr );
		return $count;
	}

	/**
	 * @see SiteLinkLookup::getLinks
	 *
	 * @since 0.3
	 *
	 * @param array $itemIds
	 * @param array $siteIds
	 * @param array $pageNames
	 *
	 * @return array[]
	 */
	public function getLinks( array $itemIds, array $siteIds = array(), array $pageNames = array() ) {
		$dbr = $this->lb->getConnection( DB_SLAVE );

		$conditions = array();

		if ( $itemIds !== array() ) {
			$conditions['ips_item_id'] = $itemIds;
		}

		if ( $siteIds !== array() ) {
			$conditions['ips_site_id'] = $siteIds;
		}

		if ( $pageNames !== array() ) {
			$conditions['ips_site_page'] = $pageNames;
		}

		$links = $dbr->select(
			$this->table,
			array(
				'ips_site_id',
				'ips_site_page',
				'ips_item_id',
			),
			$conditions,
			__METHOD__
		);

		$siteLinks = array();

		foreach ( $links as $link ) {
			$siteLinks[] = array(
				$link->ips_site_id,
				$link->ips_site_page,
				$link->ips_item_id,
			);
		}

		$this->lb->reuseConnection( $dbr );

		return $siteLinks;
	}

}
