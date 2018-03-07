<?php

namespace Wikibase\Lib\Store\Sql;

use DBAccessBase;
use InvalidArgumentException;
use MWException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IDatabase;

/**
 * Represents a lookup database table for sitelinks.
 * It should have these fields: ips_item_id, ips_site_id, ips_site_page.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SiteLinkTable extends DBAccessBase implements SiteLinkStore {

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var bool
	 */
	protected $readonly;

	/**
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

		if ( $ok && $linksToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": " . count( $linksToDelete ) . " links to delete." );
			$ok = $this->deleteLinks( $item, $linksToDelete, $dbw );
		}

		if ( $ok && $linksToInsert ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": " . count( $linksToInsert ) . " links to insert." );
			$ok = $this->insertLinks( $item, $linksToInsert, $dbw );
		}

		$this->releaseConnection( $dbw );

		return $ok;
	}

	/**
	 * @param Item $item
	 * @param SiteLink[] $links
	 * @param IDatabase $dbw
	 *
	 * @return bool Success indicator
	 */
	private function insertLinks( Item $item, array $links, IDatabase $dbw ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': inserting links for ' . $item->getId()->getSerialization() );

		$insert = [];
		foreach ( $links as $siteLink ) {
			$insert[] = [
				'ips_item_id' => $item->getId()->getNumericId(),
				'ips_site_id' => $siteLink->getSiteId(),
				'ips_site_page' => $siteLink->getPageName()
			];
		}

		$success = $dbw->insert(
			$this->table,
			$insert,
			__METHOD__,
			[ 'IGNORE' ]
		);

		return $success && $dbw->affectedRows();
	}

	/**
	 * @param Item $item
	 * @param SiteLink[] $links
	 * @param IDatabase $dbw
	 *
	 * @return bool Success indicator
	 */
	private function deleteLinks( Item $item, array $links, IDatabase $dbw ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': deleting links for ' . $item->getId()->getSerialization() );

		$siteIds = [];
		foreach ( $links as $siteLink ) {
			$siteIds[] = $siteLink->getSiteId();
		}

		$success = $dbw->delete(
			$this->table,
			[
				'ips_item_id' => $item->getId()->getNumericId(),
				'ips_site_id' => $siteIds
			],
			__METHOD__
		);

		return $success;
	}

	/**
	 * @see SiteLinkStore::deleteLinksOfItem
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
			[ 'ips_item_id' => $itemId->getNumericId() ],
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
	 * @throws InvalidArgumentException if a parameter does not have the expected type
	 * @return ItemId|null
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle ) {
		Assert::parameterType( 'string', $globalSiteId, '$globalSiteId' );
		Assert::parameterType( 'string', $pageTitle, '$pageTitle' );

		// We store page titles with spaces instead of underscores
		$pageTitle = str_replace( '_', ' ', $pageTitle );

		$db = $this->getConnection( DB_REPLICA );

		$result = $db->selectRow(
			$this->table,
			[ 'ips_item_id' ],
			[
				'ips_site_id' => $globalSiteId,
				'ips_site_page' => $pageTitle,
			]
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
	public function getLinks( array $numericIds = [], array $siteIds = [], array $pageNames = [] ) {
		$dbr = $this->getConnection( DB_REPLICA );

		$conditions = [];

		if ( $numericIds !== [] ) {
			$conditions['ips_item_id'] = $numericIds;
		}

		if ( $siteIds !== [] ) {
			$conditions['ips_site_id'] = $siteIds;
		}

		if ( $pageNames !== [] ) {
			$conditions['ips_site_page'] = $pageNames;
		}

		$links = $dbr->select(
			$this->table,
			[
				'ips_site_id',
				'ips_site_page',
				'ips_item_id',
			],
			$conditions,
			__METHOD__
		);

		$siteLinks = [];

		foreach ( $links as $link ) {
			$siteLinks[] = [
				$link->ips_site_id,
				$link->ips_site_page,
				$link->ips_item_id,
			];
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

		$dbr = $this->getConnection( DB_REPLICA );

		$rows = $dbr->select(
			$this->table,
			[
				'ips_site_id', 'ips_site_page'
			],
			[
				'ips_item_id' => $numericId
			],
			__METHOD__
		);

		$siteLinks = [];

		foreach ( $rows as $row ) {
			$siteLinks[] = new SiteLink( $row->ips_site_id, $row->ips_site_page );
		}

		$this->releaseConnection( $dbr );

		return $siteLinks;
	}

	/**
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @throws InvalidArgumentException if a parameter does not have the expected type
	 * @return EntityId|null
	 */
	public function getEntityIdForLinkedTitle( $globalSiteId, $pageTitle ) {
		return $this->getItemIdForLink( $globalSiteId, $pageTitle );
	}

}
