<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use DatabaseBase;
use DBError;
use Exception;
use InvalidArgumentException;
use Traversable;
use Wikibase\Client\Store\Sql\ConsistentReadConnectionManager;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;
use Wikibase\Client\Usage\UsageTrackerException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * An SQL based usage tracker implementation.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlUsageTracker implements UsageTracker, UsageLookup {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var ConsistentReadConnectionManager
	 */
	private $connectionManager;

	/**
	 * @var int
	 */
	private $batchSize = 100;

	/**
	 * @param EntityIdParser $idParser
	 * @param ConsistentReadConnectionManager $connectionManager
	 */
	public function __construct( EntityIdParser $idParser, ConsistentReadConnectionManager $connectionManager ) {
		$this->idParser = $idParser;
		$this->connectionManager = $connectionManager;
	}

	/**
	 * @param DatabaseBase $db
	 *
	 * @return EntityUsageTable
	 */
	private function newUsageTable( DatabaseBase $db ) {
		return new EntityUsageTable( $this->idParser, $db, $this->batchSize );
	}

	/**
	 * Sets the query batch size.
	 *
	 * @param int $batchSize
	 *
	 * @throws InvalidArgumentException
	 */
	public function setBatchSize( $batchSize ) {
		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		$this->batchSize = $batchSize;
	}

	/**
	 * Returns the current query batch size.
	 *
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * Re-indexes the given list of EntityUsages so that each EntityUsage can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	private function reindexEntityUsages( array $usages ) {
		$reindexed = [];

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$key = $usage->getIdentityString();
			$reindexed[$key] = $usage;
		}

		return $reindexed;
	}

	/**
	 * @see UsageTracker::trackUsedEntities
	 *
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 * @param string $touched
	 *
	 * @throws Exception
	 * @throws UsageTrackerException
	 * @throws Exception
	 */
	public function trackUsedEntities( $pageId, array $usages, $touched ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		if ( !is_string( $touched ) || $touched === '' ) {
			throw new InvalidArgumentException( '$touched must be a timestamp string.' );
		}

		if ( empty( $usages ) ) {
			return;
		}

		// NOTE: while logically we'd like the below to be atomic, we don't wrap it in a
		// transaction to prevent long lock retention during big updates.
		$db = $this->connectionManager->getWriteConnection();

		try {
			$usageTable = $this->newUsageTable( $db );
			$oldUsages = $usageTable->queryUsages( $pageId, '>=', '00000000000000' );

			$newUsages = $this->reindexEntityUsages( $usages );
			$oldUsages = $this->reindexEntityUsages( $oldUsages );

			$keep = array_intersect_key( $oldUsages, $newUsages );
			$added = array_diff_key( $newUsages, $oldUsages );

			// update the "touched" timestamp for the remaining entries
			$usageTable->touchUsages( $pageId, $keep, $touched );
			$usageTable->addUsages( $pageId, $added, $touched );

			$this->connectionManager->releaseConnection( $db );
		} catch ( Exception $ex ) {
			$this->connectionManager->releaseConnection( $db );

			if ( $ex instanceof DBError ) {
				throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
			} else {
				throw $ex;
			}
		}
	}

	/**
	 * @see UsageTracker::pruneStaleUsages
	 *
	 * @param int $pageId
	 * @param string $lastUpdatedBefore timestamp
	 *
	 * @return EntityUsage[]
	 * @throws Exception
	 * @throws UsageTrackerException
	 */
	public function pruneStaleUsages( $pageId, $lastUpdatedBefore ) {
		// NOTE: while logically we'd like the below to be atomic, we don't wrap it in a
		// transaction to prevent long lock retention during big updates.
		$db = $this->connectionManager->getWriteConnection();

		try {
			$usageTable = $this->newUsageTable( $db );
			$pruned = $usageTable->pruneStaleUsages( $pageId, $lastUpdatedBefore );

			$this->connectionManager->releaseConnection( $db );
			return $pruned;
		} catch ( Exception $ex ) {
			$this->connectionManager->releaseConnection( $db );

			if ( $ex instanceof DBError ) {
				throw new UsageTrackerException( $ex->getMessage(), $ex->getCode(), $ex );
			} else {
				throw $ex;
			}
		}
	}

	/**
	 * @see UsageLookup::getUsagesForPage
	 *
	 * @param int $pageId
	 *
	 * @return EntityUsage[]
	 * @throws UsageTrackerException
	 */
	public function getUsagesForPage( $pageId ) {
		$db = $this->connectionManager->getReadConnection();

		$usageTable = $this->newUsageTable( $db );
		$usages = $usageTable->queryUsages( $pageId );

		$this->connectionManager->releaseConnection( $db );

		return $usages;
	}

	/**
	 * @see UsageLookup::getPagesUsing
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects
	 *
	 * @return Traversable A traversable over PageEntityUsages grouped by page.
	 * @throws UsageTrackerException
	 */
	public function getPagesUsing( array $entityIds, array $aspects = [] ) {
		if ( empty( $entityIds ) ) {
			return new ArrayIterator();
		}

		$db = $this->connectionManager->getReadConnection();

		$usageTable = $this->newUsageTable( $db );
		$pages = $usageTable->getPagesUsing( $entityIds, $aspects );

		$this->connectionManager->releaseConnection( $db );

		return $pages;
	}

	/**
	 * @see UsageLookup::getUnusedEntities
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[]
	 * @throws UsageTrackerException
	 */
	public function getUnusedEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return [];
		}

		$db = $this->connectionManager->getReadConnection();

		$usageTable = $this->newUsageTable( $db );
		$unused = $usageTable->getUnusedEntities( $entityIds );

		$this->connectionManager->releaseConnection( $db );

		return $unused;
	}

}
