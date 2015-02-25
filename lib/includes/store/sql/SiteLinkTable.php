<?php

namespace Wikibase\Lib\Store;

use DatabaseBase;
use MWException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * Represents a lookup database table for sitelinks.
 * It should have these fields: ips_item_id, ips_site_id, ips_site_page.
 *
 * @since 0.1
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
	 *
	 * @throws MWException
	 */
	public function __construct( $table, $readonly, $wiki = false ) {
		if ( !is_string( $table ) ) {
			throw new MWException( '$table must be a string.' );
		}
		if ( !is_bool( $readonly ) ) {
			throw new MWException( '$readonly must be boolean.' );
		}
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new MWException( '$wiki must be a string or false.' );
		}

		$this->table = $table;
		$this->readonly = $readonly;
		$this->wiki = $wiki;
	}

	/**
	 * @param SiteLink $a
	 * @param SiteLink $b
	 *
	 * @return int
	 */
	public function compareSiteLinks( SiteLink $a, SiteLink $b ) {
		$siteComp = strcmp( $a->getSiteId(), $b->getSiteId() );

		if ( $siteComp !== 0 ) {
			return $siteComp;
		}

		$pageComp = strcmp( $a->getPageName(), $b->getPageName() );

		if ( $pageComp !== 0 ) {
			return $pageComp;
		}

		return 0;
	}

	/**
	 * @see SiteLinkCache::saveLinksOfItem
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 *
	 * @return boolean Success indicator
	 */
	public function saveLinksOfItem( Item $item ) {
		//First check whether there's anything to update
		$newLinks = $item->getSiteLinks();
		$oldLinks = $this->getSiteLinksForItem( $item->getId() );

		$linksToInsert = array_udiff( $newLinks, $oldLinks, array( $this, 'compareSiteLinks' ) );
		$linksToDelete = array_udiff( $oldLinks, $newLinks, array( $this, 'compareSiteLinks' ) );

		if ( !$linksToInsert && !$linksToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": links did not change, returning." );
			return true;
		}

		$ok = true;
		$dbw = $this->getConnection( DB_MASTER );

		//TODO: consider doing delete and insert in the same callback, so they share a transaction.

		if ( $ok && $linksToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": " . count( $linksToDelete ) . " links to delete." );
			$ok = $dbw->deadlockLoop( array( $this, 'deleteLinksInternal' ), $item, $linksToDelete, $dbw );
		}

		if ( $ok && $linksToInsert ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": " . count( $linksToInsert ) . " links to insert." );
			$ok = $dbw->deadlockLoop( array( $this, 'insertLinksInternal' ), $item, $linksToInsert, $dbw );
		}

		$this->releaseConnection( $dbw );

		return $ok;
	}

	/**
	 * Internal callback for inserting a list of links.
	 *
	 * @note: this is public only because it acts as a callback, there should be no
	 *        reason to call this directly!
	 *
	 * @since 0.5
	 *
	 * @param Item $item
	 * @param SiteLink[] $links
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function insertLinksInternal( Item $item, $links, DatabaseBase $dbw ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': inserting links for ' . $item->getId()->getSerialization() );

		$success = true;
		foreach ( $links as $link ) {
			$success = $dbw->insert(
				$this->table,
				array(
					'ips_item_id' => $item->getId()->getNumericId(),
					'ips_site_id' => $link->getSiteId(),
					'ips_site_page' => $link->getPageName()
				),
				__METHOD__
			);

			if ( !$success ) {
				break;
			}
		}

		return $success;
	}


	/**
	 * Internal callback for deleting a list of links.
	 *
	 * @note: this is public only because it acts as a callback, there should be no
	 *        reason to call this directly!
	 *
	 * @since 0.5
	 *
	 * @param Item $item
	 * @param SiteLink[] $links
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function deleteLinksInternal( Item $item, $links, DatabaseBase $dbw ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': deleting links for ' . $item->getId()->getSerialization() );

		//TODO: We can do this in a single query by collecting all the site IDs into a set.

		$success = true;
		foreach ( $links as $link ) {
			$success = $dbw->delete(
				$this->table,
				array(
					'ips_item_id' => $item->getId()->getNumericId(),
					'ips_site_id' => $link->getSiteId()
				),
				__METHOD__
			);

			if ( !$success ) {
				break;
			}
		}

		return $success;
	}


	/**
	 * @see SiteLinkCache::deleteLinksOfItem
	 *
	 * @since 0.1
	 *
	 * @param ItemId $itemId
	 *
	 * @return boolean Success indicator
	 * @throws MWException
	 */
	public function deleteLinksOfItem( ItemId $itemId ) {
		if ( $this->readonly ) {
			throw new MWException( 'Cannot write when in readonly mode' );
		}

		$dbw = $this->getConnection( DB_MASTER );

		$ok = $dbw->delete(
			$this->table,
			array( 'ips_item_id' => $itemId->getNumericId() ),
			__METHOD__
		);

		$this->releaseConnection( $dbw );
		return $ok;
	}

	/**
	 * @see SiteLinkLookup::getItemIdForLink
	 *
	 * @todo may want to deprecate this or change it to always return entity id object only
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return ItemId|null
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
		return $result === false ? null : ItemId::newFromNumber( (int)$result->ips_item_id );
	}

	/**
	 * @see SiteLinkLookup::getEntityIdForSiteLink
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getEntityIdForSiteLink( SiteLink $siteLink ) {
		$siteId = $siteLink->getSiteId();
		$pageName = $siteLink->getPageName();

		return $this->getItemIdForLink( $siteId, $pageName );
	}

	/**
	 * @see SiteLinkLookup::getConflictsForItem
	 *
	 * @param Item $item
	 * @param DatabaseBase|null $db
	 *
	 * @return array[]
	 */
	public function getConflictsForItem( Item $item, DatabaseBase $db = null ) {
		$links = $item->getSiteLinks();

		if ( $links === array() ) {
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
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return int
	 */
	public function countLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() ) {
		$dbr = $this->getConnection( DB_SLAVE );

		$conditions = array();

		if ( $numericIds !== array() ) {
			$conditions['ips_item_id'] = $numericIds;
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
	 * @param int[] $numericIds Numeric (unprefixed) item ids
	 * @param string[] $siteIds
	 * @param string[] $pageNames
	 *
	 * @return array[]
	 * @note The arrays returned by this method do not contain badges!
	 */
	public function getLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() ) {
		$dbr = $this->getConnection( DB_SLAVE );

		$conditions = array();

		if ( $numericIds !== array() ) {
			$conditions['ips_item_id'] = $numericIds;
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
	 * @param ItemId $itemId
	 *
	 * @return SiteLink[]
	 * @note The SiteLink objects returned by this method do not contain badges!
	 */
	public function getSiteLinksForItem( ItemId $itemId ) {
		$numericId = $itemId->getNumericId();

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
			$siteLinks[] = new SiteLink( $row->ips_site_id, $row->ips_site_page );
		}

		$this->releaseConnection( $dbr );

		return $siteLinks;
	}

}
