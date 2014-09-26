<?php

namespace Wikibase\Repo\Store\SQL;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\SiteLinkTable;
use Wikibase\Repo\Store\EntityIdPager;

/**
 * Utility class for rebuilding the wb_items_per_site table.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class ItemsPerSiteBuilder {

	private $siteLinkTable;
	private $entityLookup;

	/**
	 * @var MessageReporter $reporter
	 */
	private $reporter;

	/**
	 * The batch size, giving the number of rows to be updated in each database transaction.
	 * @var int
	 */
	private $batchSize = 100;

	public function __construct( SiteLinkTable $siteLinkTable, EntityLookup $entityLookup ) {
		$this->siteLinkTable = $siteLinkTable;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @since 0.5
	 *
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * Sets the reporter to use for reporting preogress.
	 *
	 * @param MessageReporter $reporter
	 */
	public function setReporter( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * @since 0.5
	 *
	 * @param EntityIdPager $entityIdPager
	 */
	public function rebuild( EntityIdPager $entityIdPager ) {
		$this->report( 'Start rebuild...' );

		$i = 0;
		while ( $ids = $entityIdPager->fetchIds( $this->batchSize ) ) {
			$i = $i + $this->rebuildSiteLinks( $ids );

			$this->report( 'Processed ' . $i . ' entities.' );
		};

		$this->report( 'Rebuild done.' );
	}

	/**
	 * Rebuilds EntityPerPageTable for specified pages
	 *
	 * @since 0.5
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return int
	 */
	private function rebuildSiteLinks( array $entityIds ) {
		$c = 0;
		foreach ( $entityIds as $entityId ) {
			/* @var $entityId EntityId */
			if ( !$entityId->getEntityType() === Item::ENTITY_TYPE ) {
				// Just in case someone is using a EntityIdPager which doesn't filter non-Items
				continue;
			}
			$item = $this->entityLookup->getEntity( $entityId );

			if ( !$item ) {
				continue;
			}

			$ok = $this->siteLinkTable->saveLinksOfItem( $item );
			if ( !$ok ) {
				$this->report( 'Saving sitelinks for Item ' . $item->getId()->getSerialization() . ' failed' );
			}

			$c++;
		}
		// Wait for the slaves (just in case we eg. hit a range of ids which need a lot of writes)
		$this->waitForSlaves();

		return $c;
	}

	/**
	 * Wait for slaves (quietly)
	 *
	 * @todo: this should be in the Database class.
	 * @todo: thresholds should be configurable
	 *
	 * @author Tim Starling (stolen from recompressTracked.php)
	 */
	protected function waitForSlaves() {
		$lb = wfGetLB(); //TODO: allow foreign DB, get from $this->table

		while ( true ) {
			list( , $maxLag ) = $lb->getMaxLag();
			if ( $maxLag < 2 ) {
				break;
			}

			$this->report( "Slaves are lagged by $maxLag seconds, sleeping..." );
			sleep( 5 );
			$this->report( "Resuming..." );
		}
	}

	/**
	 * reports a message
	 *
	 * @since 0.5
	 *
	 * @param $msg
	 */
	protected function report( $msg ) {
		if ( $this->reporter ) {
			$this->reporter->reportMessage( $msg );
		}
	}

}
