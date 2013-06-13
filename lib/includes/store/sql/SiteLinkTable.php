<?php

namespace Wikibase;
use MWException;
use Wikibase\DataModel\SimpleSiteLink;

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
class SiteLinkTable extends \DBAccessBase implements SiteLinkCache {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * @since 0.3
	 *
	 * @var bool
	 */
	protected $readonly;

	/**
	 * @since 0.1
	 *
	 * @param string $table The table to use for the sitelinks
	 * @param bool $readonly Whether the table can be modified.
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 */
	public function __construct( $table, $readonly, $wiki = false ) {
		$this->table = $table;
		$this->readonly = $readonly;
		$this->wiki = $wiki;

		assert( is_bool( $this->readonly ) );
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
	 * @throws MWException
	 */
	public function saveLinksOfItem( Item $item, $function = null ) {
		if ( $this->readonly ) {
			throw new MWException( 'Cannot write when in readonly mode' );
		}

		$function = is_null( $function ) ? __METHOD__ : $function;

		if ( is_null( $item->getId() ) ) {
			return false;
		}

		$dbw = $this->getConnection( DB_MASTER );

		$success = $this->deleteLinksOfItem( $item->getId(), $function );

		if ( !$success ) {
			$this->releaseConnection( $dbw );
			return false;
		}

		$siteLinks = $item->getSimpleSiteLinks();

		if ( empty( $siteLinks ) ) {
			$this->releaseConnection( $dbw );
			return true;
		}

		$transactionLevel = $dbw->trxLevel();

		if ( !$transactionLevel ) {
			$dbw->begin( __METHOD__ );
		}

		/**
		 * @var SimpleSiteLink $siteLink
		 */
		foreach ( $siteLinks as $siteLink ) {
			$success = $dbw->insert(
				$this->table,
				array_merge(
					array( 'ips_item_id' => $item->getId()->getNumericId() ),
					array(
						'ips_site_id' => $siteLink->getSiteId(),
						'ips_site_page' => $siteLink->getPageName(),
					)
				),
				$function
			) && $success;
		}

		if ( !$transactionLevel ) {
			$dbw->commit( __METHOD__ );
		}

		$this->releaseConnection( $dbw );
		return $success;
	}

	/**
	 * @see SiteLinkCache::deleteLinksOfItem
	 *
	 * @since 0.1
	 *
	 * @param EntityId $itemId
	 * @param string|null $function
	 *
	 * @return boolean Success indicator
	 * @throws MWException
	 */
	public function deleteLinksOfItem( EntityId $itemId, $function = null ) {
		if ( $this->readonly ) {
			throw new MWException( 'Cannot write when in readonly mode' );
		}

		$dbw = $this->getConnection( DB_MASTER );

		$ok = $dbw->delete(
			$this->table,
			array( 'ips_item_id' => $itemId->getNumericId() ),
			is_null( $function ) ? __METHOD__ : $function
		);

		$this->releaseConnection( $dbw );
		return $ok;
	}

	/**
	 * @see SiteLinkLookup::getItemIdForLink
	 *
	 * @todo may want to deprecate this or change it to always return entity id object only
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return integer|boolean
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle ) {
		// We store page titles with spaces instead of underscores
		$pageTitle = str_replace( '_', ' ', $pageTitle );

		$db = $this->getConnection( DB_SLAVE );

		$result = $db->selectRow(
			$this->table,
			array( 'ips_item_id' ),
			array(
				'ips_site_id' => $globalSiteId,
				'ips_site_page' => $pageTitle,
			)
		);

		$this->releaseConnection( $db );
		return $result === false ? false : (int)$result->ips_item_id;
	}

	/**
	 * @see SiteLinkLookup::getEntityIdForSiteLink
	 *
	 * @since 0.4
	 *
	 * @param SimpleSiteLink $siteLink
	 *
	 * return EntityId|null
	 */
	public function getEntityIdForSiteLink( SimpleSiteLink $siteLink ) {
		$siteId = $siteLink->getSiteId();
		$pageName = $siteLink->getPageName();

		$numericItemId = $this->getItemIdForLink( $siteId, $pageName );

		return is_int( $numericItemId ) ? new EntityId( Item::ENTITY_TYPE, $numericItemId ) : null;
	}

	/**
	 * @see SiteLinkLookup::getConflictsForItem
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param \DatabaseBase|null $db
	 *
	 * @return array of array
	 */
	public function getConflictsForItem( Item $item, \DatabaseBase $db = null ) {
		wfProfileIn( __METHOD__ );

		$links = $item->getSimpleSiteLinks();

		if ( $links === array() ) {
			wfProfileOut( __METHOD__ );
			return array();
		}

		if ( $db ) {
			$dbr = $db;
		} else {
			$dbr = $this->getConnection( DB_SLAVE );
		}

		$anyOfTheLinks = '';

		foreach ( $links as $siteLink ) {
			if ( $anyOfTheLinks !== '' ) {
				$anyOfTheLinks .= "\nOR ";
			}

			$anyOfTheLinks .= '(';
			$anyOfTheLinks .= 'ips_site_id=' . $dbr->addQuotes( $siteLink->getSiteId() );
			$anyOfTheLinks .= ' AND ';
			$anyOfTheLinks .= 'ips_site_page=' . $dbr->addQuotes( $siteLink->getPageName() );
			$anyOfTheLinks .= ')';
		}

		//TODO: $anyOfTheLinks might get very large and hit some size limit imposed by the database.
		//      We could chop it up of we know that size limit. For MySQL, it's select @@max_allowed_packet.

		wfProfileIn( __METHOD__ . '#select' );
		$conflictingLinks = $dbr->select(
			$this->table,
			array(
				'ips_site_id',
				'ips_site_page',
				'ips_item_id',
			),
			"($anyOfTheLinks) AND ips_item_id != " . intval( $item->getId()->getNumericId() ),
			__METHOD__
		);
		wfProfileOut( __METHOD__ . '#select' );

		$conflicts = array();

		foreach ( $conflictingLinks as $link ) {
			$conflicts[] = array(
				'siteId' => $link->ips_site_id,
				'itemId' => (int)$link->ips_item_id,
				'sitePage' => $link->ips_site_page,
			);
		}

		if ( !$db ) {
			$this->releaseConnection( $dbr );
		}

		wfProfileOut( __METHOD__ );
		return $conflicts;
	}

	/**
	 * @see SiteLinkCache::clear
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 * @throws MWException
	 */
	public function clear() {
		if ( $this->readonly ) {
			throw new MWException( 'Cannot write when in readonly mode' );
		}

		$dbw = $this->getConnection( DB_MASTER );

		$ok = $dbw->delete( $this->table, '*', __METHOD__ );

		$this->releaseConnection( $dbw );
		return $ok;
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
		$dbr = $this->getConnection( DB_SLAVE );

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

		$res = $dbr->selectRow(
			$this->table,
			array( 'COUNT(*) AS rowcount' ),
			$conditions,
			__METHOD__
		)->rowcount;

		$this->releaseConnection( $dbr );
		return $res;
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
		$dbr = $this->getConnection( DB_SLAVE );

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

		$this->releaseConnection( $dbr );
		return $siteLinks;
	}

	/**
	 * @see SiteLinkLookup::getSiteLinksForItem
	 *
	 * Get array of SiteLink for an item or returns empty array if no site links
	 *
	 * @since 0.4
	 *
	 * @param EntityId $id
	 *
	 * @throws \MWException
	 *
	 * @return SimpleSiteLink[]
	 */
	public function getSiteLinksForItem( EntityId $entityId ) {
		if ( $entityId->getEntityType() !== Item::ENTITY_TYPE ) {
			throw new \MWException( "$entityId is not an item id" );
		}

		$numericId = $entityId->getNumericId();

		$dbr = $this->getConnection( DB_SLAVE );

		$rows = $dbr->select(
			$this->table,
			array(
				'ips_site_id', 'ips_site_page'
			),
			array(
				'ips_item_id' => $numericId
			),
			__METHOD__
		);

		$siteLinks = array();

		foreach( $rows as $row ) {
			$siteLinks[] = new SimpleSiteLink( $row->ips_site_id, $row->ips_site_page );
		}

		$this->releaseConnection( $dbr );

		return $siteLinks;
	}

}
