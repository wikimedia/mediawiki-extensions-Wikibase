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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SiteLinkTable implements SiteLinkCache {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * @since 0.1
	 *
	 * @param string $table
	 */
	public function __construct( $table ) {
		$this->table = $table;
	}

	/**
	 * @see SiteLinkCache::saveLinksOfItem
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param string|null $function
	 *
	 * @return boolean Success indicator
	 */
	public function saveLinksOfItem( Item $item, $function = null ) {
		$function = is_null( $function ) ? __METHOD__ : $function;

		$dbw = wfGetDB( DB_MASTER );

		$success = $this->deleteLinksOfItem( $item, $function );

		if ( !$success ) {
			return false;
		}

		$siteLinks = $item->getSiteLinks();

		if ( empty( $siteLinks ) ) {
			return true;
		}

		$transactionLevel = $dbw->trxLevel();

		if ( !$transactionLevel ) {
			$dbw->begin( __METHOD__ );
		}

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $siteLinks as $siteLink ) {
			$success = $dbw->insert(
				$this->table,
				array_merge(
					array( 'ips_item_id' => $item->getId() ),
					array(
						'ips_site_id' => $siteLink->getSite()->getGlobalId(),
						'ips_site_page' => $siteLink->getPage(),
					)
				),
				$function
			) && $success;
		}

		if ( !$transactionLevel ) {
			$dbw->commit( __METHOD__ );
		}

		return $success;
	}

	/**
	 * @see SiteLinkCache::deleteLinksOfItem
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param string|null $function
	 *
	 * @return boolean Success indicator
	 */
	public function deleteLinksOfItem( Item $item, $function = null ) {
		return wfGetDB( DB_MASTER )->delete(
			$this->table,
			array( 'ips_item_id' => $item->getId() ),
			is_null( $function ) ? __METHOD__ : $function
		);
	}

	/**
	 * @see SiteLinkLookup::getItemIdForLink
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return integer|boolean
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle ) {
		$result = wfGetDB( DB_SLAVE )->selectRow(
			$this->table,
			array( 'ips_item_id' ),
			array(
				'ips_site_id' => $globalSiteId,
				'ips_site_page' => $pageTitle,
			)
		);

		return $result === false ? false : $result->ips_item_id;
	}

	/**
	 * @see SiteLinkLookup::getConflictsForItem
	 *
	 * @since 0.1
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

		$dbw = wfGetDB( DB_SLAVE );

		$anyOfTheLinks = '';

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $links as $siteLink ) {
			if ( $anyOfTheLinks !== '' ) {
				$anyOfTheLinks .= "\nOR ";
			}

			$anyOfTheLinks .= '(';
			$anyOfTheLinks .= 'ips_site_id=' . $dbw->addQuotes( $siteLink->getSite()->getGlobalId() );
			$anyOfTheLinks .= ' AND ';
			$anyOfTheLinks .= 'ips_site_page=' . $dbw->addQuotes( $siteLink->getPage() );
			$anyOfTheLinks .= ')';
		}

		//TODO: $anyOfTheLinks might get very large and hit some size limit imposed by the database.
		//      We could chop it up of we know that size limit. For MySQL, it's select @@max_allowed_packet.

		$conflictingLinks = $dbw->select(
			$this->table,
			array(
				'ips_site_id',
				'ips_site_page',
				'ips_item_id',
			),
			"($anyOfTheLinks) AND ips_item_id != " . (int)$item->getId(),
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

		return $conflicts;
	}

}
