<?php

namespace Wikibase\Lib\Store;

use DatabaseBase;
use DBAccessBase;
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
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SiteLinkTable extends DBAccessBase implements SiteLinkStore {

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

		parent::__construct( $wiki );
	}

	/**
	 * @param SiteLink[] $siteLinks1
	 * @param SiteLink[] $siteLinks2
	 *
	 * @return SiteLink[]
	 */
	private function diffSiteLinks( array $siteLinks1, array $siteLinks2 ) {
		return array_udiff(
			$siteLinks1,
			$siteLinks2,
			function( SiteLink $a, SiteLink $b ) {
				$result = strcmp( $a->getSiteId(), $b->getSiteId() );

				if ( $result === 0 ) {
					$result = strcmp( $a->getPageName(), $b->getPageName() );
				}

				return $result;
			}
		);
	}

	/**
	 * @see SiteLinkStore::saveLinksOfItem
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 *
	 * @return boolean Success indicator
	 */
	public function saveLinksOfItem( Item $item ) {
		//First check whether there's anything to update
		$newLinks = $item->getSiteLinkList()->toArray();
		$oldLinks = $this->getSiteLinksForItem( $item->getId() );

		$linksToInsert = $this->diffSiteLinks( $newLinks, $oldLinks );
		$linksToDelete = $this->diffSiteLinks( $oldLinks, $newLinks );

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
	public function insertLinksInternal( Item $item, array $links, DatabaseBase $dbw ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': inserting links for ' . $item->getId()->getSerialization() );

		$insert = array();
		foreach ( $links as $siteLink ) {
			$insert[] = array(
				'ips_item_id' => $item->getId()->getNumericId(),
				'ips_site_id' => $siteLink->getSiteId(),
				'ips_site_page' => $siteLink->getPageName()
			);
		}

		$success = $dbw->insert(
			$this->table,
			$insert,
			__METHOD__,
			array( 'IGNORE' )
		);

		return $success && $dbw->affectedRows();
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
	public function deleteLinksInternal( Item $item, array $links, DatabaseBase $dbw ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': deleting links for ' . $item->getId()->getSerialization() );

		$siteIds = array();
		foreach ( $links as $siteLink ) {
			$siteIds[] = $siteLink->getSiteId();
		}

		$success = $dbw->delete(
			$this->table,
			array(
				'ips_item_id' => $item->getId()->getNumericId(),
				'ips_site_id' => $siteIds
			),
			__METHOD__
		);

		return $success;
	}

	/**
	 * @see SiteLinkStore::deleteLinksOfItem
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
	 * @see SiteLinkLookup::getItemIdForSiteLink
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getItemIdForSiteLink( SiteLink $siteLink ) {
		return $this->getItemIdForLink( $siteLink->getSiteId(), $siteLink->getPageName() );
	}

	/**
	 * @see SiteLinkStore::clear
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

		foreach ( $rows as $row ) {
			$siteLinks[] = new SiteLink( $row->ips_site_id, $row->ips_site_page );
		}

		$this->releaseConnection( $dbr );

		return $siteLinks;
	}

}
