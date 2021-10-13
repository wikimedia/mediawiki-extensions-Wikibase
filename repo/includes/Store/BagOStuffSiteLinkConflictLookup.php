<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * Short-term sitelink conflict lookup using a BagOStuff (e.g. memcached).
 *
 * When {@link getConflictsForItem} is called in write mode (with DB_PRIMARY),
 * then the lookup will attempt to write each sitelink to the BagOStuff;
 * any sitelink already present there will be reported as a conflict.
 * The written sitelinks can later be removed by calling {@link clearConflictsForItem}.
 *
 * Compared to {@link SqlSiteLinkConflictLookup}, this class detects conflicts earlier,
 * but less reliably: its purpose is to prevent race conditions on simultaneous saves,
 * before sitelinks have been written to the database (in a secondary data update).
 *
 * @license GPL-2.0-or-later
 */
class BagOStuffSiteLinkConflictLookup implements SiteLinkConflictLookup {

	/** @var BagOStuff */
	private $bagOStuff;

	public function __construct(
		BagOStuff $bagOStuff
	) {
		$this->bagOStuff = $bagOStuff;
	}

	public function getConflictsForItem( Item $item, int $db = null ): array {
		$itemId = $item->getId()->getSerialization();
		$conflicts = [];
		$siteLinksToClear = [];

		foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
			if ( $db === DB_PRIMARY ) { // write mode
				$conflict = $this->getConflictForSiteLink( $itemId, $siteLink );
				if ( $conflict !== null ) {
					$conflicts[] = $conflict;
					$this->clearConflictsForSiteLinks( $siteLinksToClear );
					$db = null; // continue in read mode
				} else {
					$siteLinksToClear[] = $siteLink;
				}
			} else { // read mode
				$conflict = $this->peekConflictForSiteLink( $itemId, $siteLink );
				if ( $conflict !== null ) {
					$conflicts[] = $conflict;
				}
			}
		}

		return $conflicts;
	}

	/** Try to write a sitelink to BagOStuff, returning a conflict array on failure. */
	private function getConflictForSiteLink( string $itemId, SiteLink $siteLink ): ?array {
		$key = $this->cacheKey( $siteLink );
		$ttl = BagOStuff::TTL_MINUTE;
		$conflict = [
			'siteId' => $siteLink->getSiteId(),
			'sitePage' => $siteLink->getPageName(),
			'itemId' => null,
		];

		if ( $this->bagOStuff->add( $key, $itemId, $ttl ) ) {
			return null;
		}
		// add() failed, key already exists – check for self-conflict
		$otherItemId = $this->bagOStuff->get( $key );
		if ( $otherItemId === false ) {
			// now key doesn’t exist? try the add() again…
			if ( $this->bagOStuff->add( $key, $itemId, $ttl ) ) {
				return null;
			} else {
				return $conflict; // with unknown itemId
			}
		}
		// allow self-conflict, otherwise fail
		if ( $itemId === $otherItemId ) {
			return null;
		} else {
			$conflict['itemId'] = new ItemId( $otherItemId );
			return $conflict;
		}
	}

	/** Check if a sitelink is present in the BagOStuff without writing to it. */
	private function peekConflictForSiteLink( string $itemId, SiteLink $siteLink ): ?array {
		$key = $this->cacheKey( $siteLink );
		$otherItemId = $this->bagOStuff->get( $key );
		if ( $otherItemId === false || $otherItemId === $itemId ) {
			return null;
		} else {
			return [
				'siteId' => $siteLink->getSiteId(),
				'sitePage' => $siteLink->getPageName(),
				'itemId' => new ItemId( $otherItemId ),
			];
		}
	}

	/**
	 * Remove the item’s sitelinks from the BagOStuff.
	 * This should be called after the sitelinks were written to wb_items_per_site
	 * (and after that write was committed).
	 */
	public function clearConflictsForItem( Item $item ): void {
		$this->clearConflictsForSiteLinks( $item->getSiteLinkList()->toArray() );
	}

	private function clearConflictsForSiteLinks( array $siteLinks ): void {
		foreach ( $siteLinks as $siteLink ) {
			$this->bagOStuff->delete( $this->cacheKey( $siteLink ) );
		}
	}

	private function cacheKey( SiteLink $siteLink ): string {
		return $this->bagOStuff->makeKey(
			'wikibase-BagOStuffSiteLinkConflictLookup',
			$siteLink->getSiteId(),
			$siteLink->getPageName()
		);
	}

}
