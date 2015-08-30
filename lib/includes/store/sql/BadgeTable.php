<?php

namespace Wikibase\Lib\Store;

use DatabaseBase;
use DBAccessBase;
use MWException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * Represents a lookup database table for badges.
 * It should have these fields: bps_badge_id, bps_site_id, bps_site_page.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BadgeTable extends DBAccessBase implements BadgeStore {

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var bool
	 */
	private $readonly;

	/**
	 * @param string $table The table to use for the badges
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
	 * @see BadgeLookup::getBadgesForSiteLink
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId[]
	 */
	public function getBadgesForSiteLink( SiteLink $siteLink ) {
		$dbr = $this->getConnection( DB_SLAVE );

		$rows = $dbr->select(
			$this->table,
			array(
				'bps_badge_id'
			),
			array(
				'bps_site_id' => $siteLink->getSiteId(),
				'bps_site_page' => $siteLink->getPageName()
			),
			__METHOD__
		);

		$badges = array();

		foreach( $rows as $row ) {
			$badges[] = new ItemId( $row->bps_badge_id );
		}

		$this->releaseConnection( $dbr );

		return $badges;
	}

	/**
	 * @see BadgeLookup::getSiteLinksForBadge
	 *
	 * @param ItemId $badge
	 * @param string|null $siteId
	 *
	 * @return SiteLink[]
	 * @note The SiteLink objects returned by this method do not contain badges!
	 */
	public function getSiteLinksForBadge( ItemId $badge, $siteId = null ) {
		$dbr = $this->getConnection( DB_SLAVE );

		$conds = array(
			'bps_badge_id' => $badge->getSerialization()
		);

		if ( $siteId !== null ) {
			$conds['bps_site_id'] = $siteId;
		}

		$rows = $dbr->select(
			$this->table,
			array(
				'bps_site_id',
				'bps_site_page'
			),
			$conds,
			__METHOD__
		);

		$siteLinks = array();

		foreach( $rows as $row ) {
			$siteLinks[] = new SiteLink( $row->bps_site_id, $row->bps_site_page );
		}

		$this->releaseConnection( $dbr );

		return $siteLinks;
	}

	/**
	 * @see BadgeStore::saveBadgesOfSiteLink
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return boolean Success indicator
	 */
	public function saveBadgesOfSiteLink( SiteLink $siteLink ) {
		$newBadges = $siteLink->getBadges();
		$oldBadges = $this->getBadgesForSiteLink( $siteLink );

		$badgesToInsert = array_diff( $newBadges, $oldBadges );
		$badgesToDelete = array_diff( $oldBadges, $newBadges );

		if ( !$badgesToInsert && !$badgesToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": badges did not change, returning." );
			return true;
		}

		$ok = true;
		$dbw = $this->getConnection( DB_MASTER );

		//TODO: consider doing delete and insert in the same callback, so they share a transaction.

		if ( $ok && $badgesToDelete ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": " . count( $badgesToDelete ) . " badges to delete." );
			$ok = $dbw->deadlockLoop( array( $this, 'deleteBadgesInternal' ), $siteLink, $badgesToDelete, $dbw );
		}

		if ( $ok && $badgesToInsert ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": " . count( $badgesToInsert ) . " badges to insert." );
			$ok = $dbw->deadlockLoop( array( $this, 'insertBadgesInternal' ), $siteLink, $badgesToInsert, $dbw );
		}

		$this->releaseConnection( $dbw );

		return $ok;
	}

	/**
	 * Internal callback for inserting a list of badges.
	 *
	 * @note: this is public only because it acts as a callback, there should be no
	 *        reason to call this directly!
	 *
	 * @since 0.5
	 *
	 * @param SiteLink $siteLink
	 * @param ItemId[] $badges
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function insertBadgesInternal( SiteLink $siteLink, array $badges, DatabaseBase $dbw ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': inserting badges for '
           . $siteLink->getSiteId() . ':' . $siteLink->getPageName() );

		$insert = array();
		foreach ( $badges as $badge ) {
			$insert[] = array(
				'bps_badge_id' => $badge->getSerialization(),
				'bps_site_id' => $siteLink->getSiteId(),
				'bps_site_page' => $siteLink->getPageName()
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
	 * Internal callback for deleting a list of badges.
	 *
	 * @note: this is public only because it acts as a callback, there should be no
	 *        reason to call this directly!
	 *
	 * @since 0.5
	 *
	 * @param SiteLink $siteLink
	 * @param ItemId[] $badges
	 * @param DatabaseBase $dbw
	 *
	 * @return boolean Success indicator
	 */
	public function deleteBadgesInternal( SiteLink $siteLink, array $badges, DatabaseBase $dbw ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ': deleting badges for '
           . $siteLink->getSiteId() . ':' . $siteLink->getPageName() );

		$badgeIds = array();
		foreach ( $badges as $badge ) {
			$badgeIds[] = $badge->getSerialization();
		}

		$success = $dbw->delete(
			$this->table,
			array(
				'bps_badge_id' => $badgeIds,
				'bps_site_id' => $siteLink->getSiteId(),
				'bps_site_page' => $siteLink->getPageName()
			),
			__METHOD__
		);

		return $success;
	}

	/**
	 * @see BadgeStore::deleteBadgesOfSiteLink
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return boolean Success indicator
	 * @throws MWException
	 */
	public function deleteBadgesOfSiteLink( SiteLink $siteLink ) {
		if ( $this->readonly ) {
			throw new MWException( 'Cannot write when in readonly mode' );
		}

		$dbw = $this->getConnection( DB_MASTER );

		$ok = $dbw->delete(
			$this->table,
			array(
				'bps_site_id' => $siteLink->getSiteId(),
				'bps_site_page' => $siteLink->getPageName()
			),
			__METHOD__
		);

		$this->releaseConnection( $dbw );
		return $ok;
	}

	/**
	 * @see BadgeStore::clear
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

}
