<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Store\Sql;

use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\Sql\SiteLinkTable;

/**
 * Utility class for rebuilding the wb_items_per_site table.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class ItemsPerSiteBuilder {

	/**
	 * @var SiteLinkTable
	 */
	private $siteLinkTable;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityPrefetcher
	 */
	private $entityPrefetcher;

	/**
	 * @var MessageReporter|null
	 */
	private $reporter = null;

	/**
	 * The batch size, giving the number of rows to be updated in each database transaction.
	 * @var int
	 */
	private $batchSize = 100;

	/**
	 * @var RepoDomainDb
	 */
	private $db;

	public function __construct(
		SiteLinkTable $siteLinkTable,
		EntityLookup $entityLookup,
		EntityPrefetcher $entityPrefetcher,
		RepoDomainDb $db
	) {
		$this->siteLinkTable = $siteLinkTable;
		$this->entityLookup = $entityLookup;
		$this->entityPrefetcher = $entityPrefetcher;
		$this->db = $db;
	}

	public function setBatchSize( int $batchSize ): void {
		$this->batchSize = $batchSize;
	}

	/**
	 * Sets the reporter to use for reporting progress.
	 */
	public function setReporter( MessageReporter $reporter ): void {
		$this->reporter = $reporter;
	}

	public function rebuild( EntityIdPager $entityIdPager ): void {
		$this->report( 'Start rebuild...' );

		$total = 0;
		while ( true ) {
			$ids = $entityIdPager->fetchIds( $this->batchSize );
			if ( !$ids ) {
				break;
			}

			$total += $this->rebuildSiteLinks( $ids );
			$this->report( 'Processed ' . $total . ' entities.' );
		}

		$this->report( 'Rebuild done.' );
	}

	/**
	 * @param EntityId[] $itemIds
	 */
	private function rebuildSiteLinks( array $itemIds ): int {
		$this->entityPrefetcher->prefetch( $itemIds );

		$c = 0;
		foreach ( $itemIds as $itemId ) {
			if ( !( $itemId instanceof ItemId ) ) {
				// Just in case someone is using a EntityIdPager which doesn't filter non-Items
				continue;
			}
			$item = $this->entityLookup->getEntity( $itemId );

			if ( !( $item instanceof Item ) ) {
				continue;
			}

			$ok = $this->siteLinkTable->saveLinksOfItem( $item );
			if ( !$ok ) {
				$this->report( 'Saving sitelinks for Item ' . $item->getId()->getSerialization() . ' failed' );
			}

			$c++;
		}
		// Wait for the replicas, just in case we e.g. hit a range of ids which need a lot of writes.
		$this->db->replication()->wait();
		$this->db->autoReconfigure();

		return $c;
	}

	private function report( string $msg ): void {
		if ( $this->reporter ) {
			$this->reporter->reportMessage( $msg );
		}
	}

}
